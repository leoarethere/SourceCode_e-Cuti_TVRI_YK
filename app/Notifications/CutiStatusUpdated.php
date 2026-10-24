<?php

namespace App\Notifications;

use App\Models\Cuti;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification; // <-- Impor ShouldQueue yang benar
use Illuminate\Contracts\Queue\ShouldQueue; // <-- Impor MailMessage yang benar (dengan backslash)
use Illuminate\Notifications\Messages\MailMessage;

class CutiStatusUpdated extends Notification
{
    use Queueable;

    // Properti untuk menyimpan data cuti
    protected Cuti $cuti;

    /**
     * Buat instance notifikasi baru.
     */
    public function __construct(Cuti $cuti)
    {
        $this->cuti = $cuti; // Terima data Cuti saat notifikasi dibuat
    }

    /**
     * Tentukan channel pengiriman (kita pakai 'mail').
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Buat representasi 'mail' dari notifikasi.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Kolom lama `status` sudah diganti `status_global`; pakai yang baru agar tidak crash
        $global = $this->cuti->status_global ?? '-';
        $isApproved = str_contains($global, 'Disetujui');
        $statusText = $isApproved ? 'Telah Disetujui' : "Berstatus: {$global}";
        $subject = "Status Pengajuan Cuti Anda {$statusText}";
        $greeting = 'Halo, ' . ($this->cuti->employee?->nama ?? 'Pegawai');
        $mulai = $this->cuti->tanggal_mulai ?? '-';
        $akhir = $this->cuti->tanggal_akhir ?? '-';
        $line = "Pengajuan cuti Anda dari tanggal {$mulai} s/d {$akhir} **{$statusText}**.";

        return (new MailMessage)
                    ->subject($subject)
                    ->greeting($greeting)
                    ->line($line)
                    ->line('Terima kasih telah menggunakan aplikasi kami!');
    }
}