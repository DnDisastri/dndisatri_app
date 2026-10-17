<?php

namespace App\Filament\Resources\ClassFeatures\Pages;

use App\Filament\Resources\ClassFeatures\ClassFeatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClassFeatures extends ListRecords
{
    protected static string $resource = ClassFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuovo privilegio'),
        ];
    }
}
