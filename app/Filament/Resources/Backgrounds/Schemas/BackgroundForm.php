<?php

namespace App\Filament\Resources\Backgrounds\Schemas;

use App\Domain\Dnd\Ability;
use App\Enums\RulesEdition;
use App\Models\CharacterItem;
use App\Models\Feat;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class BackgroundForm
{
    public static function configure(Schema $schema): Schema
    {
        $nel2024 = fn (Get $get) => in_array($get('rules_edition'), [RulesEdition::Revised->value, RulesEdition::Revised], true);

        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('rules_edition', $get('rules_edition')))
                        ->validationMessages(['unique' => "Con queste regole c'è già un background con questo nome."])
                        ->disabledOn('edit')
                        ->helperText('I personaggi lo indicano per nome: dopo averlo creato non si cambia.'),

                    Select::make('rules_edition')
                        ->label('Regole')
                        ->options(RulesEdition::options())
                        ->default(RulesEdition::Classic->value)
                        ->required()
                        ->live()
                        ->selectablePlaceholder(false)
                        ->disabledOn('edit'),

                    CheckboxList::make('skills')
                        ->label('Abilità')
                        ->options(config('dnd.character.skill_names', []))
                        ->columns(3)
                        ->maxItems(2)
                        ->helperText('Due, di solito. Vuoto se le sceglie il giocatore.')
                        ->columnSpanFull(),

                    TextInput::make('free_skills')
                        ->label('Abilità a scelta')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(4)
                        ->default(0)
                        ->helperText('Come il background personalizzato.'),

                    TextInput::make('gp')
                        ->label('Monete d\'oro')
                        ->numeric()
                        ->minValue(0)
                        ->default(10)
                        ->required(),

                    Textarea::make('equipment_summary')
                        ->label('Equipaggiamento in breve')
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('feature')
                        ->label('Privilegio')
                        ->rows(2)
                        ->helperText('Solo nelle regole 2014.')
                        ->visible(fn (Get $get) => ! $nel2024($get))
                        ->columnSpanFull(),
                ]),

            Section::make('Regole 2024')
                ->description('Il background dà le caratteristiche da aumentare (+2/+1 o +1/+1/+1) e un talento di origine.')
                ->visible($nel2024)
                ->columns(2)
                ->schema([
                    CheckboxList::make('ability_options')
                        ->label('Caratteristiche')
                        ->options(collect(Ability::cases())->mapWithKeys(fn (Ability $a) => [$a->value => $a->fullName()])->all())
                        ->columns(3)
                        ->minItems(3)
                        ->maxItems(3)
                        ->required($nel2024),

                    Select::make('origin_feat')
                        ->label('Talento di origine')
                        ->options(fn () => Feat::ofEdition(RulesEdition::Revised)->where('category', Feat::ORIGIN)->pluck('name', 'name')->all())
                        ->required($nel2024),
                ]),

            Section::make('Oggetti di partenza')
                ->collapsible()
                ->schema([
                    Repeater::make('starting_items')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('name')->label('Oggetto')->required()->maxLength(100),
                            TextInput::make('qty')->label('Quantità')->numeric()->minValue(1)->default(1),
                            Select::make('category')->label('Categoria')->options(array_combine(CharacterItem::CATEGORIES, CharacterItem::CATEGORIES)),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Aggiungi un oggetto'),
                ]),
        ])->columns(1);
    }
}
