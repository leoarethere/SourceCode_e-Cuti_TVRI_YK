<?php

namespace Database\Seeders;

use App\Models\Kriteria;
use Illuminate\Database\Seeder;

class KriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $kriteria = [
            ['kode' => 'C1', 'nama_kriteria' => 'Sisa Kuota Cuti', 'tipe' => 'benefit', 'bobot' => 0.30],
            ['kode' => 'C2', 'nama_kriteria' => 'Kinerja Karyawan', 'tipe' => 'benefit', 'bobot' => 0.25],
            ['kode' => 'C3', 'nama_kriteria' => 'Masa Kerja', 'tipe' => 'benefit', 'bobot' => 0.15],
            ['kode' => 'C4', 'nama_kriteria' => 'Urgensi Alasan Cuti', 'tipe' => 'benefit', 'bobot' => 0.20],
            ['kode' => 'C5', 'nama_kriteria' => 'Beban Kerja Divisi', 'tipe' => 'cost', 'bobot' => 0.10],
        ];

        foreach ($kriteria as $item) {
            Kriteria::updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
