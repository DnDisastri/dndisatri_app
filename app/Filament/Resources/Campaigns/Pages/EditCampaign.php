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
     * Solo un admin può cambiare a chi appartiene un tavolo. Il campo è già
     * disabilitato nel modulo, ma un campo disabilitato non è una difesa:
     * la decisione va presa qui, dove il browser non arriva.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! auth()->user()->isAdmin()) {
            $data['dm_id'] = $this->record->dm_id;
        }

        return $data;
    }
}
