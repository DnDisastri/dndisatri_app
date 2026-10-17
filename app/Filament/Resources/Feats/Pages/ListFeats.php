<?php

namespace App\Filament\Resources\Feats\Pages;

use App\Filament\Resources\Feats\FeatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeats extends ListRecords
{
    protected static string $resource = FeatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuovo talento'),
        ];
    }
}
