<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WelcomeWidget extends Widget
{
    // Hubungkan dengan file tampilan (view) yang akan kita buat di Langkah 2
    protected static string $view = 'filament.widgets.welcome-widget';

    // Opsional: Agar widget melebar penuh
    protected int | string | array $columnSpan = 'full';
}