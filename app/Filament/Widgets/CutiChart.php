<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Cuti;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CutiChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Cuti Disetujui (Tahun Ini)';
    protected static ?int $sort = 2; // Muncul di bawah statistik
    protected int | string | array $columnSpan = 'full'; // Lebar penuh

    protected function getData(): array
    {
        // Ambil data per bulan untuk tahun ini yang statusnya 'Disetujui'
        $data = Cuti::select(
                DB::raw('MONTH(tanggal_mulai) as bulan'),
                DB::raw('COUNT(*) as total')
            )
            ->where('status_global', 'Disetujui') // Pastikan statusnya benar
            ->whereYear('tanggal_mulai', now()->year)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        // Siapkan array 12 bulan dengan nilai default 0
        $totals = array_fill(0, 12, 0);

        // Masukkan data database ke array bulan yang tepat
        foreach ($data as $item) {
            // $item->bulan (1-12) dikurangi 1 karena array index mulai dari 0
            $totals[$item->bulan - 1] = $item->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Cuti Disetujui',
                    'data' => $totals,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)', // Warna Biru TVRI
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return Auth::user()->role !== 'pegawai';
    }
}