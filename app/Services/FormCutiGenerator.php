<?php

namespace App\Services;

use TCPDF;
use Carbon\Carbon;
use App\Models\Cuti;

/**
 * Class FormCutiGenerator
 *
 * Menggunakan TCPDF untuk "menggambar" PDF formulir cuti individual
 * dengan tampilan yang lebih rapi dan profesional
 */
class FormCutiGenerator extends TCPDF
{
    protected Cuti $cuti;
    protected $masaKerja;
    protected $lamaCuti;

    /**
     * Fungsi utama untuk menghasilkan PDF
     */
    public function generate(Cuti $cuti)
    {
        // Eager load semua relasi yang kita butuhkan
        $this->cuti = $cuti->load('employee', 'kepalaApprover.employee');

        // Hitung data tambahan
        $this->calculateData();

        // --- Konfigurasi Halaman ---
        $this->SetAuthor('Sistem E-Cuti TVRI Yogyakarta');
        $this->SetTitle('Form Cuti - ' . $this->cuti->employee->nama);
        $this->SetSubject('Formulir Pengajuan Cuti');
        
        $this->SetMargins(20, 20, 20); // Margin lebih proporsional
        $this->SetAutoPageBreak(true, 20);
        
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        
        $this->AddPage('P', 'A4');

        // --- Mulai Menggambar PDF ---
        $this->SetFont('helvetica', '', 10);

        $this->drawHeader();
        $this->drawTable();

        // --- Selesai & Output ---
        $fileName = 'form_cuti_' . str_replace(' ', '_', $this->cuti->employee->nama) . '_' . date('Ymd') . '.pdf';
        
        $this->Output($fileName, 'I'); 
    }

    /**
     * Menghitung data dinamis (Masa Kerja & Lama Cuti)
     */
    private function calculateData()
    {
        // Hitung masa kerja dengan format yang lebih baik
        if ($this->cuti->employee->tanggal_bergabung) {
            $tanggalBergabung = Carbon::parse($this->cuti->employee->tanggal_bergabung);
            $years = $tanggalBergabung->diffInYears(now());
            $months = $tanggalBergabung->copy()->addYears($years)->diffInMonths(now());
            
            if ($years > 0) {
                $this->masaKerja = $years . ' Tahun' . ($months > 0 ? ' ' . $months . ' Bulan' : '');
            } else {
                $this->masaKerja = $months . ' Bulan';
            }
        } else {
            $this->masaKerja = '-';
        }
            
        // Hitung lama cuti
        $this->lamaCuti = Carbon::parse($this->cuti->tanggal_mulai)
                            ->diffInDays(Carbon::parse($this->cuti->tanggal_akhir)) + 1;
    }

    /**
     * Menggambar Header dengan Logo dan Kop Surat
     */
    private function drawHeader()
    {
        $this->SetFont('helvetica', 'B', 16);
        
        // Logo TVRI (jika ada)
        // $logoPath = public_path('images/logo-tvri.png');
        // if (file_exists($logoPath)) {
        //     $this->Image($logoPath, 20, 15, 30, 0, '', '', '', false, 300, '', false, false, 0);
        // }
        
        // Kop Surat
        $this->SetXY(20, 20);
        $this->Cell(0, 8, 'LEMBAGA PENYIARAN PUBLIK TELEVISI REPUBLIK INDONESIA', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 8, 'STASIUN YOGYAKARTA', 0, 1, 'C');
        
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 5, 'Jl. Magelang Km. 4,5 Yogyakarta 55284 | Kontak: (0274) 514402, 514403, 514909 | Email: yogyakarta@tvri.go.id', 0, 1, 'C');
        
        // Garis pemisah
        $this->SetLineWidth(0.8);
        $this->Line(20, $this->GetY() + 3, 190, $this->GetY() + 3);
        $this->SetLineWidth(0.2);
        $this->Line(20, $this->GetY() + 4, 190, $this->GetY() + 4);
        
        $this->Ln(10);
        
        // Judul Formulir
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 8, 'FORMULIR PERMINTAAN DAN PEMBERIAN CUTI', 0, 1, 'C');
        $this->Ln(5);
    }

    private function drawTable()
    {
        $this->SetFont('helvetica', '', 10);

        // --- Persiapan Data Kepala Stasiun ---
        $kepala = $this->cuti->kepalaApprover;
        $signaturePath = $kepala ? $kepala->signature_image_path : null;
        $kepalaNama = '[Nama Kepala Stasiun]';
        $kepalaNip = '[NIP Kepala Stasiun]';

        // === PERBAIKAN 1: Ambil Nama Unit Kerja dari Relasi ===
        $unitKerja = $this->cuti->employee->unitKerja; // Ambil relasi
        $namaUnit = $unitKerja ? $unitKerja->nama : '-'; // Ambil nama atau strip
        // ======================================================

        if ($kepala && $kepala->employee) {
            $kepalaNama = $kepala->employee->nama;
            $kepalaNip = $kepala->employee->NIP;
        }

        // --- HTML Tanda Tangan ---
        $signatureHtml = '<div style="color: #999; font-style: italic; font-size: 9pt;">TTD Digital</div>';
        if ($this->cuti->status_kepala == 'approved' && $signaturePath && file_exists(storage_path('app/public/' . $signaturePath))) {
            $signatureHtml = '<img 
                                src="' . storage_path('app/public/' . $signaturePath) . '" 
                                style="height: 30mm; width: auto; max-height: 30mm;"
                             >';
        }

        // Mapping jenis cuti
        $jenisMap = [
            'Cuti Tahunan' => 'Cuti Tahunan',
            'Cuti Sakit' => 'Cuti Sakit',
            'Cuti Besar' => 'Cuti Besar',
            'Cuti Melahirkan' => 'Cuti Melahirkan',
            'Cuti Karena Alasan Penting' => 'Cuti Karena Alasan Penting'
        ];
        
        $jenisCuti = $jenisMap[$this->cuti->jenis_cuti] ?? $this->cuti->jenis_cuti;

        // Status styling
        $statusLabels = [
            'pending' => 'MENUNGGU',
            'approved' => 'DISETUJUI',
            'rejected' => 'DITOLAK'
        ];
        
        // Hitung tanggal kembali
        $returnDateHtml = '-';
        if (isset($this->cuti->tanggal_akhir)) {
            $returnDate = \Carbon\Carbon::parse($this->cuti->tanggal_akhir)->addDay();
            $returnDateHtml = $returnDate->isoFormat('D MMMM Y');
        }

        // Hitung lama cuti
        $lamaCutiHtml = '-';
        if (isset($this->cuti->tanggal_mulai) && isset($this->cuti->tanggal_akhir)) {
            $start = \Carbon\Carbon::parse($this->cuti->tanggal_mulai);
            $end = \Carbon\Carbon::parse($this->cuti->tanggal_akhir);
            $days = $start->diffInDays($end) + 1;
            $lamaCutiHtml = $days . ' Hari';
        }

        // === PERBAIKAN 2: Menggunakan 'status_atasan_langsung' ===
        // Kita ambil status dari kolom baru, bukan 'status_sdm' lagi
        $statusAtasan = $this->cuti->status_atasan_langsung; 
        $tanggapanAtasan = $this->cuti->tanggapan_atasan_langsung;
        // =========================================================


        $html = '
        <style>
            .form-table { border-collapse: collapse; width: 100%; font-family: helvetica; }
            .form-table td { padding: 8px 10px; vertical-align: top; line-height: 1.6; }
            .section-header {
                background: linear-gradient(to right, #1e3a8a, #3b82f6);
                color: white; font-weight: bold; font-size: 11pt; padding: 10px !important;
                text-transform: uppercase; letter-spacing: 0.5px;
            }
            .label-cell { width: 35%; color: #374151; font-weight: 600; border-right: 1px solid #e5e7eb; }
            .value-cell { width: 65%; color: #1f2937; background-color: #f9fafb; }
            .row-separator { border-bottom: 1px solid #e5e7eb; }
            .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 9pt; }
            .status-approved { background-color: #d1fae5; color: #065f46; }
            .status-pending { background-color: #fef3c7; color: #92400e; }
            .status-rejected { background-color: #fee2e2; color: #991b1b; }
            .signature-section { margin-top: 20px; padding: 15px; background-color: #ffffff; border: none; }
            .signature-box { text-align: center; margin: 15px auto; min-height: 40px; }
            .footer-text {
                margin-top: 30px; padding-top: 15px; border-top: 2px solid #e5e7eb;
                text-align: center; font-size: 8pt; color: #6b7280; line-height: 1.8;
            }
            .important-note {
                background-color: #fef9c3; border-left: 4px solid #eab308; padding: 10px;
                margin: 15px 0; font-size: 9pt; color: #854d0e;
            }
        </style>

        <table class="form-table" cellpadding="0" cellspacing="0">
            <tr>
                <td colspan="2" class="section-header"><span style="margin-right: 8px;">📋</span> I. DATA PEGAWAI</td>
            </tr>
            <tr class="row-separator"><td class="label-cell">Nama Lengkap</td><td class="value-cell"><strong>'.e($this->cuti->employee->nama).'</strong></td></tr>
            <tr class="row-separator"><td class="label-cell">Nomor Induk Pegawai (NIP)</td><td class="value-cell">'.e($this->cuti->employee->NIP).'</td></tr>
            <tr class="row-separator"><td class="label-cell">Jabatan</td><td class="value-cell">'.e($this->cuti->employee->jabatan).'</td></tr>
            
            <tr class="row-separator"><td class="label-cell">Unit Kerja</td><td class="value-cell">'.e($namaUnit).'</td></tr>
            
            <tr class="row-separator"><td class="label-cell">Nomor Telepon</td><td class="value-cell">'.e($this->cuti->employee->telp ?? '-').'</td></tr>
            <tr class="row-separator"><td class="label-cell">Masa Kerja</td><td class="value-cell">'.e($this->masaKerja).'</td></tr>
            <tr><td class="label-cell">Sisa Cuti Tahunan</td><td class="value-cell"><strong style="color: #1e40af;">'.e($this->cuti->employee->sisa_cuti_tahunan).' Hari</strong></td></tr>

            <tr>
                <td colspan="2" class="section-header"><span style="margin-right: 8px;">📅</span> II. DATA PENGAJUAN CUTI</td>
            </tr>
            <tr class="row-separator"><td class="label-cell">Tanggal Pengajuan</td><td class="value-cell">'.Carbon::parse($this->cuti->created_at)->isoFormat('dddd, D MMMM Y').'</td></tr>
            <tr class="row-separator"><td class="label-cell">Jenis Cuti</td><td class="value-cell"><strong>'.e($jenisCuti).'</strong></td></tr>
            <tr class="row-separator"><td class="label-cell">Alasan Cuti</td><td class="value-cell">'.e($this->cuti->alasan).'</td></tr>
            <tr class="row-separator"><td class="label-cell">Periode Cuti</td><td class="value-cell"><strong>'.Carbon::parse($this->cuti->tanggal_mulai)->isoFormat('D MMMM Y').'</strong> s/d <strong>'.Carbon::parse($this->cuti->tanggal_akhir)->isoFormat('D MMMM Y').'</strong></td></tr>
            <tr class="row-separator"><td class="label-cell">Jumlah Hari Cuti</td><td class="value-cell"><strong style="color: #1e40af;">'.e($lamaCutiHtml).'</strong></td></tr>
            <tr><td class="label-cell">Alamat Selama Cuti</td><td class="value-cell">'.nl2br(e($this->cuti->alamat_selama_cuti)).'</td></tr>

            <tr>
                <td colspan="2" class="section-header"><span style="margin-right: 8px;">✓</span> III. STATUS PERSETUJUAN</td>
            </tr>
            
            <tr class="row-separator">
                <td class="label-cell">Ketua Tim '.e($namaUnit).'</td>
                <td class="value-cell">
                    <span class="status-badge status-'.strtolower($statusAtasan).'">'.e($statusLabels[$statusAtasan] ?? strtoupper($statusAtasan)).'</span>
                    '.($tanggapanAtasan ? '<br><em style="color: #6b7280; font-size: 9pt;">Catatan: '.e($tanggapanAtasan).'</em>' : '').'
                </td>
            </tr>

            <tr class="row-separator">
                <td class="label-cell">Kasubbag Tata Usaha</td>
                <td class="value-cell">
                    <span class="status-badge status-'.strtolower($this->cuti->status_tata_usaha).'">'.e($statusLabels[$this->cuti->status_tata_usaha] ?? strtoupper($this->cuti->status_tata_usaha)).'</span>
                    '.($this->cuti->tanggapan_tata_usaha ? '<br><em style="color: #6b7280; font-size: 9pt;">Catatan: '.e($this->cuti->tanggapan_tata_usaha).'</em>' : '').'
                </td>
            </tr>
            <tr>
                <td class="label-cell">Kepala Stasiun</td>
                <td class="value-cell">
                    <span class="status-badge status-'.strtolower($this->cuti->status_kepala).'">'.e($statusLabels[$this->cuti->status_kepala] ?? strtoupper($this->cuti->status_kepala)).'</span>
                    '.($this->cuti->tanggapan_kepala ? '<br><em style="color: #6b7280; font-size: 9pt;">Catatan: '.e($this->cuti->tanggapan_kepala).'</em>' : '').'
                </td>
            </tr>
        </table>

        <div class="signature-section">
            <table width="100%">
                <tr>
                    <td width="50%"></td> <td width="50%" style="text-align: center;">
                        <div style="margin-bottom: 5px; color: #374151;">Yogyakarta, '.Carbon::now()->isoFormat('D MMMM Y').'</div>
                        <div style="margin-bottom: 3px; font-weight: bold;">Mengetahui dan Menyetujui,</div>
                        <div style="margin-bottom: 10px; color: #6b7280; font-size: 9pt;">Kepala Stasiun TVRI Yogyakarta</div>
                        <div class="signature-box">'.$signatureHtml.'</div>
                        <div style="font-weight: bold;">'.e($kepalaNama).'</div>
                        <div style="border-bottom: 1px solid #000; width: 30mm; margin: 0 auto;"></div>
                        <div style="margin-top: 3px; font-size: 9pt;">NIP. '.e($kepalaNip).'</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="important-note">
            <strong>Catatan Penting:</strong> Dokumen ini merupakan salinan resmi yang dicetak dari Sistem E-Cuti TVRI Yogyakarta. 
            Pegawai yang bersangkutan wajib menyerahkan dokumen ini kepada atasan langsung sebelum menjalankan cuti.
        </div>

        <div class="footer-text">
            <em>Dokumen dicetak secara elektronik pada '.Carbon::now()->isoFormat('dddd, D MMMM Y [pukul] HH:mm').' WIB</em><br>
            <span style="font-size: 7pt; color: #9ca3af;">Sistem E-Cuti TVRI Yogyakarta v1.0</span>
        </div>
        ';

        $this->writeHTML($html, true, false, true, false, '');
    }
}