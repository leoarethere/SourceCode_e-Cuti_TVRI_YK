<?php

namespace App\Providers;

use App\Models\Cuti;
use App\Observers\CutiObserver;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider; // <-- Pastikan import ini ada

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paksa Carbon menggunakan Bahasa Indonesia
        config(['app.locale' => 'id']);
        Carbon::setLocale('id');

        // Hitung ulang prioritas SAW saat cuti dibuat/diubah/dihapus.
        Cuti::observe(CutiObserver::class);
    }
}
