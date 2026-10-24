<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Cuti;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

class FormCutiExport implements FromView, WithEvents
{
    protected $cuti;

    public function __construct(Cuti $cuti)
    {
        $this->cuti = $cuti;
    }

    /**
     * Return view untuk export
     */
    public function view(): View
    {
        $cuti = $this->cuti->load('employee');
        
        // Hitung masa kerja
        $masaKerja = $cuti->employee?->tanggal_bergabung
            ? Carbon::parse($cuti->employee->tanggal_bergabung)->diffInYears(now())
            : 0;

        // Hitung lama cuti: utamakan array tanggal (sistem baru), fallback range
        if (!empty($cuti->tanggal_cuti_array) && is_array($cuti->tanggal_cuti_array)) {
            $lamaCuti = count($cuti->tanggal_cuti_array);
        } elseif ($cuti->tanggal_mulai && $cuti->tanggal_akhir) {
            $lamaCuti = Carbon::parse($cuti->tanggal_mulai)
                ->diffInDays(Carbon::parse($cuti->tanggal_akhir)) + 1;
        } else {
            $lamaCuti = 0;
        }
        
        return view('exports.form-cuti', [
            'cuti' => $cuti,
            'masaKerja' => $masaKerja,
            'lamaCuti' => $lamaCuti,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Set landscape orientation
                $event->sheet->getDelegate()->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
                
                // Set paper size (A4)
                $event->sheet->getDelegate()->getPageSetup()
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                
                // Set margins
                $event->sheet->getDelegate()->getPageMargins()
                    ->setTop(0.5)
                    ->setRight(0.5)
                    ->setLeft(0.5)
                    ->setBottom(0.5);
            },
        ];
    }
}