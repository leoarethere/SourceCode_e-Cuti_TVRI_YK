<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Filament\Http\Middleware\Authenticate;
use Filament\Support\Facades\FilamentView;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()

            // === KONFIGURASI LOGO & NAMA ===
            ->brandName('E-Cuti TVRI Yogyakarta')
            ->brandLogo(fn () => new HtmlString('
                <div class="flex items-center gap-x-1">
                    <img 
                        src="'. asset('img/logodark.png') .'" 
                        alt="Logo" 
                        class="h-10" 
                        style="height: 3rem;"
                    >
                    <span class="font-bold text-2xl text-gray-950 dark:text-white">
                        Yogyakarta
                    </span>
                </div>
            '))
            ->favicon(asset('img/favicon.png'))
            // ===============================
            
            // ✅ LIGHT MODE DEFAULT (User masih bisa toggle)
            ->darkMode(false)
            
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        // 1. Hook untuk Footer Dashboard (Yang sudah Anda buat sebelumnya)
        FilamentView::registerRenderHook(
            PanelsRenderHook::CONTENT_END,
            fn (): string => Blade::render("@include('filament.layout.footer')")
        );

        // 2. ✅ TAMBAHAN BARU: Force Light Mode Default
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => '
                <script>
                    // Set light mode sebagai default jika belum ada preferensi
                    (function() {
                        const currentTheme = localStorage.getItem("theme");
                        
                        // Jika belum pernah set, default ke light
                        if (!currentTheme || currentTheme === "dark") {
                            localStorage.setItem("theme", "light");
                            document.documentElement.classList.remove("dark");
                        }
                        
                        // Cegah flash dark mode saat load
                        if (localStorage.getItem("theme") === "light") {
                            document.documentElement.classList.remove("dark");
                        }
                    })();
                </script>
            '
        );

        // 3. Modifikasi Tampilan Login (Yang sudah ada)
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => '
                <style>
                    /* Hanya terapkan di halaman login */
                    .fi-body.fi-panel-admin.fi-page-login {
                        background-image: url("'. asset('img/loginbanner.jpeg') .'"); 
                        background-size: cover;
                        background-position: center;
                        background-repeat: no-repeat;
                    }

                    .fi-body.fi-panel-admin.fi-page-login::before {
                        content: "";
                        position: absolute;
                        top: 0; right: 0; bottom: 0; left: 0;
                        background: rgba(0, 0, 0, 0.5);
                        z-index: -1;
                    }

                    .fi-page-login .fi-simple-main-ctn {
                        background-color: rgba(255, 255, 255, 0.9) !important;
                        backdrop-filter: blur(10px);
                        border-radius: 1rem;
                        padding: 2rem;
                        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                    }

                    .dark .fi-page-login .fi-simple-main-ctn {
                        background-color: rgba(17, 24, 39, 0.9) !important;
                        border: 1px solid rgba(255,255,255,0.1);
                    }
                </style>
            '
        );

        // 4. Copyright di Login Form
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): string => '
                <div class="text-center text-gray-500 dark:text-gray-400 mt-4">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Copyright ' . date('Y') . ' TVRI Stasiun D.I. Yogyakarta | All rights reserved.
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        <span>&lt;/&gt;</span>
                        Dikembangkan oleh :
                        <a href="https://www.instagram.com/leoarethere/" target="_blank" class="underline hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            Leonardo Putra Susanto
                        </a>
                        &amp;
                        <a href="https://www.instagram.com/destywahyu01/" target="_blank" class="underline hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            Desty Wahyu Anjani
                        </a>
                    </p>
                </div>
            '
        );
    }
}