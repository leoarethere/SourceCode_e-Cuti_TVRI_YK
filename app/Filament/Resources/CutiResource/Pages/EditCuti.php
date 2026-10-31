<?php

namespace App\Filament\Resources\CutiResource\Pages;

use Carbon\Carbon;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\CutiResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCuti extends EditRecord
{
    protected static string $resource = CutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () =>
                    Auth::user()->role === 'admin' ||
                    $this->record->status_global === \App\Models\Cuti::STATUS_MENUNGGU_ATASAN
                ),
        ];
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $user = Auth::user();

        // Admin bisa edit semua pengajuan yang masih pending
        if ($user->role === 'admin') {
            abort_if(
                ! in_array($this->record->status_global, \App\Models\Cuti::STATUS_PENDING, true),
                403,
                'Data sudah diproses final dan tidak dapat diubah.'
            );
            return;
        }

        // Non-admin: hanya boleh edit tahap 1 dan hanya pemiliknya sendiri
        abort_if(
            $this->record->status_global !== \App\Models\Cuti::STATUS_MENUNGGU_ATASAN,
            403,
            'Data sudah diproses dan tidak dapat diubah.'
        );

        abort_if(
            ($this->record->employee?->user_id ?? null) !== $user->id,
            403,
            'Anda bukan pemilik pengajuan ini.'
        );
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        // Kunci employee_id agar tidak bisa diganti via request
        unset($data['employee_id']);
        unset($data['status_atasan_langsung'], $data['status_tata_usaha'], $data['status_kepala']);

        // Admin boleh override status_global jika diinginkan
        if ($user->role !== 'admin') {
            unset($data['status_global']);
        }

        // 1. Tanggal wajib + hitung ulang min/max
        if (!empty($data['tanggal_cuti_array']) && is_array($data['tanggal_cuti_array'])) {
            $dates = collect($data['tanggal_cuti_array'])->sort()->values()->all();
            $data['tanggal_cuti_array'] = $dates;
            $data['tanggal_mulai'] = min($dates);
            $data['tanggal_akhir'] = max($dates);
            $data['periode'] = Carbon::parse($data['tanggal_mulai'])->format('Y-m');
            $lamaCuti = count($dates);
            $data['lama_cuti'] = $lamaCuti;

            // 2. Backdate: pengaju tidak boleh
            if (in_array($user->role, ['pegawai', 'ketua_tim'])) {
                $hasBackdate = collect($dates)->some(fn ($d) => Carbon::parse($d)->lt(now()->startOfDay()));
                if ($hasBackdate) {
                    Notification::make()
                        ->title('Backdate Tidak Diizinkan')
                        ->body('Pegawai tidak bisa mengubah ke tanggal lampau. Hubungi admin.')
                        ->danger()->persistent()->send();
                    $this->halt();
                }
            }

            // 3. Saldo (hanya Cuti Tahunan)
            $jenis = $data['jenis_cuti'] ?? $this->record->jenis_cuti;
            if ($jenis === 'Cuti Tahunan') {
                $employee = $this->record->employee;
                if (! $employee) {
                    Notification::make()->title('Data Pegawai Hilang')->danger()->persistent()->send();
                    $this->halt();
                }
                if ($employee->sisa_cuti_tahunan < $lamaCuti) {
                    Notification::make()
                        ->title('Sisa Cuti Tidak Cukup')
                        ->body("Anda mengajukan {$lamaCuti} hari, sisa {$employee->sisa_cuti_tahunan} hari.")
                        ->danger()->persistent()->send();
                    $this->halt();
                }
            }
        } else {
            Notification::make()
                ->title('Error Validasi')
                ->body('Anda harus memilih minimal 1 tanggal untuk cuti.')
                ->danger()->persistent()->send();
            $this->halt();
        }

        // 4. Validasi overlap: tidak boleh bentrok dengan cuti LAIN (pending/approved) pada tgl sama
        if (!empty($data['tanggal_cuti_array']) && is_array($data['tanggal_cuti_array'])) {
            $requestedDates = collect($data['tanggal_cuti_array'])->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->all();
            $overlap = \App\Models\Cuti::where('employee_id', $this->record->employee_id)
                ->where('id', '!=', $this->record->id) // Kecualikan record yg sedang diedit
                ->whereIn('status_global', ['Menunggu Persetujuan Atasan Langsung', 'Menunggu Persetujuan Tata Usaha', 'Menunggu Persetujuan Kepala', 'Disetujui'])
                ->where(function ($q) use ($requestedDates) {
                    $q->whereJsonContains('tanggal_cuti_array', $requestedDates[0]);
                    foreach (array_slice($requestedDates, 1) as $dt) {
                        $q->orWhereJsonContains('tanggal_cuti_array', $dt);
                    }
                })
                ->exists();

            if ($overlap) {
                Notification::make()
                    ->title('Tanggal Bentrok')
                    ->body('Beberapa tanggal yang dipilih sudah ada pengajuan cuti lain yang pending/disetujui.')
                    ->danger()->persistent()->send();
                $this->halt();
            }
        }

        // 5. Lampiran wajib untuk Cuti Sakit
        $jenis = $data['jenis_cuti'] ?? $this->record->jenis_cuti;
        if ($jenis === 'Cuti Sakit' && empty($data['lampiran_link'] ?? $this->record->lampiran_link)) {
            Notification::make()
                ->title('Error Validasi')
                ->body('Lampiran surat dokter wajib untuk Cuti Sakit.')
                ->danger()->persistent()->send();
            $this->halt();
        }

        return $data;
    }
}
