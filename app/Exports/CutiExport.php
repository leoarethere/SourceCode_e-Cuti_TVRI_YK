<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Cuti;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CutiExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithColumnWidths,
    WithEvents
{
    protected Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records;
    }

    /**
     * PERBAIKAN: Menghapus baris kosong [] sebelum header tabel
     */
    public function headings(): array
    {
        return [
            ['REKAP DATA PENGAJUAN CUTI'],
            ['LEMBAGA PENYIARAN PUBLIK TELEVISI REPUBLIK INDONESIA'],
            ['STASIUN YOGYAKARTA'],
            ['Periode: ' . Carbon::now()->isoFormat('D MMMM Y')],
            // [], <--- HAPUS BARIS INI (Penyebab tabel kosong)
            [
                'No',
                'NIP',
                'Nama Pegawai',
                'Jabatan',
                'Unit Kerja',
                'Jenis Cuti',
                'Detail Tanggal Cuti',
                'Lama (Hari)',
                'Alasan',
                'Alamat Selama Cuti',
                'Status',
                'Tanggal Diajukan'
            ]
        ];
    }

    public function map($cuti): array
    {
        // ... (Logika map sama seperti sebelumnya) ...
        
        $lamaCuti = (int) $cuti->lama_cuti;
        if ($lamaCuti <= 0 && isset($cuti->tanggal_mulai) && isset($cuti->tanggal_akhir)) {
            $lamaCuti = Carbon::parse($cuti->tanggal_mulai)
                ->diffInDays(Carbon::parse($cuti->tanggal_akhir)) + 1;
        }

        $unitKerja = '-';
        if ($cuti->employee && $cuti->employee->unitKerja) {
            $unitKerja = $cuti->employee->unitKerja->nama;
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

        return [
            $cuti->id, 
            " " . ($cuti->employee->NIP ?? '-'),
            $cuti->employee->nama ?? '-',
            $cuti->employee->jabatan ?? '-',
            $unitKerja,
            $cuti->jenis_cuti,
            $detailTanggal,
            $lamaCuti . ' Hari',
            $cuti->alasan,
            $cuti->alamat_selama_cuti,
            $cuti->status_global,
            Carbon::parse($cuti->created_at)->format('d/m/Y'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5, 'B' => 18, 'C' => 25, 'D' => 20, 'E' => 20, 'F' => 20,
            'G' => 45, 'H' => 12, 'I' => 35, 'J' => 35, 'K' => 25, 'L' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            3 => ['font' => ['size' => 10, 'italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            
            // Header Tabel (Row 5)
            // KARENA BARIS KOSONG DIHAPUS, HEADER SEKARANG NAIK KE BARIS 5
            // JADI STYLE INI AKAN PAS MENGENAI HEADER
            5 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');
                $sheet->mergeCells('A3:L3');
                
                $sheet->getRowDimension(5)->setRowHeight(30);
                $sheet->getStyle('A5:L5')->getAlignment()->setWrapText(true);
                
                $highestRow = $sheet->getHighestRow();
                
                if ($highestRow > 5) {
                    // Border data
                    $sheet->getStyle("A5:L{$highestRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                    
                    // Alignment Center untuk data (mulai baris 6)
                    $centerColumns = ['A', 'B', 'H', 'K', 'L'];
                    foreach ($centerColumns as $col) {
                        $sheet->getStyle("{$col}6:{$col}{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    
                    // Wrap text
                    $sheet->getStyle("G6:G{$highestRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("I6:J{$highestRow}")->getAlignment()->setWrapText(true);
                    
                    $sheet->getStyle("A6:L{$highestRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                }
            },
        ];
    }
}