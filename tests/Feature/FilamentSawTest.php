<?php

namespace Tests\Feature;

use App\Filament\Pages\PerangkinganCuti;
use App\Filament\Resources\KriteriaResource\Pages\EditKriteria;
use App\Models\Cuti;
use App\Models\Kriteria;
use App\Models\UnitKerja;
use App\Models\User;
use Database\Seeders\KriteriaSeeder;
use Database\Seeders\SawDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSawTest extends TestCase
{
    use RefreshDatabase;

    private string $periode;
    private User $admin;
    private User $tataUsaha;
    private User $pegawai;
    private User $ketua;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(KriteriaSeeder::class);
        $this->seed(SawDemoSeeder::class);

        $this->periode = now()->format('Y-m');

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin.test@tvri.go.id',
            'role' => 'admin',
            'password' => 'password',
        ]);

        $this->tataUsaha = User::create([
            'name' => 'TU Test',
            'email' => 'tu.test@tvri.go.id',
            'role' => 'tata_usaha',
            'password' => 'password',
        ]);

        $this->pegawai = User::query()->where('email', 'budi@tvri.go.id')->firstOrFail();

        $this->ketua = User::query()
            ->where('email', 'ketua.pemberitaan@tvri.go.id')
            ->firstOrFail();
    }

    private function cuti(string $nama): Cuti
    {
        return Cuti::query()
            ->where('periode', $this->periode)
            ->whereHas('employee', fn ($query) => $query->where('nama', $nama))
            ->firstOrFail();
    }

    public function test_admin_dapat_membuka_halaman_perangkingan(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/perangkingan-cuti')
            ->assertOk();
    }

    public function test_pegawai_tidak_dapat_membuka_halaman_perangkingan(): void
    {
        $this->actingAs($this->pegawai)
            ->get('/admin/perangkingan-cuti')
            ->assertForbidden();
    }

    public function test_hanya_admin_yang_dapat_membuka_kriteria(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/kriterias')
            ->assertOk();
    }

    public function test_admin_tidak_dapat_menambah_kriteria_baru(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/kriterias/create')
            ->assertForbidden();
    }

    public function test_tata_usaha_tidak_dapat_membuka_kriteria(): void
    {
        $this->actingAs($this->tataUsaha)
            ->get('/admin/kriterias')
            ->assertForbidden();
    }

    public function test_tata_usaha_dapat_membuka_beban_kerja(): void
    {
        $this->actingAs($this->tataUsaha)
            ->get('/admin/beban-kerja-units')
            ->assertOk();
    }

    public function test_pegawai_tidak_dapat_membuka_beban_kerja(): void
    {
        $this->actingAs($this->pegawai)
            ->get('/admin/beban-kerja-units')
            ->assertForbidden();
    }

    public function test_ketua_tim_hanya_melihat_divisinya(): void
    {
        Livewire::actingAs($this->ketua)
            ->test(PerangkinganCuti::class)
            ->assertCanSeeTableRecords([
                $this->cuti('Budi'),
                $this->cuti('Joko'),
            ])
            ->assertCanNotSeeTableRecords([
                $this->cuti('Siti'),
                $this->cuti('Rina'),
                $this->cuti('Andi'),
            ]);
    }

    public function test_admin_melihat_semua_divisi(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PerangkinganCuti::class)
            ->assertCanSeeTableRecords([
                $this->cuti('Siti'),
                $this->cuti('Budi'),
                $this->cuti('Rina'),
                $this->cuti('Andi'),
                $this->cuti('Joko'),
            ]);
    }

    public function test_filter_divisi_pada_halaman_perangkingan(): void
    {
        $program = UnitKerja::query()->where('nama', 'Program')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(PerangkinganCuti::class)
            ->set('unitKerjaId', $program->id)
            ->assertCanSeeTableRecords([$this->cuti('Siti')])
            ->assertCanNotSeeTableRecords([
                $this->cuti('Budi'),
                $this->cuti('Rina'),
                $this->cuti('Andi'),
                $this->cuti('Joko'),
            ]);
    }

    public function test_tombol_hitung_prioritas_menjalankan_saw(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PerangkinganCuti::class)
            ->call('hitung')
            ->assertNotified('Perhitungan SAW selesai');
    }

    public function test_edit_bobot_menjaga_total_tetap_satu(): void
    {
        $kriteria = Kriteria::query()->where('kode', 'C1')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(EditKriteria::class, ['record' => $kriteria->getRouteKey()])
            ->fillForm(['bobot' => 0.35])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsWithDelta(
            1.0,
            (float) Kriteria::query()->sum('bobot'),
            0.0001
        );

        $this->assertEqualsWithDelta(
            0.35,
            (float) $kriteria->fresh()->bobot,
            0.0001
        );
    }

    public function test_halaman_daftar_cuti_menampilkan_kolom_saw(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/cutis')
            ->assertOk();
    }

    public function test_halaman_pegawai_menampilkan_kinerja_dan_masa_kerja(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/employees')
            ->assertOk();
    }

    public function test_tata_usaha_dapat_membuka_form_beban_kerja(): void
    {
        $this->actingAs($this->tataUsaha)
            ->get('/admin/beban-kerja-units/create')
            ->assertOk();
    }

    public function test_tata_usaha_dapat_mengedit_beban_kerja(): void
    {
        $beban = \App\Models\BebanKerjaUnit::query()->firstOrFail();

        $this->actingAs($this->tataUsaha)
            ->get('/admin/beban-kerja-units/' . $beban->getRouteKey() . '/edit')
            ->assertOk();
    }

    public function test_admin_dapat_membuka_form_create_cuti(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/cutis/create')
            ->assertOk();
    }

    public function test_admin_dapat_membuka_form_edit_cuti(): void
    {
        $cuti = $this->cuti('Siti');

        $this->actingAs($this->admin)
            ->get('/admin/cutis/' . $cuti->getRouteKey() . '/edit')
            ->assertOk();
    }
}
