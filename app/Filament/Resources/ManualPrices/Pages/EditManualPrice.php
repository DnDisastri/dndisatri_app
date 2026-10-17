<?php

namespace App\Filament\Resources\ManualPrices\Pages;

use App\Filament\Resources\ManualPrices\ManualPriceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditManualPrice extends EditRecord
{
    protected static string $resource = ManualPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
