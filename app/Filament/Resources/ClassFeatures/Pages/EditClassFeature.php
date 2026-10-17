<?php

namespace App\Filament\Resources\ClassFeatures\Pages;

use App\Filament\Resources\ClassFeatures\ClassFeatureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClassFeature extends EditRecord
{
    protected static string $resource = ClassFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
