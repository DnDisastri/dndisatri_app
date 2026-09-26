<?php

namespace App\Filament\Resources\Monsters\Pages;

use App\Filament\Resources\Monsters\MonsterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMonster extends CreateRecord
{
    protected static string $resource = MonsterResource::class;

    /** Chi l'ha scritto, per la colonna «scritto da»: il bestiario resta comune. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
