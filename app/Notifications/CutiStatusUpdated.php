<?php

namespace App\Notifications;

use App\Models\Cuti;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CutiStatusUpdated extends Notification implements ShouldQueue
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
     * Tentukan channel pengiriman (mail + database untuk bell Filament).
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Representasi notifikasi untuk database (bell di panel Filament).
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'cuti_id' => $this->cuti->id,
            'status' => $this->cuti->status_global,
            'title' => 'Status Pengajuan Cuti Diperbarui',
            'body' => 'Pengajuan cuti ' . ($this->cuti->employee?->nama ?? 'pegawai')
                . ' berstatus: ' . ($this->cuti->status_global ?? '-') . '.',
            'icon' => 'heroicon-o-document-text',
            'color' => str_starts_with((string) $this->cuti->status_global, Cuti::STATUS_DISETUJUI)
                ? 'success'
                : 'warning',
        ];
    }

    /**
     * Buat representasi 'mail' dari notifikasi.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Kolom lama `status` sudah diganti `status_global`; pakai yang baru agar tidak crash
        $global = $this->cuti->status_global ?? '-';
        $isApproved = str_starts_with($global, Cuti::STATUS_DISETUJUI);
        $statusText = $isApproved ? 'Telah Disetujui' : "Berstatus: {$global}";
        $subject = "Status Pengajuan Cuti Anda {$statusText}";
        $greeting = 'Halo, ' . ($this->cuti->employee?->nama ?? 'Pegawai');

        // tanggal_mulai/tanggal_akhir sudah di-cast Carbon (date),
        // format agar email tidak menampilkan timestamp penuh.
        $mulai = $this->cuti->tanggal_mulai?->translatedFormat('d F Y') ?? '-';
        $akhir = $this->cuti->tanggal_akhir?->translatedFormat('d F Y') ?? '-';
        $line = "Pengajuan cuti Anda dari tanggal {$mulai} s/d {$akhir} **{$statusText}**.";

        return (new MailMessage)
                    ->subject($subject)
                    ->greeting($greeting)
                    ->line($line)
                    ->line('Terima kasih telah menggunakan aplikasi kami!');
    }
}
