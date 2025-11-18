<?php

namespace App\Filament\Widgets;

use App\Models\Cuti;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class CutiStatsOverview extends BaseWidget
{
    // Atur urutan agar muncul paling atas
    protected static ?int $sort = 1;
    
    // Refresh data setiap 15 detik
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // Hitung Cuti yang sedang berjalan (Status mengandung kata 'Menunggu')
        $pendingCount = Cuti::where('status_global', 'like', '%Menunggu%')->count();

        // Hitung Cuti yang sudah Disetujui tahun ini
        $approvedCount = Cuti::where('status_global', 'Disetujui')
                            ->whereYear('tanggal_mulai', now()->year)
                            ->count();

        // Hitung Total Pegawai
        $employeeCount = Employee::count();

        return [
            Stat::make('Total Pegawai', $employeeCount)
                ->description('Pegawai aktif terdaftar')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Pengajuan Pending', $pendingCount)
                ->description('Menunggu persetujuan')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Cuti Disetujui (Tahun Ini)', $approvedCount)
                ->description('Total pengajuan diterima')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }

    // Pastikan hanya admin/atasan yang bisa lihat
    public static function canView(): bool
    {
        return Auth::user()->role !== 'pegawai';
    }
}