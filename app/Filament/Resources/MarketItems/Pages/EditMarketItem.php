<?php

namespace App\Filament\Resources\MarketItems\Pages;

use App\Filament\Resources\MarketItems\MarketItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketItem extends EditRecord
{
    protected static string $resource = MarketItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
