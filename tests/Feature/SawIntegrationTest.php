<?php

namespace Tests\Feature;

use App\Models\Cuti;
use App\Models\Employee;
use App\Models\Kriteria;
use App\Models\PenilaianCuti;
use App\Models\UnitKerja;
use App\Models\User;
use App\Notifications\CutiStatusUpdated;
use App\Services\SawService;
use App\Exports\CutiExport;
use Database\Seeders\KriteriaSeeder;
use Database\Seeders\SawDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SawIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private string $periode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(KriteriaSeeder::class);
        $this->seed(SawDemoSeeder::class);

        $this->periode = now()->format('Y-m');
    }

    private function cuti(string $nama): Cuti
    {
        return Cuti::query()
            ->where('periode', $this->periode)
            ->whereHas('employee', fn ($query) => $query->where('nama', $nama))
            ->firstOrFail();
    }

    private function nilaiC5(Cuti $cuti): float
    {
        return (float) PenilaianCuti::query()
            ->where('cuti_id', $cuti->id)
            ->whereHas('kriteria', fn ($query) => $query->where('kode', 'C5'))
            ->value('nilai');
    }

    public function test_seeder_menghasilkan_ranking_sesuai_bab_iv(): void
    {
        $rows = Cuti::query()
            ->where('periode', $this->periode)
            ->whereIn('status_global', Cuti::STATUS_PENDING)
            ->with('employee')
            ->orderBy('ranking')
            ->get();

        $this->assertSame(
            ['Siti', 'Budi', 'Rina', 'Andi', 'Joko'],
            $rows->pluck('employee.nama')->all()
        );

        $this->assertSame([1, 2, 3, 4, 5], $rows->pluck('ranking')->all());

        $expected = [
            'Siti' => 0.849342,
            'Budi' => 0.790351,
            'Rina' => 0.723333,
            'Andi' => 0.694211,
            'Joko' => 0.589035,
        ];

        foreach ($rows as $row) {
            $this->assertEqualsWithDelta(
                $expected[$row->employee->nama],
                (float) $row->skor_prioritas,
                0.000001,
                "Skor {$row->employee->nama} tidak sesuai."
            );
        }
    }

    public function test_skor_disimpan_enam_desimal(): void
    {
        $cuti = $this->cuti('Siti');

        $this->assertMatchesRegularExpression(
            '/^0\.849342$/',
            number_format((float) $cuti->skor_prioritas, 6, '.', '')
        );
    }

    public function test_c5_dihitung_per_divisi_bukan_per_pegawai(): void
    {
        $budi = $this->cuti('Budi');
        $joko = $this->cuti('Joko');

        $this->assertSame(
            $budi->employee->unit_kerja_id,
            $joko->employee->unit_kerja_id,
            'Budi dan Joko harus berada pada divisi yang sama.'
        );

        $this->assertSame(75.0, $this->nilaiC5($budi));
        $this->assertSame(75.0, $this->nilaiC5($joko));
    }

    public function test_semua_kriteria_tersimpan_untuk_setiap_cuti(): void
    {
        foreach (['Siti', 'Budi', 'Rina', 'Andi', 'Joko'] as $nama) {
            $cuti = $this->cuti($nama);

            $this->assertCount(
                5,
                $cuti->penilaianCutis,
                "Jumlah penilaian {$nama} tidak lengkap."
            );
        }
    }

    public function test_cuti_yang_sudah_diproses_keluar_dari_ranking(): void
    {
        $siti = $this->cuti('Siti');
        $siti->update(['status_global' => Cuti::STATUS_DISETUJUI]);

        $siti->refresh();

        $this->assertNull($siti->ranking);
        $this->assertNull($siti->skor_prioritas);

        $pending = Cuti::query()
            ->where('periode', $this->periode)
            ->whereIn('status_global', Cuti::STATUS_PENDING)
            ->orderBy('ranking')
            ->get();

        $this->assertCount(4, $pending);
        $this->assertSame([1, 2, 3, 4], $pending->pluck('ranking')->all());
    }

    public function test_observer_menghitung_cuti_baru(): void
    {
        $joko = $this->cuti('Joko');
        $tanggal = now()->startOfMonth()->addDays(20)->format('Y-m-d');

        $cuti = Cuti::create([
            'employee_id' => $joko->employee_id,
            'jenis_cuti' => 'Cuti Tahunan',
            'tanggal_mulai' => $tanggal,
            'tanggal_akhir' => $tanggal,
            'tanggal_cuti_array' => [$tanggal],
            'alasan' => 'Cuti tambahan untuk menguji observer',
            'urgensi' => 5,
            'periode' => $this->periode,
            'lama_cuti' => 1,
            'status_global' => Cuti::STATUS_MENUNGGU_ATASAN,
        ]);

        $cuti->refresh();

        $this->assertNotNull($cuti->ranking);
        $this->assertNotNull($cuti->skor_prioritas);

        $this->assertSame(
            6,
            Cuti::query()
                ->where('periode', $this->periode)
                ->whereIn('status_global', Cuti::STATUS_PENDING)
                ->count()
        );
    }

    public function test_rincian_total_sama_dengan_skor_tersimpan(): void
    {
        $cuti = $this->cuti('Siti');
        $rows = app(SawService::class)->rincian($cuti);

        $this->assertCount(5, $rows);

        $total = collect($rows)->sum('kontribusi');

        $this->assertEqualsWithDelta(
            (float) $cuti->skor_prioritas,
            $total,
            0.000001
        );
    }

    public function test_beban_kerja_fallback_seratus(): void
    {
        $unit = UnitKerja::create(['nama' => 'Unit Tanpa Beban']);

        $this->assertSame(
            100.0,
            app(SawService::class)->bebanKerja($unit->id, $this->periode)
        );
    }

    public function test_build_matrix_mengembalikan_lima_alternatif(): void
    {
        $matrix = app(SawService::class)->buildMatrix($this->periode);

        $this->assertCount(5, $matrix);

        foreach ($matrix as $row) {
            $this->assertCount(5, $row['values']);
        }
    }

    public function test_validasi_total_bobot(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(SawService::class)->validateBobot(
            collect([
                ['kode' => 'C1', 'bobot' => 0.30],
                ['kode' => 'C2', 'bobot' => 0.25],
                ['kode' => 'C3', 'bobot' => 0.15],
                ['kode' => 'C4', 'bobot' => 0.20],
                ['kode' => 'C5', 'bobot' => 0.05],
            ])
        );
    }

    public function test_label_status_rekomendasi(): void
    {
        $this->assertSame('Prioritas Utama', $this->cuti('Siti')->status_rekomendasi);
        $this->assertSame('Direkomendasikan', $this->cuti('Budi')->status_rekomendasi);
        $this->assertSame('Pertimbangan', $this->cuti('Rina')->status_rekomendasi);
        $this->assertSame('Ditunda', $this->cuti('Andi')->status_rekomendasi);
        $this->assertSame('Ditunda', $this->cuti('Joko')->status_rekomendasi);

        $this->assertSame('Belum dihitung', (new Cuti())->status_rekomendasi);
    }

    public function test_masa_kerja_dihitung_dalam_tahun_penuh(): void
    {
        $siti = Employee::query()->where('nama', 'Siti')->firstOrFail();

        $this->assertSame(5, $siti->masa_kerja_tahun);
    }

    public function test_periode_tanpa_cuti_mengembalikan_kosong(): void
    {
        $hasil = app(SawService::class)->hitungPrioritas('2099-01');

        $this->assertCount(0, $hasil);
    }

    public function test_semua_cuti_diproses_maka_ranking_dibersihkan(): void
    {
        Cuti::query()
            ->where('periode', $this->periode)
            ->update(['status_global' => Cuti::STATUS_DISETUJUI]);

        $hasil = app(SawService::class)->hitungPrioritas($this->periode);

        $this->assertCount(0, $hasil);
        $this->assertSame(
            0,
            Cuti::query()
                ->where('periode', $this->periode)
                ->whereNotNull('ranking')
                ->count()
        );
    }

    public function test_kriteria_seeder_idempoten(): void
    {
        $this->seed(KriteriaSeeder::class);

        $this->assertSame(5, Kriteria::query()->count());
        $this->assertEqualsWithDelta(
            1.0,
            (float) Kriteria::query()->sum('bobot'),
            0.0001
        );
    }

    public function test_notifikasi_status_tersimpan_ke_database(): void
    {
        $pegawai = User::query()->where('email', 'budi@tvri.go.id')->firstOrFail();
        $cuti = $this->cuti('Budi');

        $pegawai->notify(new CutiStatusUpdated($cuti));

        $this->assertDatabaseCount('notifications', 1);

        $notification = $pegawai->notifications()->first();

        $this->assertSame('Status Pengajuan Cuti Diperbarui', $notification->data['title']);
        $this->assertSame($cuti->id, $notification->data['cuti_id']);
    }

    public function test_export_excel_memuat_kolom_saw(): void
    {
        $cutis = Cuti::query()
            ->with('employee.unitKerja')
            ->where('periode', $this->periode)
            ->get();

        $export = new CutiExport($cutis);

        $headings = $export->headings();

        $this->assertContains('Rank SAW', $headings[4]);
        $this->assertContains('Skor SAW', $headings[4]);

        $this->assertCount(14, $export->map($cutis->first()));
    }
}
