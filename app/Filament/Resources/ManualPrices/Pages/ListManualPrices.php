<?php

namespace App\Filament\Resources\ManualPrices\Pages;

use App\Filament\Resources\ManualPrices\ManualPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManualPrices extends ListRecords
{
    protected static string $resource = ManualPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuova voce'),
        ];
    }
}
