<?php

namespace Database\Seeders;

use App\Models\BebanKerjaUnit;
use App\Models\Cuti;
use App\Models\Employee;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\SawService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SawDemoSeeder extends Seeder
{
    public function run(): void
    {
        $periode = now()->format('Y-m');

        // 1. Siapkan divisi/unit kerja.
        $pemberitaan = $this->unit('Pemberitaan');
        $program     = $this->unit('Program');
        $teknik      = $this->unit('Teknik');
        $umum        = $this->unit('Umum');

        // Pastikan setiap divisi punya ketua agar alur approval tahap 1 bisa diuji.
        $this->ketua($pemberitaan, 'ketua.pemberitaan@tvri.go.id', 'Ketua Pemberitaan');
        $this->ketua($program, 'ketua.program@tvri.go.id', 'Ketua Program');
        $this->ketua($teknik, 'ketua.teknik@tvri.go.id', 'Ketua Teknik');
        $this->ketua($umum, 'ketua.umum@tvri.go.id', 'Ketua Umum');

        // 2. Siapkan pegawai demo (Budi & Siti memakai akun yang sudah ada jika tersedia).
        $budi = $this->pegawai(
            name: 'Budi',
            email: 'budi@tvri.go.id',
            nip: '199001012020121001',
            jabatan: 'Reporter',
            unit: $pemberitaan,
            tanggalBergabung: now()->subYears(3),
            sisaCuti: 10,
            kinerja: 85,
        );

        $siti = $this->pegawai(
            name: 'Siti',
            email: 'siti@tvri.go.id',
            nip: '199505052021012001',
            jabatan: 'Kameramen',
            unit: $program,
            tanggalBergabung: now()->subYears(5),
            sisaCuti: 8,
            kinerja: 90,
        );

        $andi = $this->pegawai(
            name: 'Andi',
            email: 'andi@tvri.go.id',
            nip: '199201012019011001',
            jabatan: 'Teknisi',
            unit: $teknik,
            tanggalBergabung: now()->subYears(1),
            sisaCuti: 12,
            kinerja: 70,
        );

        $rina = $this->pegawai(
            name: 'Rina',
            email: 'rina@tvri.go.id',
            nip: '199305052018012001',
            jabatan: 'Staf Umum',
            unit: $umum,
            tanggalBergabung: now()->subYears(4),
            sisaCuti: 6,
            kinerja: 95,
        );

        $joko = $this->pegawai(
            name: 'Joko',
            email: 'joko@tvri.go.id',
            nip: '199401012020011001',
            jabatan: 'Jurnalis',
            unit: $pemberitaan,
            tanggalBergabung: now()->subYears(2),
            sisaCuti: 9,
            kinerja: 75,
        );

        // 3. Beban kerja divisi (C5). Budi & Joko satu divisi => nilai sama.
        $bebanKerja = [
            $pemberitaan->id => 75,
            $program->id     => 80,
            $teknik->id      => 50,
            $umum->id        => 60,
        ];

        foreach ($bebanKerja as $unitId => $nilai) {
            BebanKerjaUnit::updateOrCreate(
                ['unit_kerja_id' => $unitId, 'periode' => $periode],
                ['nilai' => $nilai]
            );
        }

        // 4. Pengajuan cuti demo pada periode yang sama.
        $employees = [
            ['employee' => $budi, 'urgensi' => 4],
            ['employee' => $siti, 'urgensi' => 5],
            ['employee' => $andi, 'urgensi' => 2],
            ['employee' => $rina, 'urgensi' => 3],
            ['employee' => $joko, 'urgensi' => 1],
        ];

        foreach ($employees as $index => $row) {
            $tanggal = Carbon::createFromFormat('Y-m', $periode)
                ->startOfMonth()
                ->addDays(9 + $index)
                ->format('Y-m-d');

            Cuti::updateOrCreate(
                [
                    'employee_id' => $row['employee']->id,
                    'periode'     => $periode,
                    'alasan'      => 'Demo perhitungan SAW',
                ],
                [
                    'jenis_cuti'             => 'Cuti Tahunan',
                    'tanggal_mulai'          => $tanggal,
                    'tanggal_akhir'          => $tanggal,
                    'tanggal_cuti_array'     => [$tanggal],
                    'urgensi'                => $row['urgensi'],
                    'lama_cuti'              => 1,
                    'alamat_selama_cuti'     => 'Yogyakarta',
                    'status_atasan_langsung' => 'pending',
                    'status_tata_usaha'      => 'pending',
                    'status_kepala'          => 'pending',
                    'status_global'          => Cuti::STATUS_MENUNGGU_ATASAN,
                ]
            );
        }

        // 5. Langsung hitung agar halaman perangkingan siap demo.
        app(SawService::class)->hitungPrioritas($periode);
    }

    private function unit(string $nama): UnitKerja
    {
        // Rename unit lama agar konsisten dengan istilah divisi pada laporan.
        if ($nama === 'Pemberitaan') {
            UnitKerja::where('nama', 'Tim Berita')->update(['nama' => 'Pemberitaan']);
        }

        if ($nama === 'Teknik') {
            UnitKerja::where('nama', 'Tim Teknik')->update(['nama' => 'Teknik']);
        }

        return UnitKerja::firstOrCreate(['nama' => $nama]);
    }

    private function ketua(UnitKerja $unit, string $email, string $name): void
    {
        // Jangan menimpa ketua yang sudah ditunjuk admin.
        if ($unit->fresh()->ketua_user_id) {
            return;
        }

        /** @var User $user */
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'role'     => 'ketua_tim',
                'password' => Hash::make('password'),
            ]
        );

        $user->update([
            'name'          => $name,
            'role'          => 'ketua_tim',
            'unit_kerja_id' => $unit->id,
        ]);

        Employee::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama'              => $name,
                'email'             => $email,
                'NIP'               => '1985' . str_pad((string) $unit->id, 14, '0', STR_PAD_LEFT),
                'jabatan'           => 'Ketua ' . $unit->nama,
                'nilai_kinerja'     => 80,
                'unit_kerja_id'     => $unit->id,
                'tanggal_bergabung' => now()->subYears(8)->format('Y-m-d'),
                'telp'              => '081234567890',
                'alamat_domisili'   => 'Yogyakarta',
                'sisa_cuti_tahunan' => 12,
            ]
        );
    }

    private function pegawai(
        string $name,
        string $email,
        string $nip,
        string $jabatan,
        UnitKerja $unit,
        Carbon $tanggalBergabung,
        int $sisaCuti,
        float $kinerja,
    ): Employee {
        /** @var User $user */
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'role'     => 'pegawai',
                'password' => Hash::make('password'),
            ]
        );

        $user->update([
            'name'          => $name,
            'role'          => 'pegawai',
            'unit_kerja_id' => $unit->id,
        ]);

        /** @var Employee $employee */
        $employee = Employee::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama'               => $name,
                'email'              => $email,
                'NIP'                => $nip,
                'jabatan'            => $jabatan,
                'nilai_kinerja'      => $kinerja,
                'unit_kerja_id'      => $unit->id,
                'tanggal_bergabung'  => $tanggalBergabung->format('Y-m-d'),
                'telp'               => '081234567890',
                'alamat_domisili'    => 'Yogyakarta',
                'sisa_cuti_tahunan'  => $sisaCuti,
            ]
        );

        return $employee;
    }
}
