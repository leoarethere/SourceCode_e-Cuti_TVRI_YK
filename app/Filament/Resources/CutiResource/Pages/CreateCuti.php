<?php

namespace App\Filament\Resources\CutiResource\Pages;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Cuti;
use App\Models\Employee;
use App\Models\UnitKerja;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\CutiResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCuti extends CreateRecord
{
    protected static string $resource = CutiResource::class;

    /**
     * Method ini dipanggil SEBELUM form di-render
     * Untuk mengisi default value employee_id jika form dibuka
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = Auth::user();
        
        // Jika bukan admin, coba cari dan isi employee_id otomatis
        if ($user->role !== 'admin') {
            $employee = Employee::where('user_id', $user->id)->first();
            
            if ($employee) {
                $data['employee_id'] = $employee->id;
            }
        }
        
        return $data;
    }

    /**
     * Method ini dipanggil SEBELUM data disimpan ke database
     * Ini adalah langkah terakhir sebelum INSERT untuk memanipulasi data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        // 1. Kunci employee_id: non-admin selalu pakai miliknya (anti IDOR/tampering),
        // admin wajib pilih.
        if ($user->role !== 'admin') {
            $employee = Employee::where('user_id', $user->id)->first();
            if (! $employee) {
                Notification::make()
                    ->title('Akun Tidak Terhubung')
                    ->body('Silakan hubungi administrator untuk menautkan akun Anda dengan data pegawai.')
                    ->danger()
                    ->persistent()
                    ->send();
                $this->halt();
            }
            $data['employee_id'] = $employee->id;
        } else {
            $employee = isset($data['employee_id']) ? Employee::find($data['employee_id']) : null;
            if (! $employee) {
                Notification::make()
                    ->title('Error Validasi')
                    ->body('Pilih pegawai terlebih dahulu.')
                    ->danger()
                    ->persistent()
                    ->send();
                $this->halt();
            }
        }

        // 1b. Backdate: pengaju (pegawai + ketua_tim) tidak boleh; admin/TU/kepala boleh
        if (in_array($user->role, ['pegawai', 'ketua_tim']) && !empty($data['tanggal_cuti_array']) && is_array($data['tanggal_cuti_array'])) {
            $hasBackdate = collect($data['tanggal_cuti_array'])
                ->some(fn($date) => Carbon::parse($date)->lt(now()->startOfDay()));
            
            if ($hasBackdate) {
                Notification::make()
                    ->title('Backdate Tidak Diizinkan')
                    ->body('Pegawai tidak bisa mengajukan cuti backdate. Hubungi admin atau tata usaha.')
                    ->danger()
                    ->persistent()
                    ->send();
                
                $this->halt();
            }
        }

        // ✅ 2. UPDATE: Auto-fill tanggal_mulai dan tanggal_akhir dari array
        if (!empty($data['tanggal_cuti_array']) && is_array($data['tanggal_cuti_array'])) {
            $dates    = collect($data['tanggal_cuti_array'])->sort()->values();
            $lamaCuti = $dates->count();
            $data['tanggal_cuti_array'] = $dates->all();
            $data['tanggal_mulai']      = $dates->first();
            $data['tanggal_akhir']      = $dates->last();
            $data['periode']            = Carbon::parse($data['tanggal_mulai'])->format('Y-m');
            $data['lama_cuti']          = $lamaCuti;

            // === VALIDASI SALDO CUTI TAHUNAN ===
            if (($data['jenis_cuti'] ?? null) === 'Cuti Tahunan') {
                $employee = Employee::find($data['employee_id']);

                if (! $employee || $employee->sisa_cuti_tahunan < $lamaCuti) {
                    $sisa = $employee?->sisa_cuti_tahunan ?? 0;
                    Notification::make()
                        ->title('Sisa Cuti Tidak Cukup')
                        ->body("Anda mengajukan {$lamaCuti} hari, namun sisa cuti Anda hanya {$sisa} hari.")
                        ->danger()
                        ->persistent()
                        ->send();

                    $this->halt();
                }
            }

        } else {
            // Validasi: Harus ada tanggal yang dipilih
            Notification::make()
                ->title('Error Validasi')
                ->body('Anda harus memilih minimal 1 tanggal untuk cuti.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // 3. Set Status (Sesuai Struktur Baru Unit Kerja)
        $data['status_atasan_langsung'] = 'pending'; 
        $data['status_tata_usaha'] = 'pending';
        $data['status_kepala'] = 'pending';
        
        // 4. Set Status Global Awal
        $data['status_global'] = Cuti::STATUS_MENUNGGU_ATASAN;

        // === PENANGANAN SELF-APPROVAL ===
        // Jika pengajunya adalah ketua unit itu sendiri, tahap 1 (persetujuan
        // ketua) diberlakukan sebagai otomatis-disetujui dan pengajuan langsung
        // lanjut ke Tata Usaha. Ini mencegah ketua menyetujui cutinya sendiri.
        $unitPengaju = Employee::find($data['employee_id'])?->unitKerja;
        if ($unitPengaju && $unitPengaju->ketua_user_id === $user->id) {
            $data['status_atasan_langsung'] = 'approved';
            $data['status_global'] = Cuti::STATUS_MENUNGGU_TU;
        }

        // 5. Validasi overlap: tidak boleh ada cuti lain (pending/approved) pada tanggal yg sama
        if (!empty($data['tanggal_cuti_array']) && is_array($data['tanggal_cuti_array'])) {
            $requestedDates = collect($data['tanggal_cuti_array'])->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->all();
            $overlap = Cuti::where('employee_id', $data['employee_id'])
                ->whereIn('status_global', Cuti::STATUS_PENDING_OR_APPROVED)
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

        // 6. Validasi lampiran untuk Cuti Sakit
        if (($data['jenis_cuti'] ?? null) === 'Cuti Sakit' && empty($data['lampiran_link'])) {
            Notification::make()
                ->title('Error Validasi')
                ->body('Lampiran surat dokter wajib dilampirkan untuk Cuti Sakit.')
                ->danger()
                ->persistent()
                ->send();
            
            $this->halt();
        }

        return $data;
    }

    /**
     * Method alternatif: Hook setelah form divalidasi
     * Sebagai pengaman tambahan sebelum create
     */
    protected function afterValidate(): void
    {
        $user = Auth::user();
        
        // Double-check: Pastikan employee_id bisa ditemukan untuk non-admin
        if ($user->role !== 'admin') {
            $employee = Employee::where('user_id', $user->id)->first();
            
            if (!$employee) {
                Notification::make()
                    ->title('Akun Tidak Terhubung')
                    ->body('Silakan hubungi administrator untuk menautkan akun Anda dengan data pegawai.')
                    ->danger()
                    ->persistent()
                    ->send();
                    
                $this->halt();
            }
        }
    }

    protected function afterCreate(): void
    {
        $cuti = $this->record;

        // Cari Ketua Unit dari Pegawai yang mengajukan (null-safe)
        $unitKerja = $cuti->employee?->unitKerja;
        $lamaCuti  = $cuti->lama_cuti;

        $isSelfSubmission = $unitKerja && $unitKerja->ketua_user_id === Auth::id();

        if ($unitKerja && $unitKerja->ketua_user_id && ! $isSelfSubmission) {
            $ketuaTim = User::find($unitKerja->ketua_user_id);

            if ($ketuaTim) {
                Notification::make()
                    ->title('Pengajuan Cuti Baru')
                    ->body('Pegawai ' . ($cuti->employee?->nama ?? '-') . " mengajukan cuti selama {$lamaCuti} hari. Mohon diperiksa.")
                    ->icon('heroicon-o-document-text')
                    ->warning()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('Lihat')
                            ->url(CutiResource::getUrl('index')),
                    ])
                    ->sendToDatabase($ketuaTim);
            }
        }

        // Fallback: pengajuan langsung oleh ketua unit (self-approval bypass)
        // atau unit tanpa ketua -> notifikasi langsung ke Tata Usaha agar
        // pengajuan tidak menggantung tanpa approver.
        if ($isSelfSubmission || ! $unitKerja?->ketua_user_id) {
            $penerima = User::where('role', 'tata_usaha')->get();

            Notification::make()
                ->title('Pengajuan Cuti Baru (Via Tata Usaha)')
                ->body($isSelfSubmission
                    ? 'Pengajuan cuti oleh Ketua Unit ' . ($cuti->employee?->nama ?? '-') . " selama {$lamaCuti} hari. Diteruskan langsung ke Tata Usaha."
                    : 'Pengajuan cuti ' . ($cuti->employee?->nama ?? '-') . " selama {$lamaCuti} hari. Unit ini belum memiliki ketua, mohon diproses Tata Usaha.")
                ->icon('heroicon-o-document-text')
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('Lihat')
                        ->url(CutiResource::getUrl('index')),
                ])
                ->sendToDatabase($penerima);
        }
    }
}