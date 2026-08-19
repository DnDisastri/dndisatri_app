<?php

namespace App\Filament\Resources\MarketItems\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MarketItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('category'),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Toggle::make('is_unlimited')
                    ->required(),
                TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('details')
                    ->columnSpanFull(),
            ]);
    }
}
