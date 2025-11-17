<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Form Cuti - {{ $cuti->employee->nama ?? 'Karyawan' }}</title>
    <style>
        /* CSS ini disederhanakan AGAR KOMPATIBEL DENGAN DOMPDF */
        @page {
            size: A4;
            margin: 25mm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
        }

        /* Hilangkan semua styling @media screen/print */

        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        /* Styling untuk Header Kop Surat */
        .header-table td {
            vertical-align: top;
        }
        .logo {
            height: 70px; /* Perkecil sedikit */
            width: auto;
        }
        .company-info {
            text-align: center;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
        }
        .company-address {
            font-size: 10pt;
        }
        .company-contact {
            font-size: 9pt;
            font-style: italic;
        }

        .document-title {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
        }

        /* Tabel Data Utama */
        .master-table {
            border: 1px solid #000;
            margin-bottom: 15px;
        }
        .master-table th, .master-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }
        .header-col {
            width: 30%;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .data-col {
            width: 70%;
        }

        /* Tabel data di dalam kolom kanan (untuk styling rapi) */
        .data-table {
            width: 100%;
        }
        .data-table td {
            border: none; /* Hilangkan border tabel dalam */
            padding: 4px 0; /* Padding vertikal kecil */
        }
        .data-table .label {
            width: 40%;
            font-weight: bold;
        }
        .data-table .value {
            width: 60%;
        }

        .content-box {
            padding: 8px;
            min-height: 60px;
            font-size: 11pt;
            line-height: 1.5;
        }

        /* Tanda Tangan (menggunakan tabel, bukan float) */
        .signature-table {
            margin-top: 30px;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 15px;
        }
        .signature-space {
            height: 60px; /* Ruang untuk TTD */
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
        }
        .signature-name {
            font-weight: bold;
        }
        .signature-title {
            font-size: 10pt;
            font-style: italic;
        }

        .footer {
            margin-top: 25px;
            font-size: 9pt;
            text-align: center;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }

    </style>
</head>
<body>
    
    <table class-="header-table" style="border: none; margin-bottom: 15px; border-bottom: 3px double #000; padding-bottom: 15px;">
        <tr>
            <td style="width: 20%; text-align: left;">
                <img src="{{ public_path('images/logo-tvri.png') }}" alt="Logo TVRI" class="logo">
            </td>
            
            <td style="width: 60%;" class="company-info">
                <div class="company-name">TELEVISI REPUBLIK INDONESIA (TVRI)</div>
                <div class="company-name" style="font-size: 14pt;"><strong>KANTOR WILAYAH TVRI JOGJA</strong></div>
                <div class="company-address">Jl. Magelang Km. 5, Kutu Dukuh, Sinduadi, Kec. Mlati, Kabupaten Sleman</div>
                <div class="company-address">Daerah Istimewa Yogyakarta 55284</div>
                <div class="company-contact">Telp: (0274) 623222 | Email: kanwiljogja@tvri.go.id | Website: www.tvri.go.id</div>
            </td>

            <td style="width: 20%; text-align: right;">
                </td>
        </tr>
    </table>


    <div class="document-title">
        SURAT PERMOHONAN CUTI KARYAWAN
    </div>

    <table style="width: 100%; margin-bottom: 15px; font-size: 11pt;">
        <tr>
            <td width="20%"><strong>Nomor Surat</strong></td>
            <td>: SC/{{ date('Y') }}/{{ isset($cuti->id) ? str_pad($cuti->id, 4, '0', STR_PAD_LEFT) : '0000' }}</td>
        </tr>
        <tr>
            <td><strong>Tanggal Pengajuan</strong></td>
            <td>: {{ \Carbon\Carbon::parse($cuti->created_at)->isoFormat('D MMMM Y') }}</td>
        </tr>
    </table>

    <table class="master-table">
        <tr>
            <td class="header-col">DATA PEGAWAI</td>
            <td class="data-col">
                <table class="data-table">
                    <tr>
                        <td class="label">Nama Lengkap</td>
                        <td class="value">{{ $cuti->employee->nama ?? 'Data tidak tersedia' }}</td>
                    </tr>
                    <tr>
                        <td class="label">NIP</td>
                        <td class="value">{{ $cuti->employee->NIP ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jabatan</td>
                        <td class="value">{{ $cuti->employee->jabatan ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Unit Kerja</td>
                        <td class="value">{{ $cuti->employee->unit_kerja ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Nomor Telepon</td>
                        <td class="value">{{ $cuti->employee->telp ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td class="header-col">DATA PENGAJUAN CUTI</td>
            <td class="data-col">
                <table class="data-table">
                    <tr>
                        <td class="label">Jenis Cuti</td>
                        <td class="value">{{ $cuti->jenis_cuti ?? 'Cuti Tahunan' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Mulai</td>
                        <td class="value">
                            @if(isset($cuti->tanggal_mulai))
                                {{ \Carbon\Carbon::parse($cuti->tanggal_mulai)->isoFormat('D MMMM Y') }}
                            @else - @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Akhir</td>
                        <td class="value">
                            @if(isset($cuti->tanggal_akhir))
                                {{ \Carbon\Carbon::parse($cuti->tanggal_akhir)->isoFormat('D MMMM Y') }}
                            @else - @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Lama Cuti</td>
                        <td class="value">
                            @if(isset($lamaCuti))
                                {{ $lamaCuti }} Hari
                            @else - @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td class="header-col">ALASAN CUTI</td>
            <td class="data-col">
                <div class="content-box">
                    {!! nl2br(e($cuti->alasan ?? 'Alasan tidak tersedia')) !!}
                </div>
            </td>
        </tr>

        <tr>
            <td class="header-col">INFORMASI TAMBAHAN</td>
            <td class="data-col">
                <table class="data-table">
                    <tr>
                        <td class="label">Sisa Cuti Tahunan</td>
                        <td class="value">{{ $cuti->employee->sisa_cuti_tahunan ?? '0' }} hari</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Masuk Kembali</td>
                        <td class="value">
                            @if(isset($cuti->tanggal_akhir))
                                @php
                                    $returnDate = \Carbon\Carbon::parse($cuti->tanggal_akhir)->addDay();
                                @endphp
                                {{ $returnDate->isoFormat('D MMMM Y') }}
                            @else - @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Alamat Selama Cuti</td>
                        <td class="value">{{ $cuti->alamat_selama_cuti ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>

    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-name">{{ $cuti->employee->nama ?? '________________' }}</div>
                <div class="signature-title">Pemohon</div>
                <div class="signature-space"></div> </td>
            <td>
                <div class="signature-name">[Nama Ketua Tim SDM]</div>
                <div class="signature-title">Ketua Tim SDM</div>
                <div class="signature-space"></div> </td>
        </tr>
        <tr>
            <td style="padding-top: 25px;">
                <div class="signature-name">[Nama Kasubbag Tata Usaha]</div>
                <div class="signature-title">Kasubbag Tata Usaha</div>
                <div class="signature-space"></div> </td>
            <td style="padding-top: 25px;">
                <div class="signature-name">[Nama Kepala Stasiun]</div>
                <div class="signature-title">Kepala Stasiun</div>
                <div class="signature-space"></div> </td>
        </tr>
    </table>

    <div class="footer">
        Dokumen ini dicetak secara elektronik dari Sistem E-Cuti TVRI Yogyakarta<br>
        Tanggal cetak: {{ date('d/m/Y H:i:s') }}
    </div>

</body>
</html>