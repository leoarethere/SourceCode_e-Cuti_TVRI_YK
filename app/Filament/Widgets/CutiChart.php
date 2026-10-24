<?php

namespace App\Filament\Widgets;

use App\Models\Cuti;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CutiChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Cuti Disetujui (Tahun Ini)';
    protected static ?int $sort = 2; // Muncul di bawah statistik
    protected int | string | array $columnSpan = 'full'; // Lebar penuh

    protected function getData(): array
    {
        // MySQL-only: hitung HARI cuti per bulan (akurat untuk tanggal acak lintas bulan).
        // Ambil cuti Disetujui tahun ini lalu ekspansi tanggal_cuti_array di PHP.
        $year = (int) now()->year;
        $startOfYear = "$year-01-01";
        $endOfYear = "$year-12-31";

        // Ambil cuti Disetujui yang bersinggungan dengan tahun berjalan
        $cutis = Cuti::where('status_global', 'Disetujui')
            ->where(function ($q) use ($year, $startOfYear, $endOfYear) {
                $q->where(function ($sub) use ($startOfYear, $endOfYear) {
                    $sub->whereDate('tanggal_mulai', '<=', $endOfYear)
                        ->whereDate('tanggal_akhir', '>=', $startOfYear);
                })
                ->orWhere('tanggal_cuti_array', 'like', "%\"$year-%");
            })
            ->get(['tanggal_mulai', 'tanggal_akhir', 'tanggal_cuti_array']);

        // Siapkan array 12 bulan dengan nilai default 0
        $totals = array_fill(0, 12, 0);
        $year = now()->year;

        foreach ($cutis as $cuti) {
            $dates = [];
            if (!empty($cuti->tanggal_cuti_array) && is_array($cuti->tanggal_cuti_array)) {
                $dates = $cuti->tanggal_cuti_array;
            } elseif ($cuti->tanggal_mulai && $cuti->tanggal_akhir) {
                // Fallback data lama (range): ekspansi harian
                try {
                    $start = \Carbon\Carbon::parse($cuti->tanggal_mulai);
                    $end = \Carbon\Carbon::parse($cuti->tanggal_akhir);
                    for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                        $dates[] = $d->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            foreach ($dates as $d) {
                try {
                    $c = \Carbon\Carbon::parse($d);
                } catch (\Exception $e) {
                    continue;
                }
                if ((int) $c->year === (int) $year) {
                    $totals[(int) $c->month - 1]++;
                }
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Hari Cuti Disetujui',
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
        return Auth::user()?->role !== 'pegawai';
    }
}