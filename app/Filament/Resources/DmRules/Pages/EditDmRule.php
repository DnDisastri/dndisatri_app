<?php

namespace App\Filament\Resources\DmRules\Pages;

use App\Filament\Resources\DmRules\DmRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDmRule extends EditRecord
{
    protected static string $resource = DmRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
