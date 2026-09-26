<?php

namespace App\Filament\Resources\Maps\Pages;

use App\Filament\Resources\Maps\MapResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMap extends CreateRecord
{
    protected static string $resource = MapResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();

        return $data;
    }
}
