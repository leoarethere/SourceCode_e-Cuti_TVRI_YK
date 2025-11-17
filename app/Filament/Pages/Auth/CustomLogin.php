<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login;
use Illuminate\Contracts\Support\Htmlable;

class CustomLogin extends Login
{
    public function getHeading(): string | Htmlable
    {
        return 'Masuk ke E-Cuti';
    }
    
    public function getSubheading(): string | Htmlable | null
    {
        return 'Silakan masukkan email dan password Anda';
    }
}