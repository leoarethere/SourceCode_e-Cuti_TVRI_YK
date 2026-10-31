<?php

namespace App\Filament\Resources\KriteriaResource\Pages;

use App\Filament\Resources\KriteriaResource;
use App\Models\Cuti;
use App\Models\Kriteria;
use App\Services\SawService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditKriteria extends EditRecord
{
    protected static string $resource = KriteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Ubah satu bobot, lalu sesuaikan bobot kriteria lain secara proporsional
     * agar total selalu tepat 1.0000.
     */
    protected function beforeSave(): void
    {
        $newBobot = round((float) ($this->data['bobot'] ?? 0), 4);

        if ($newBobot < 0 || $newBobot > 1) {
            Notification::make()
                ->title('Bobot tidak valid')
                ->body('Bobot harus berada di antara 0 dan 1.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        $others = Kriteria::query()
            ->whereKeyNot($this->record->getKey())
            ->orderBy('kode')
            ->get();

        if ($others->isEmpty()) {
            return;
        }

        $sumOthers = (float) $others->sum('bobot');

        if ($sumOthers <= 0) {
            Notification::make()
                ->title('Bobot tidak dapat disesuaikan')
                ->body('Total bobot kriteria lain 0, sehingga tidak bisa dinormalisasi.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        $remaining = 1.0 - $newBobot;
        $factor = $remaining / $sumOthers;
        $allocated = 0.0;
        $lastIndex = $others->count() - 1;

        foreach ($others as $index => $kriteria) {
            $value = $index === $lastIndex
                ? round($remaining - $allocated, 4)
                : round((float) $kriteria->bobot * $factor, 4);

            $allocated += $value;
            $kriteria->update(['bobot' => $value]);
        }

        Notification::make()
            ->title('Bobot kriteria lain disesuaikan')
            ->body('Bobot kriteria lain disesuaikan otomatis agar total tetap 1.00.')
            ->success()
            ->send();
    }

    /**
     * Bobot berubah => seluruh periode perlu dihitung ulang.
     */
    protected function afterSave(): void
    {
        $saw = app(SawService::class);

        $periodes = Cuti::query()
            ->whereNotNull('periode')
            ->distinct()
            ->orderBy('periode')
            ->pluck('periode');

        foreach ($periodes as $periode) {
            try {
                $saw->hitungPrioritas($periode);
            } catch (\Throwable $e) {
                Notification::make()
                    ->title("Perhitungan SAW periode {$periode} gagal")
                    ->body($e->getMessage())
                    ->warning()
                    ->send();
            }
        }
    }
}
