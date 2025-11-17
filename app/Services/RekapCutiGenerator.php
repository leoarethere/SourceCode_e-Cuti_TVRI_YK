<?php

namespace App\Services;

use TCPDF;
use Carbon\Carbon;
use App\Models\Cuti;
use Illuminate\Support\Collection;

/**
 * Class RekapCutiGenerator
 *
 * Menggunakan TCPDF untuk membuat file PDF rekap (tabel)
 * dari sekumpulan data cuti.
 */
class RekapCutiGenerator extends TCPDF
{
    protected Collection $records;

    /**
     * Fungsi utama untuk menghasilkan PDF
     */
    public function generate(Collection $records)
    {
        $this->records = $records;

        // --- Konfigurasi Halaman ---
        $this->SetAuthor('Sistem E-Cuti TVRI Yogyakarta');
        $this->SetTitle('Rekap Pengajuan Cuti');
        $this->SetSubject('Rekap Pengajuan Cuti');
        
        // Landscape (L) untuk tabel yang lebar
        // Margin 10mm (lebih kecil untuk memberi ruang lebih pada tabel)
        $this->SetMargins(10, 15, 10);
        $this->SetAutoPageBreak(true, 15);
        
        $this->setPrintHeader(false);
        $this->setPrintFooter(true);
        
        $this->AddPage('L', 'A4'); // Orientasi Landscape, Ukuran A4

        // --- Mulai Menggambar PDF ---
        $this->SetFont('times', '', 11);

        $this->drawTitle();
        $this->drawTable(); // Ini akan menggambar tabel data

        // --- Selesai & Output ---
        $fileName = 'rekap_cuti_' . date('Y-m-d_His') . '.pdf';
        
        $this->Output($fileName, 'I'); 
    }

    /**
     * Menggambar Judul Rekap
     */
    private function drawTitle()
    {
        $this->SetFont('times', 'B', 16);
        $this->Cell(0, 10, 'REKAP DATA PENGAJUAN CUTI', 0, 1, 'C');
        $this->SetFont('times', 'B', 12);
        $this->Cell(0, 8, 'LPP TVRI STASIUN YOGYAKARTA', 0, 1, 'C');
        $this->SetFont('times', 'I', 10);
        $this->Cell(0, 7, 'Periode Cetak: ' . Carbon::now()->isoFormat('D MMMM Y'), 0, 1, 'C');
        $this->Ln(5);
    }

    /**
     * Menggambar tabel data menggunakan MultiCell() - Paling Presisi
     */
    private function drawTable()
    {
        // === KONFIGURASI TABEL ===
        $this->SetFont('times', '', 8); // Font data lebih kecil
        $this->SetFillColor(255, 255, 255); // Putih
        $this->SetTextColor(0, 0, 0); // Hitam
        $this->SetDrawColor(0, 0, 0); // Border hitam
        $this->SetLineWidth(0.1); // Border tipis

        // Lebar kolom dalam mm (Total 277mm)
        $w = [8, 30, 40, 30, 30, 20, 20, 15, 42, 42]; // Kolom 'Status' diperlebar
        
        // Header tabel
        $header = ['No', 'NIP', 'Nama Pegawai', 'Unit Kerja', 'Jenis Cuti', 
                   'Tgl Mulai', 'Tgl Akhir', 'Lama (Hari)', 'Alasan', 'Status'];

        // === CETAK HEADER ===
        $this->SetFont('times', 'B', 8); // Font header bold
        $this->SetFillColor(230, 230, 230); // Latar abu-abu
        $num_headers = count($header);
        for ($i = 0; $i < $num_headers; ++$i) {
            // Gunakan MultiCell untuk header agar teksnya juga wrap
            $this->MultiCell($w[$i], 10, $header[$i], 1, 'C', true, 0, '', '', true, 0, false, true, 10, 'M');
        }
        $this->Ln(); // Baris baru setelah header

        // === CETAK DATA ===
        $this->SetFont('times', '', 8); // Font data normal
        
        $no = 1;
        foreach ($this->records as $cuti) {
            // Hitung lama cuti
            $lamaCuti = Carbon::parse($cuti->tanggal_mulai)
                            ->diffInDays(Carbon::parse($cuti->tanggal_akhir)) + 1;

            // Siapkan data untuk baris ini
            $data = [
                $no++,
                e($cuti->employee->NIP ?? '-'),
                e($cuti->employee->nama ?? '-'),
                e($cuti->employee->unit_kerja ?? '-'),
                e($cuti->jenis_cuti),
                Carbon::parse($cuti->tanggal_mulai)->format('d/m/Y'),
                Carbon::parse($cuti->tanggal_akhir)->format('d/m/Y'),
                $lamaCuti,
                e($cuti->alasan),
                e($cuti->status_global)
            ];
            
            // Perataan untuk setiap kolom
            $align = ['C', 'L', 'L', 'L', 'L', 'C', 'C', 'C', 'L', 'L'];

            // --- Logika Inti: MultiCell Dinamis ---
            // 1. Dapatkan tinggi baris maksimum
            $maxHeight = $this->calculateRowHeight($data, $w);

            // 2. Gambar setiap sel dengan tinggi maksimum
            for ($i = 0; $i < count($data); ++$i) {
                $this->MultiCell(
                    $w[$i],     // Lebar
                    $maxHeight, // Tinggi (dinamis)
                    $data[$i],  // Teks
                    1,          // Border
                    $align[$i], // Perataan
                    false,      // Latar (fill)
                    0,          // Pindah (0 = ke kanan)
                    '', '', true, 0, false, true, $maxHeight, 'M' // Penting: atur valign ke Middle
                );
            }
            $this->Ln(); // Baris baru di akhir
            
            // Cek page break (jika Y melebihi 180mm)
            if ($this->GetY() > 180) {
                $this->AddPage('L', 'A4');
                // Gambar header lagi
                $this->SetFont('times', 'B', 8);
                $this->SetFillColor(230, 230, 230);
                for ($i = 0; $i < $num_headers; ++$i) {
                    $this->MultiCell($w[$i], 10, $header[$i], 1, 'C', true, 0, '', '', true, 0, false, true, 10, 'M');
                }
                $this->Ln();
                $this->SetFont('times', '', 8);
            }
        }
    }

    /**
     * Fungsi helper untuk menghitung tinggi baris
     * berdasarkan konten terpanjang
     */
    private function calculateRowHeight($data, $widths)
    {
        $maxHeight = 6; // Tinggi minimal sel (6mm)

        for ($i = 0; $i < count($data); $i++) {
            // getStringHeight() menghitung tinggi yang dibutuhkan teks
            // (lebar sel dikurangi 2mm untuk padding)
            $height = $this->getStringHeight($widths[$i] - 2, $data[$i]); 
            
            if ($height > $maxHeight) {
                $maxHeight = $height;
            }
        }
        
        // Beri sedikit padding ekstra 2mm jika tingginya lebih dari standar
        return $maxHeight > 6 ? $maxHeight + 2 : 6; 
    }

    /**
     * Override Footer() default untuk nomor halaman
     */
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('times', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->getAliasNumPage() . ' dari ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}