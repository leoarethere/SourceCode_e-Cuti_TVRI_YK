<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'Dashboard';
    protected static ?string $navigationLabel = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            \Filament\Widgets\AccountWidget::class,
            \Filament\Widgets\StatsOverviewWidget::class,
        ];
    }
}
