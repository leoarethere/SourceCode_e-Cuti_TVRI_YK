<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use App\Models\UnitKerja;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Unit Kerja Dulu (Karena User butuh Unit Kerja)
        $unitBerita = UnitKerja::create(['nama' => 'Tim Berita']);
        $unitTeknik = UnitKerja::create(['nama' => 'Tim Teknik']);

        // 2. User: ADMIN
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@tvri.go.id',
            'role' => 'admin',
            'password' => Hash::make('password'), // Password default: 'password'
        ]);

        // 3. User: KEPALA STASIUN (Approval Tahap 3)
        $kepala = User::create([
            'name' => 'Kepala Stasiun TVRI',
            'email' => 'kepala@tvri.go.id',
            'role' => 'kepala_stasiun',
            'password' => Hash::make('password'),
        ]);
        // Kepala Stasiun juga butuh data Employee untuk Tanda Tangan di PDF
        Employee::create([
            'user_id' => $kepala->id,
            'nama' => $kepala->name,
            'email' => $kepala->email,
            'NIP' => '197001012000121001',
            'jabatan' => 'Kepala Stasiun',
            'unit_kerja_id' => null, // Kepala tidak terikat unit spesifik
            'tanggal_bergabung' => '2000-01-01',
            'sisa_cuti_tahunan' => 12,
        ]);

        // 4. User: TATA USAHA (Approval Tahap 2)
        $tataUsaha = User::create([
            'name' => 'Ibu Tata Usaha',
            'email' => 'tu@tvri.go.id',
            'role' => 'tata_usaha',
            'password' => Hash::make('password'),
        ]);
        // TU butuh Employee agar nama/NIP tampil di PDF tanda tangan
        Employee::create([
            'user_id' => $tataUsaha->id,
            'nama' => $tataUsaha->name,
            'email' => $tataUsaha->email,
            'NIP' => '197505052000122001',
            'jabatan' => 'Kasubbag Tata Usaha',
            'unit_kerja_id' => null,
            'tanggal_bergabung' => '2000-01-01',
            'sisa_cuti_tahunan' => 12,
        ]);

        // 5. User: KETUA TIM BERITA (Approval Tahap 1 untuk Berita)
        $ketuaBerita = User::create([
            'name' => 'Ketua Tim Berita',
            'email' => 'ketuaberita@tvri.go.id',
            'role' => 'ketua_tim',
            'unit_kerja_id' => $unitBerita->id,
            'password' => Hash::make('password'),
        ]);
        // Set user ini sebagai ketua di tabel unit_kerjas
        $unitBerita->update(['ketua_user_id' => $ketuaBerita->id]);
        // Buat data Employee untuk Ketua
        Employee::create([
            'user_id' => $ketuaBerita->id,
            'unit_kerja_id' => $unitBerita->id,
            'nama' => $ketuaBerita->name,
            'email' => $ketuaBerita->email,
            'NIP' => '198001012010121001',
            'jabatan' => 'Produser Berita',
            'tanggal_bergabung' => '2010-01-01',
            'sisa_cuti_tahunan' => 12,
        ]);

        // 6. User: PEGAWAI BERITA (Yang Mengajukan Cuti)
        $pegawaiBerita = User::create([
            'name' => 'Budi Jurnalis',
            'email' => 'budi@tvri.go.id',
            'role' => 'pegawai',
            'unit_kerja_id' => $unitBerita->id,
            'password' => Hash::make('password'),
        ]);
        // Wajib buat Employee agar bisa masuk menu Pengajuan Cuti
        Employee::create([
            'user_id' => $pegawaiBerita->id,
            'unit_kerja_id' => $unitBerita->id, // Penting: Harus sama dengan unit ketuanya
            'nama' => $pegawaiBerita->name,
            'email' => $pegawaiBerita->email,
            'NIP' => '199001012020121001',
            'jabatan' => 'Reporter',
            'tanggal_bergabung' => '2020-05-20',
            'telp' => '081234567890',
            'alamat_domisili' => 'Jl. Magelang Km 5, Yogyakarta',
            'sisa_cuti_tahunan' => 12,
        ]);

        // 7. Tambahan: PEGAWAI TEKNIK (Untuk tes beda divisi)
        $pegawaiTeknik = User::create([
            'name' => 'Siti Kameramen',
            'email' => 'siti@tvri.go.id',
            'role' => 'pegawai',
            'unit_kerja_id' => $unitTeknik->id,
            'password' => Hash::make('password'),
        ]);
        Employee::create([
            'user_id' => $pegawaiTeknik->id,
            'unit_kerja_id' => $unitTeknik->id,
            'nama' => $pegawaiTeknik->name,
            'email' => $pegawaiTeknik->email,
            'NIP' => '199505052021012001',
            'jabatan' => 'Kameramen',
            'tanggal_bergabung' => '2021-01-10',
            'sisa_cuti_tahunan' => 12,
        ]);
    }
}