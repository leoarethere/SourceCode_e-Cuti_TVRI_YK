<?php

namespace App\Services;

use TCPDF;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class RekapCutiGenerator
 * Update: Menggunakan font Helvetica agar seragam dengan Form Cuti
 */
class RekapCutiGenerator extends TCPDF
{
    protected Collection $records;

    public function generate(Collection $records)
    {
        $this->records = $records;

        // --- Konfigurasi Halaman ---
        $this->SetAuthor('Sistem E-Cuti TVRI Yogyakarta');
        $this->SetTitle('Rekap Pengajuan Cuti');
        $this->SetSubject('Rekap Data Cuti');
        
        // Margin: Kiri, Atas, Kanan (10mm)
        $this->SetMargins(10, 15, 10);
        $this->SetAutoPageBreak(true, 15);
        
        $this->setPrintHeader(false);
        $this->setPrintFooter(true);
        
        $this->AddPage('L', 'A4'); // Landscape

        // --- Mulai Menggambar ---
        // GANTI FONT DISINI
        $this->SetFont('helvetica', '', 10); 
        
        $this->drawTitle();
        $this->drawTable(); 

        // --- Output ---
        $fileName = 'rekap_cuti_' . date('Ymd_His') . '.pdf';
        $this->Output($fileName, 'I'); 
    }

    /**
     * Menggambar Judul Rekap
     */
    private function drawTitle()
    {
        // GANTI FONT JUDUL
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'REKAP DATA PENGAJUAN CUTI', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 8, 'LEMBAGA PENYIARAN PUBLIK TELEVISI REPUBLIK INDONESIA STASIUN YOGYAKARTA', 0, 1, 'C');
        
        $this->SetFont('helvetica', 'I', 10);
        $this->Cell(0, 7, 'Periode Cetak: ' . Carbon::now()->isoFormat('D MMMM Y'), 0, 1, 'C');
        $this->Ln(5);
    }

    /**
     * Menggambar Header Tabel
     */
    private function drawTableHeader($header, $w)
    {
        // GANTI FONT HEADER TABEL
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(220, 220, 220); // Abu-abu
        
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
    }

    /**
     * Menggambar Tabel Data
     */
    private function drawTable()
    {
        // GANTI FONT ISI TABEL
        $this->SetFont('helvetica', '', 9);
        $this->setCellPaddings(1.5, 1.5, 1.5, 1.5);

        // Lebar kolom (sama seperti revisi terakhir)
        $w = [
            10, // No
            40, // NIP
            40, // Nama
            30, // Unit
            25, // Jenis
            55, // Detail Tanggal
            12, // Hari
            45, // Alasan
            20  // Status
        ];
        
        $header = ['No', 'NIP', 'Nama Pegawai', 'Unit Kerja', 'Jenis Cuti', 
                   'Detail Tanggal Cuti', 'Hari', 'Alasan', 'Status'];

        $this->drawTableHeader($header, $w);

        // Reset Font untuk Data
        $this->SetFont('helvetica', '', 9);
        $this->SetFillColor(255, 255, 255);
        
        $no = 1;
        foreach ($this->records as $cuti) {
            if (!is_object($cuti)) continue;
            
            // --- Logika Data (Sama seperti sebelumnya) ---
            $employee = isset($cuti->employee) ? $cuti->employee : null;
            $nip = $employee && isset($employee->NIP) ? $employee->NIP : '-';
            $namaPegawai = $employee && isset($employee->nama) ? $employee->nama : '-';
            
            if ($employee && isset($employee->unitKerja) && isset($employee->unitKerja->nama)) {
                $unitKerjaNama = $employee->unitKerja->nama;
            } else {
                $unitKerjaNama = '-';
            }

            $lamaCuti = (int) $cuti->lama_cuti;
            if ($lamaCuti <= 0 && isset($cuti->tanggal_mulai) && isset($cuti->tanggal_akhir)) {
                $lamaCuti = Carbon::parse($cuti->tanggal_mulai)
                            ->diffInDays(Carbon::parse($cuti->tanggal_akhir)) + 1;
            }

            $detailTanggal = '-';
            if (!empty($cuti->tanggal_cuti_array) && is_array($cuti->tanggal_cuti_array)) {
                $dates = collect($cuti->tanggal_cuti_array)
                    ->map(fn($d) => Carbon::parse($d))
                    ->sort();
                
                $grouped = $dates->groupBy(fn($d) => $d->format('Y-m'));

                $detailTanggal = $grouped->map(function ($datesInMonth) {
                    $monthYear = $datesInMonth->first()->isoFormat('MMMM Y');
                    $days = $datesInMonth->map(fn($d) => $d->format('d'))->join(', ');
                    return "$days $monthYear"; 
                })->join("\n"); 

            } elseif ($cuti->tanggal_mulai && $cuti->tanggal_akhir) {
                $start = Carbon::parse($cuti->tanggal_mulai);
                $end = Carbon::parse($cuti->tanggal_akhir);
                
                if ($start->isSameMonth($end)) {
                     $detailTanggal = $start->format('d') . ' - ' . $end->format('d') . ' ' . $end->isoFormat('MMMM Y');
                } else {
                     $detailTanggal = $start->isoFormat('d MMM') . ' - ' . $end->isoFormat('d MMM Y');
                }
            }

            $jenisCuti = isset($cuti->jenis_cuti) ? $cuti->jenis_cuti : '-';
            $alasan = isset($cuti->alasan) ? $cuti->alasan : '-';
            $statusGlobal = isset($cuti->status_global) ? $cuti->status_global : '-';
            
            $data = [
                (string) $no++,
                (string) $nip,
                (string) $namaPegawai,
                (string) $unitKerjaNama,
                (string) $jenisCuti,
                (string) $detailTanggal,
                (string) $lamaCuti . ' Hari',
                (string) $alasan,
                (string) $statusGlobal
            ];
            
            $align = ['C', 'L', 'L', 'L', 'L', 'L', 'C', 'L', 'L'];

            $maxHeight = $this->calculateRowHeight($data, $w);

            if ($this->GetY() + $maxHeight > ($this->getPageHeight() - $this->getBreakMargin())) {
                $this->AddPage('L', 'A4');
                $this->drawTableHeader($header, $w);
                // Pastikan font di-reset setelah page break
                $this->SetFont('helvetica', '', 9);
            }

            $x = $this->GetX();
            $y = $this->GetY();
            
            for ($i = 0; $i < count($data); ++$i) {
                $this->SetXY($x, $y);
                $this->MultiCell(
                    $w[$i], $maxHeight, $data[$i], 1, $align[$i], 
                    false, 0, '', '', true, 0, false, true, $maxHeight, 'M'
                );
                $x += $w[$i];
            }
            $this->Ln();
        }
    }

    private function calculateRowHeight($data, $widths)
    {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $text = (string) $data[$i];
            $nb = max($nb, $this->getNumLines($text, $widths[$i]));
        }
        return max(5 * $nb + 2, 8); 
    }

    public function Footer() {
        $this->SetY(-15);
        // GANTI FONT FOOTER
        $this->SetFont('helvetica', 'I', 8);
        $this->Line(10, $this->GetY(), 287, $this->GetY());
        $this->Ln(2);
        $this->Cell(0, 10, 'Halaman ' . $this->getAliasNumPage() . ' dari ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}