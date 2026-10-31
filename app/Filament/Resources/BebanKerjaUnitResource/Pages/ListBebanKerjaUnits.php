<?php

namespace App\Filament\Resources\BebanKerjaUnitResource\Pages;

use App\Filament\Resources\BebanKerjaUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBebanKerjaUnits extends ListRecords
{
    protected static string $resource = BebanKerjaUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
