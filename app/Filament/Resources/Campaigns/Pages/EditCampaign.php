<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Solo un admin cambia il DM di una campagna: il campo disabilitato non
     * basta, la richiesta si manomette.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! auth()->user()->isAdmin()) {
            $data['dm_id'] = $this->record->dm_id;
        }

        return $data;
    }
}
