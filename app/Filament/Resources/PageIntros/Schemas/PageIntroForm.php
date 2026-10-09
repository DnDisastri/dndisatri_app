<?php

namespace App\Filament\Resources\PageIntros\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageIntroForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Testo')
                ->schema([
                    Textarea::make('body')
                        ->label('Introduzione')
                        ->required()
                        ->maxLength(500)
                        ->rows(4)
                        ->helperText('Una o due frasi sotto il titolo della pagina: dove sei e cosa ci trovi. Gli a capo si vedono anche nella pagina.')
                        ->columnSpanFull(),
                ]),
        ])->columns(1);
    }
}
