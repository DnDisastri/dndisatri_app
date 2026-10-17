<?php

namespace App\Filament\Resources\BaseItems\Schemas;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\Coins;
use App\Enums\ArmorType;
use App\Enums\DamageType;
use App\Enums\EquipmentSlot;
use App\Enums\RulesEdition;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BaseItemForm
{
    public static function configure(Schema $schema): Schema
    {
        $di = fn (EquipmentSlot $slot) => fn (Get $get) => $get('kind') === $slot->value || $get('kind') === $slot;

        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Rinominandolo, gli oggetti degli zaini e dell\'Emporio che lo usano lo seguono.'),

                    Select::make('kind')
                        ->label('Che cos\'è')
                        ->options(collect(EquipmentSlot::cases())->mapWithKeys(fn (EquipmentSlot $s) => [$s->value => $s->label()])->all())
                        ->required()
                        ->live()
                        ->disabledOn('edit'),

                    Select::make('rules_edition')
                        ->label('Regole')
                        ->options(RulesEdition::options())
                        ->default(RulesEdition::Classic->value)
                        ->required()
                        ->selectablePlaceholder(false),

                    TextInput::make('price_cp')
                        ->label('Prezzo')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->maxValue(Coins::MAX / 100)
                        ->suffix('mo')
                        ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100)
                        ->dehydrateStateUsing(fn ($state) => blank($state) ? null : (int) round((float) $state * 100))
                        ->helperText('Proposto come valore quando lo si trova in un bottino.'),

                    TextInput::make('position')
                        ->label('Posizione')
                        ->numeric()
                        ->default(99)
                        ->helperText('Ordine nei menu.'),
                ]),

            Section::make('Arma')
                ->visible($di(EquipmentSlot::Weapon))
                ->columns(3)
                ->schema([
                    TextInput::make('damage')
                        ->label('Danni')
                        ->placeholder('1d8')
                        ->required()
                        ->regex('/^\d+d\d+$/')
                        ->validationMessages(['regex' => 'Scrivilo come 1d8 o 2d6.']),

                    Select::make('damage_type')
                        ->label('Tipo di danno')
                        ->options(DamageType::options()),

                    Select::make('ability')
                        ->label('Caratteristica')
                        ->options([Ability::Str->value => Ability::Str->fullName(), Ability::Dex->value => Ability::Dex->fullName()])
                        ->default(Ability::Str->value)
                        ->required()
                        ->helperText('Con la proprietà Accurata vale la migliore fra le due.'),

                    TagsInput::make('properties')
                        ->label('Proprietà')
                        ->suggestions(['Accurata', 'Leggera', 'Pesante', 'A due mani', 'Portata', 'Ricarica', 'Speciale', 'Lancio (6/18 m)', 'Munizioni (24/96 m)', 'Versatile (1d10)'])
                        ->helperText('Scrivi e premi Invio. Accurata cambia l\'attacco, le altre sono da leggere.')
                        ->columnSpanFull(),
                ]),

            Section::make('Armatura')
                ->visible($di(EquipmentSlot::Armor))
                ->columns(2)
                ->schema([
                    Select::make('armor_type')
                        ->label('Tipo')
                        ->options(ArmorType::options())
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (?string $state, Set $set) => $set('dex_cap', ArmorType::tryFrom((string) $state)?->defaultDexCap())),

                    TextInput::make('armor_base')
                        ->label('CA base')
                        ->numeric()
                        ->minValue(10)
                        ->maxValue(25)
                        ->required(),

                    TextInput::make('dex_cap')
                        ->label('Tetto di Destrezza')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10)
                        ->helperText('Vuoto = tutta la Destrezza (leggere), 2 per le medie, 0 = niente Destrezza (pesanti).'),

                    TextInput::make('strength_required')
                        ->label('Forza richiesta')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(30)
                        ->helperText('Vuoto se non ne richiede.'),

                    Toggle::make('stealth_disadvantage')
                        ->label('Svantaggio in Furtività'),
                ]),

            Section::make('Scudo')
                ->visible($di(EquipmentSlot::Shield))
                ->schema([
                    TextInput::make('shield_bonus')
                        ->label('Bonus alla CA')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10)
                        ->default(2)
                        ->required(),
                ]),
        ])->columns(1);
    }
}
