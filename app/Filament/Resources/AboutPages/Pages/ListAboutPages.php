<?php

namespace App\Filament\Resources\AboutPages\Pages;

use App\Filament\Resources\AboutPages\AboutPageResource;
use App\Models\AboutPage;
use Filament\Resources\Pages\ListRecords;

class ListAboutPages extends ListRecords
{
    protected static string $resource = AboutPageResource::class;

    // Contenuto unico: si va dritti alla modifica invece di mostrare un elenco
    // di una riga sola.
    public function mount(): void
    {
        parent::mount();

        $record = AboutPage::query()->first();

        if ($record) {
            $this->redirect(AboutPageResource::getUrl('edit', ['record' => $record]));
        }
    }
}
