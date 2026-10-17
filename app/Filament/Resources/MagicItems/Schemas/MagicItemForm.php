<?php

namespace App\Filament\Resources\MagicItems\Schemas;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\Coins;
use App\Domain\Dnd\ItemEffectMode;
use App\Enums\EquipmentSlot;
use App\Enums\ItemRarity;
use App\Enums\RulesEdition;
use App\Models\CharacterItem;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class MagicItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('rules_edition', $get('rules_edition')))
                        ->validationMessages(['unique' => "Con queste regole c'è già un oggetto con questo nome."]),

                    Select::make('rules_edition')
                        ->label('Regole')
                        ->options(RulesEdition::options())
                        ->default(RulesEdition::Classic->value)
                        ->required()
                        ->selectablePlaceholder(false),

                    Select::make('rarity')
                        ->label('Rarità')
                        ->options(ItemRarity::options())
                        ->default(ItemRarity::Uncommon->value)
                        ->required(),

                    Select::make('category')
                        ->label('Categoria nello zaino')
                        ->options(array_combine(CharacterItem::CATEGORIES, CharacterItem::CATEGORIES))
                        ->default('Oggetti Magici')
                        ->required(),

                    Toggle::make('requires_attunement')
                        ->label('Richiede sintonia')
                        ->helperText('Gli effetti valgono solo in sintonia (al massimo tre oggetti).'),

                    TextInput::make('value_cp')
                        ->label('Valore')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->maxValue(Coins::MAX / 100)
                        ->suffix('mo')
                        ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100)
                        ->dehydrateStateUsing(fn ($state) => blank($state) ? null : (int) round((float) $state * 100))
                        ->helperText('Proposto nel bottino. Vuoto se non ha un prezzo.'),

                    Textarea::make('details')
                        ->label('Cosa fa')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Con parole vostre: arriva nei dettagli dell\'oggetto, dopo rarità e sintonia.')
                        ->columnSpanFull(),
                ]),

            Section::make('Arma, armatura o scudo')
                ->description('Solo se è un\'arma o un\'armatura magica: il tipo decide attacchi e CA.')
                ->columns(2)
                ->schema([
                    Select::make('base')
                        ->label('Tipo')
                        ->options(fn () => EquipmentSlot::bases())
                        ->placeholder('Lo sceglie il DM nel bottino'),

                    TextInput::make('magic_bonus')
                        ->label('Bonus magico')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(CharacterItem::MAX_MAGIC_BONUS)
                        ->default(0)
                        ->prefix('+')
                        ->required(),
                ]),

            Section::make('Effetti sulle caratteristiche')
                ->description('Quelli che la scheda calcola da sola. Il resto va nella descrizione.')
                ->schema([
                    Repeater::make('effects')
                        ->hiddenLabel()
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Aggiungi un effetto')
                        ->schema([
                            Select::make('ability')
                                ->label('Caratteristica')
                                ->options(collect(Ability::cases())->mapWithKeys(fn (Ability $a) => [$a->value => $a->fullName()])->all())
                                ->required(),

                            Select::make('mode')
                                ->label('Come')
                                ->options([
                                    ItemEffectMode::Set->value => 'Diventa (se migliora)',
                                    ItemEffectMode::Bonus->value => 'Si somma',
                                ])
                                ->default(ItemEffectMode::Set->value)
                                ->required(),

                            TextInput::make('value')
                                ->label('Valore')
                                ->numeric()
                                ->minValue(-10)
                                ->maxValue(30)
                                ->required(),
                        ]),
                ]),
        ])->columns(1);
    }
}
