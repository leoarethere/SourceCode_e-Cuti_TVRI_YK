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
        // Hitung Cuti yang memasuki alur persetujuan (seluruh tahap "Menunggu")
        $pendingCount = Cuti::whereIn('status_global', Cuti::STATUS_PENDING)->count();

        // Hitung Cuti yang sudah Disetujui tahun ini
        // Pakai OR pada tanggal_mulai / tanggal_akhir agar tidak ada yang terlewat
        $approvedCount = Cuti::where('status_global', Cuti::STATUS_DISETUJUI)
                            ->where(function ($q) {
                                $q->whereYear('tanggal_mulai', now()->year)
                                  ->orWhereYear('tanggal_akhir', now()->year);
                            })
                            ->count();

        // Hitung Total Pegawai
        $employeeCount = Employee::count();

        // Hitung Pengajuan yang sudah diperingkatkan SAW periode aktif
        $currentPeriode = now()->format('Y-m');
        $rankedCount = Cuti::where('periode', $currentPeriode)
            ->whereIn('status_global', Cuti::STATUS_PENDING)
            ->whereNotNull('ranking')
            ->count();

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

            Stat::make('Prioritas SAW (' . now()->isoFormat('MMM Y') . ')', $rankedCount)
                ->description('Pengajuan siap dipertimbangkan')
                ->icon('heroicon-o-trophy')
                ->color('info')
                ->url(route('filament.admin.pages.perangkingan-cuti')),
        ];
    }

    // Pastikan hanya admin/atasan yang bisa lihat
    public static function canView(): bool
    {
        return Auth::user()?->role !== 'pegawai';
    }
}