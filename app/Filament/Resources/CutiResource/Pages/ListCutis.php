<?php

namespace App\Filament\Resources\CutiResource\Pages;

use App\Exports\CutiExport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;

// === Impor yang Kita Butuhkan ===

// Untuk Tombol Aksi
use App\Services\RekapCutiGenerator;

// Untuk Tombol Excel (MASIH DIPAKAI)
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelTypes;
use App\Filament\Resources\CutiResource;

// Untuk Tombol PDF (BARU)
use Filament\Resources\Pages\ListRecords; // <-- Generator TCPDF manual kita
use Symfony\Component\HttpFoundation\StreamedResponse; // <-- Untuk output PDF

class ListCutis extends ListRecords
{
    protected static string $resource = CutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(), 

            // === TOMBOL EXCEL REKAP ===
            Action::make('exportExcel')
                ->label('Export Rekap Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $data = $this->getFilteredTableQuery()->with(['employee.unitKerja'])->get(); 
                    
                    return Excel::download(
                        new CutiExport($data), 
                        'rekap_cuti_' . date('Y-m-d_His') . '.xlsx'
                    );
                }),

            // === TOMBOL PDF REKAP ===
            Action::make('exportPdfRekap')
                ->label('Export Rekap PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->action(function () {
                    $data = $this->getFilteredTableQuery()->with(['employee.unitKerja'])->get(); 
                    
                    return new StreamedResponse(function () use ($data) {
                        app(RekapCutiGenerator::class)->generate($data);
                    }, 200, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),
        ];
    }
}