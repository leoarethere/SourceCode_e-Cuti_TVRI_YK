<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WelcomeHeader extends Widget
{
    protected static string $view = 'filament.widgets.welcome-header';

    // Agar widget melebar penuh (Full Width)
    protected int | string | array $columnSpan = 'full';

    // Urutan paling atas (Sort Order)
    protected static ?int $sort = -1;

    /**
     * Mengirim data ke View (Blade)
     */
    protected function getViewData(): array
    {
        $user = Auth::user();

        // Logika Sapaan Berdasarkan Waktu (Asia/Jakarta, konsisten dengan config/app.php)
        $hour = (int) \Carbon\Carbon::now('Asia/Jakarta')->format('H');
        if ($hour >= 5 && $hour < 11) {
            $greeting = 'Selamat Pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            $greeting = 'Selamat Siang';
        } elseif ($hour >= 15 && $hour < 18) {
            $greeting = 'Selamat Sore';
        } else {
            $greeting = 'Selamat Malam';
        }

        return [
            'user' => $user,
            'greeting' => $greeting,
            'role' => ucfirst(str_replace('_', ' ', $user->role)), // Format role biar rapi
        ];
    }
}