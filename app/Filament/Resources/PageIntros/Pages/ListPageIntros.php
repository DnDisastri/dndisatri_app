<?php

namespace App\Filament\Resources\PageIntros\Pages;

use App\Filament\Resources\PageIntros\PageIntroResource;
use App\Models\PageIntro;
use Filament\Resources\Pages\ListRecords;

class ListPageIntros extends ListRecords
{
    protected static string $resource = PageIntroResource::class;

    // Una pagina nuova nell'enum compare qui senza bisogno di una migrazione.
    public function mount(): void
    {
        parent::mount();

        PageIntro::fillMissing();
    }
}
