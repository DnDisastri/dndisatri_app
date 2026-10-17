<?php

namespace App\Filament\Resources\Species\Schemas;

use App\Domain\Dnd\Ability;
use App\Enums\RulesEdition;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SpeciesForm
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
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit')
                        ->helperText('Personaggi, sottospecie e build la indicano per nome: dopo averla creata non si cambia.'),

                    Select::make('rules_edition')
                        ->label('Regole')
                        ->options(RulesEdition::options())
                        ->default(RulesEdition::Classic->value)
                        ->required()
                        ->selectablePlaceholder(false),

                    Textarea::make('description')
                        ->label('In una riga')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('La legge chi crea il personaggio, prima dei numeri.')
                        ->columnSpanFull(),

                    KeyValue::make('asi')
                        ->label('Bonus alle caratteristiche')
                        ->keyLabel('Caratteristica')
                        ->valueLabel('Bonus')
                        ->keyPlaceholder(collect(Ability::cases())->map(fn (Ability $a) => $a->value)->join(', ').' o all')
                        ->helperText('Con la chiave «all» il bonus vale per tutte, come per l\'umano.')
                        ->columnSpanFull(),

                    TextInput::make('speed')
                        ->label('Velocità in metri')
                        ->numeric()
                        ->step(0.5)
                        ->default(9)
                        ->required(),

                    TextInput::make('position')
                        ->label('Posizione')
                        ->numeric()
                        ->default(99)
                        ->helperText('Ordine nella creazione.'),

                    Textarea::make('traits')
                        ->label('Tratti')
                        ->rows(4)
                        ->helperText('Finiscono sulla scheda: scurovisione, resistenze, competenze.')
                        ->columnSpanFull(),
                ]),

            Section::make('Scelte in creazione')
                ->description('Quello che la specie fa scegliere, oltre ai bonus fissi.')
                ->columns(3)
                ->schema([
                    TextInput::make('free_bonuses')
                        ->label('+1 a scelta')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(6)
                        ->default(0)
                        ->required(),

                    TextInput::make('skill_choices')
                        ->label('Abilità a scelta')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(4)
                        ->default(0)
                        ->required(),

                    Toggle::make('grants_feat')
                        ->label('Un talento al 1° livello'),
                ]),
        ])->columns(1);
    }
}
