<?php

namespace App\Filament\Resources\ManualPrices\Schemas;

use App\Domain\Dnd\Coins;
use App\Models\ManualPrice;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ManualPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->description('Il listino che i DM consultano nel Manuale. La percentuale della campagna si applica sopra; l\'Emporio non cambia.')
                ->columns(2)
                ->schema([
                    TextInput::make('category')
                        ->label('Categoria')
                        ->required()
                        ->maxLength(255)
                        ->datalist(fn () => ManualPrice::query()->distinct()->orderBy('category')->pluck('category')->all())
                        ->helperText('Una nuova categoria finisce in fondo al listino.'),

                    TextInput::make('name')
                        ->label('Voce')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('category', $get('category')))
                        ->validationMessages(['unique' => "In questa categoria c'è già una voce con questo nome."]),

                    TextInput::make('price_cp')
                        ->label('Prezzo')
                        ->required()
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->maxValue(Coins::MAX / 100)
                        ->suffix('mo')
                        ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100)
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100))
                        ->helperText('In monete d\'oro: 0,5 sono 5 ma, 0,01 è 1 mr.'),
                ]),
        ])->columns(1);
    }
}
