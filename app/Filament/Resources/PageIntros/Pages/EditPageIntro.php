<?php

namespace App\Filament\Resources\PageIntros\Pages;

use App\Filament\Resources\PageIntros\PageIntroResource;
use Filament\Resources\Pages\EditRecord;

class EditPageIntro extends EditRecord
{
    protected static string $resource = PageIntroResource::class;

    public function getTitle(): string
    {
        return 'Introduzione: '.$this->getRecord()->page->label();
    }
}
