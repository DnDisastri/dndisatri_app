<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        // Il campo del DM è disabilitato per chi non è admin, e i campi
        // disabilitati non arrivano dal browser: qui si chiude il buco,
        // perché altrimenti un DM potrebbe aprire un tavolo intestato ad altri
        // manomettendo la richiesta.
        if (! auth()->user()->isAdmin()) {
            $data['dm_id'] = auth()->id();
        }

        return $data;
    }
}
