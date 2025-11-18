<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use App\Models\UnitKerja;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Buat Unit Kerja
        $unitSDM = UnitKerja::create(['nama' => 'Tim SDM']);
        $unitBerita = UnitKerja::create(['nama' => 'Tim Berita']);
        $unitTeknik = UnitKerja::create(['nama' => 'Tim Teknik']);

        // 2. Admin
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // 3. Kepala Stasiun
        $kepala = User::create([
            'name' => 'Budi Santoso',
            'email' => 'kepala@test.com',
            'password' => bcrypt('password'),
            'role' => 'kepala_stasiun',
            'signature_image_path' => null, // Upload manual
        ]);

        // 4. Kasubbag Tata Usaha
        $tataUsaha = User::create([
            'name' => 'Siti Nurhaliza',
            'email' => 'tata.usaha@test.com',
            'password' => bcrypt('password'),
            'role' => 'tata_usaha',
        ]);

        // 5. Ketua Tim SDM
        $ketuaSDM = User::create([
            'name' => 'Ahmad Ketua SDM',
            'email' => 'ketua.sdm@test.com',
            'password' => bcrypt('password'),
            'role' => 'ketua_tim',
            'unit_kerja_id' => $unitSDM->id,
        ]);
        $unitSDM->update(['ketua_user_id' => $ketuaSDM->id]);

        // 6. Ketua Tim Berita
        $ketuaBerita = User::create([
            'name' => 'Dewi Ketua Berita',
            'email' => 'ketua.berita@test.com',
            'password' => bcrypt('password'),
            'role' => 'ketua_tim',
            'unit_kerja_id' => $unitBerita->id,
        ]);
        $unitBerita->update(['ketua_user_id' => $ketuaBerita->id]);

        // 7. Pegawai SDM
        $userPegawaiSDM = User::create([
            'name' => 'Andi Pegawai SDM',
            'email' => 'pegawai.sdm@test.com',
            'password' => bcrypt('password'),
            'role' => 'pegawai',
            'unit_kerja_id' => $unitSDM->id,
        ]);

        Employee::create([
            'user_id' => $userPegawaiSDM->id,
            'nama' => 'Andi Pegawai SDM',
            'email' => 'pegawai.sdm@test.com',
            'NIP' => '199001012020011001',
            'jabatan' => 'Staff SDM',
            'unit_kerja_id' => $unitSDM->id,
            'tanggal_bergabung' => '2020-01-01',
            'sisa_cuti_tahunan' => 12,
            'telp' => '081234567890',
            'alamat_domisili' => 'Yogyakarta',
        ]);

        // 8. Pegawai Berita
        $userPegawaiBerita = User::create([
            'name' => 'Rina Pegawai Berita',
            'email' => 'pegawai.berita@test.com',
            'password' => bcrypt('password'),
            'role' => 'pegawai',
            'unit_kerja_id' => $unitBerita->id,
        ]);

        Employee::create([
            'user_id' => $userPegawaiBerita->id,
            'nama' => 'Rina Pegawai Berita',
            'email' => 'pegawai.berita@test.com',
            'NIP' => '199002022020012002',
            'jabatan' => 'Reporter',
            'unit_kerja_id' => $unitBerita->id,
            'tanggal_bergabung' => '2021-03-15',
            'sisa_cuti_tahunan' => 10,
            'telp' => '082345678901',
            'alamat_domisili' => 'Sleman',
        ]);
    }
}
