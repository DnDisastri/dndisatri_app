<?php

namespace App\Filament\Resources\DmRules\Pages;

use App\Filament\Resources\DmRules\DmRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDmRules extends ListRecords
{
    protected static string $resource = DmRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
