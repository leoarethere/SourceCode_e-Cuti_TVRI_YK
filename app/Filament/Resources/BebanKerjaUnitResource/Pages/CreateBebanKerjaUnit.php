<?php

namespace App\Filament\Resources\BebanKerjaUnitResource\Pages;

use App\Filament\Resources\BebanKerjaUnitResource;
use App\Models\BebanKerjaUnit;
use App\Services\SawService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBebanKerjaUnit extends CreateRecord
{
    protected static string $resource = BebanKerjaUnitResource::class;

    protected function beforeCreate(): void
    {
        $this->validateUniquePeriode();
    }

    protected function afterCreate(): void
    {
        try {
            app(SawService::class)->hitungPrioritas($this->record->periode);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Perhitungan SAW gagal')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }

    private function validateUniquePeriode(): void
    {
        $exists = BebanKerjaUnit::query()
            ->where('unit_kerja_id', $this->data['unit_kerja_id'] ?? null)
            ->where('periode', $this->data['periode'] ?? null)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Data sudah ada')
                ->body('Beban kerja untuk divisi dan periode tersebut sudah diisi.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
