<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\Pages\Login;
use Filament\PanelProvider;
use Filament\Facades\Filament;
use App\Filament\Pages\Dashboard;
use Filament\Navigation\NavigationItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')

            // Register pages
            ->pages([
                Dashboard::class,
            ])

            ->login()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            ->navigationItems([
                NavigationItem::make('Dashboard')
                    ->icon('heroicon-o-home')
                    ->url(fn() => Dashboard::getUrl())
                    ->isActiveWhen(fn() => request()->routeIs('filament.admin.pages.dashboard')),
            ]);
    }
}
