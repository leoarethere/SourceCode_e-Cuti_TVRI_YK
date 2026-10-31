<?php

namespace App\Filament\Resources\KriteriaResource\Pages;

use App\Filament\Resources\KriteriaResource;
use App\Models\Kriteria;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateKriteria extends CreateRecord
{
    protected static string $resource = KriteriaResource::class;

    protected function beforeCreate(): void
    {
        $this->validateTotalBobot();
    }

    private function validateTotalBobot(): void
    {
        $total = (float) Kriteria::query()->sum('bobot') + (float) ($this->data['bobot'] ?? 0);

        if (abs($total - 1.0) > 0.0001) {
            Notification::make()
                ->title('Total bobot tidak valid')
                ->body('Total seluruh kriteria harus 1.00. Setelah perubahan ini totalnya menjadi ' . number_format($total, 2, ',', '.') . '.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
