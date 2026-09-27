<?php

namespace App\Filament\Resources\Subclasses\Pages;

use App\Filament\Resources\Subclasses\SubclassResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubclass extends CreateRecord
{
    protected static string $resource = SubclassResource::class;
}
