<?php

namespace App\Filament\Resources\BebanKerjaUnitResource\Pages;

use App\Filament\Resources\BebanKerjaUnitResource;
use App\Models\BebanKerjaUnit;
use App\Services\SawService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBebanKerjaUnit extends EditRecord
{
    protected static string $resource = BebanKerjaUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->validateUniquePeriode();
    }

    protected function afterSave(): void
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
            ->whereKeyNot($this->record->getKey())
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
