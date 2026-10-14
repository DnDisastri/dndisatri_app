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

        // Il campo è disabilitato per chi non è admin, ma la richiesta si manomette:
        // senza questo un DM aprirebbe una campagna intestata a un altro.
        if (! auth()->user()->isAdmin()) {
            $data['dm_id'] = auth()->id();
            $data['is_special'] = false;
        }

        // La speciale è di tutti i DM: nessun titolare.
        if (! empty($data['is_special'])) {
            $data['dm_id'] = null;
            $data['last_season'] = null;
        }

        return $data;
    }
}
