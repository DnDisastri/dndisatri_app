<?php

namespace App\Filament\Resources\MarketItems\Pages;

use App\Filament\Resources\MarketItems\MarketItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketItems extends ListRecords
{
    protected static string $resource = MarketItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
