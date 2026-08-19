<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Filament\Resources\GameSessions\GameSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGameSession extends CreateRecord
{
    protected static string $resource = GameSessionResource::class;

    /**
     * Chi fissa la serata è chi sta scrivendo, e non un numero da digitare.
     * Prima `created_by` era un campo del modulo dove si batteva l'id a mano.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
