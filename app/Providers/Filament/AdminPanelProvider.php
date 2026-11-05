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
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')

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

            // LIGHT MODE DEFAULT
            ->darkMode(false)

            ->colors([
                'primary' => Color::Blue,
            ])
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make('Master Data'),
                \Filament\Navigation\NavigationGroup::make('SPK'),
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
        // 1. Footer dashboard
        FilamentView::registerRenderHook(
            PanelsRenderHook::CONTENT_END,
            fn (): string => Blade::render("@include('filament.layout.footer')")
        );

        // 2. Force light mode default
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => '
                <script>
                    (function() {
                        const currentTheme = localStorage.getItem("theme");

                        if (!currentTheme || currentTheme === "dark") {
                            localStorage.setItem("theme", "light");
                            document.documentElement.classList.remove("dark");
                        }

                        if (localStorage.getItem("theme") === "light") {
                            document.documentElement.classList.remove("dark");
                        }
                    })();
                </script>
            '
        );

        // 3. Tampilan halaman login: background foto + kartu form semi-transparan
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            function (): string {
                $banner = asset('img/loginbanner.jpeg');

                return <<<HTML
                <style>
                    /* Background foto + lapisan gelap dalam satu deklarasi (tanpa pseudo-element) */
                    .fi-body:has(.fi-simple-main) {
                        background-image:
                            linear-gradient(rgba(15, 23, 42, 0.55), rgba(15, 23, 42, 0.55)),
                            url("{$banner}");
                        background-size: cover;
                        background-position: center;
                        background-repeat: no-repeat;
                        background-attachment: fixed;
                    }

                    /* Jarak antara logo dan judul "Masuk ke akun Anda".
                       Ubah 1rem jika ingin lebih rapat atau lebih renggang. */
                    .fi-body .fi-simple-header .fi-logo {
                        margin-bottom: 1rem;
                    }

                    /* Yang ditimpa adalah KARTU (.fi-simple-main), bukan wadah lebar (.fi-simple-main-ctn) */
                    .fi-body .fi-simple-main {
                        background-color: rgba(255, 255, 255, 0.95);
                        backdrop-filter: blur(10px);
                        -webkit-backdrop-filter: blur(10px);
                        border-radius: 1rem;
                        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
                    }

                    .dark .fi-body .fi-simple-main {
                        background-color: rgba(17, 24, 39, 0.92);
                        border: 1px solid rgba(255, 255, 255, 0.1);
                    }
                </style>
                HTML;
            }
        );

        // 4. Copyright & kredit di bawah form login
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            function (): string {
                $year = date('Y');

                return <<<HTML
                <style>
                    .login-credits {
                        margin-top: 1.5rem;
                        padding-top: 1.25rem;
                        border-top: 1px solid #e5e7eb;
                        font-size: 0.75rem;
                        line-height: 1.6;
                        color: #6b7280;
                        text-align: center;
                    }

                    .login-credits p { margin: 0; }
                    .login-credits-label { margin-top: 0.875rem !important; font-weight: 500; color: #374151; }

                    .login-credits-link {
                        color: #2563eb;
                        font-weight: 600;
                        text-decoration: none;
                    }

                    .login-credits-link:hover,
                    .login-credits-link:focus-visible {
                        color: #1d4ed8;
                        text-decoration: underline;
                        text-underline-offset: 3px;
                    }

                    .login-credits-list {
                        list-style: none;
                        margin: 0.5rem 0 0;
                        padding: 0;
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: center;
                        gap: 0.375rem;
                    }

                    .login-credits-list li { white-space: nowrap; }

                    .login-credits-list .login-credits-link {
                        display: inline-block;
                        padding: 0.1875rem 0.625rem;
                        border: 1px solid #e5e7eb;
                        border-radius: 999px;
                        background-color: #f9fafb;
                        font-weight: 500;
                        transition: background-color 0.2s ease, border-color 0.2s ease;
                    }

                    .login-credits-list .login-credits-link:hover,
                    .login-credits-list .login-credits-link:focus-visible {
                        background-color: #eff6ff;
                        border-color: #bfdbfe;
                        text-decoration: none;
                    }

                    .dark .login-credits { border-top-color: rgba(255, 255, 255, 0.1); color: #a1a1aa; }
                    .dark .login-credits-label { color: #d4d4d8; }
                    .dark .login-credits-link { color: #60a5fa; }
                    .dark .login-credits-link:hover,
                    .dark .login-credits-link:focus-visible { color: #93c5fd; }
                    .dark .login-credits-list .login-credits-link { background-color: #27272a; border-color: #3f3f46; }
                    .dark .login-credits-list .login-credits-link:hover,
                    .dark .login-credits-list .login-credits-link:focus-visible { background-color: #1e3a5f; border-color: #1d4ed8; }
                </style>

                <div class="login-credits">
                    <p>
                        Copyright {$year}
                        <a href="https://yogyakarta.tvri.go.id/" target="_blank" rel="noopener noreferrer" class="login-credits-link">TVRI Stasiun D.I. Yogyakarta</a>
                    </p>
                    <p>All rights reserved.</p>

                    <p class="login-credits-label">&lt;/&gt; Didesain &amp; Dikembangkan oleh</p>
                    <ul class="login-credits-list">
                        <li><a href="https://www.instagram.com/leoarethere/" target="_blank" rel="noopener noreferrer" class="login-credits-link">Leonardo Putra Susanto</a></li>
                        <li><a href="https://www.instagram.com/destywahyu01/" target="_blank" rel="noopener noreferrer" class="login-credits-link">Desty Wahyu Anjani</a></li>
                        <li><a href="#" class="login-credits-link">Ilham Bagaskara</a></li>
                    </ul>
                </div>
                HTML;
            }
        );
    }
}