<?php

namespace App\Filament\Resources\CutiResource\Pages;

use App\Models\Employee;
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
        
        // 1. Logika pengisian ID Pegawai (Wajib untuk non-admin)
        if ($user->role !== 'admin') {
            
            // Cari ID pegawai berdasarkan user_id yang login
            $employee = Employee::where('user_id', $user->id)->first();

            if ($employee) {
                $data['employee_id'] = $employee->id;
            } else {
                // Jika tidak ada employee terkait, tampilkan error dan hentikan proses
                Notification::make()
                    ->title('Error: Profil Pegawai Tidak Ditemukan')
                    ->body("Akun Anda ({$user->name}) belum terhubung dengan data pegawai. Silakan hubungi admin untuk menautkan akun Anda.")
                    ->danger()
                    ->persistent()
                    ->send();
                
                $this->halt();
            }
        }

        // 2. PERBAIKAN STATUS (Sesuai Struktur Baru Unit Kerja)
        // Menggunakan 'status_atasan_langsung' menggantikan 'status_sdm'
        $data['status_atasan_langsung'] = 'pending'; 
        $data['status_tata_usaha'] = 'pending';
        $data['status_kepala'] = 'pending';
        
        // 3. Set Status Global Awal
        $data['status_global'] = 'Menunggu Persetujuan Atasan Langsung';

        // Validasi lampiran untuk Cuti Sakit
        if ($data['jenis_cuti'] === 'Cuti Sakit' && empty($data['lampiran_link'])) {
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
}