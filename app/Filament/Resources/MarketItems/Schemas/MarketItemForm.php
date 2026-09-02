<?php

namespace App\Filament\Resources\MarketItems\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MarketItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("L'articolo")
                ->description('Cosa si vende, a che prezzo e in quante copie.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('category')
                        ->label('Categoria')
                        ->maxLength(255)
                        ->datalist([
                            'Armi',
                            'Armature',
                            'Pozioni',
                            'Pozioni Rare',
                            'Veleni',
                            'Kit e Strumenti',
                            'Oggetti Magici',
                            'Equipaggiamento',
                        ]),

                    TextInput::make('price')
                        ->label('Prezzo')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->suffix('mo')
                        ->helperText("Prezzo in monete d'oro."),

                    Toggle::make('is_unlimited')
                        ->label('Scorte illimitate')
                        ->default(false),

                    TextInput::make('stock')
                        ->label('Scorte')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Ignorato se le scorte sono illimitate.'),

                    Textarea::make('details')
                        ->label('Dettagli')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ])->columns(1);
    }
}
