<?php

namespace App\Filament\Resources\Maps\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MapForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('La mappa')
                ->description("L'immagine e a quale tavolo appartiene.")
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Titolo')
                        ->required()
                        ->maxLength(255),

                    Select::make('campaign_id')
                        ->label('Campagna')
                        ->relationship('campaign', 'title')
                        ->searchable()
                        ->preload()
                        ->placeholder('Nessuna, vale per tutto il gruppo'),

                    Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(4)
                        ->columnSpanFull(),

                    FileUpload::make('image_path')
                        ->label('Immagine')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->required()
                        ->disk('public')
                        ->directory('mappe')
                        ->maxSize(8192)
                        ->columnSpanFull(),
                ]),
        ])->columns(1);
    }
}
