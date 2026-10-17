<?php

namespace App\Filament\Resources\ClassFeatures\Schemas;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ClassRules;
use App\Enums\ActionCost;
use App\Enums\FeatureUsesFormula;
use App\Enums\RestType;
use App\Models\ClassFeature;
use App\Models\Subclass;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ClassFeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        $formula = fn (Get $get) => $get('uses_formula') instanceof FeatureUsesFormula ? $get('uses_formula') : FeatureUsesFormula::tryFrom((string) $get('uses_formula'));

        return $schema->components([
            Section::make()
                ->columns(3)
                ->schema([
                    Select::make('owner_type')
                        ->label('Di una')
                        ->options([ClassFeature::CLASS_OWNER => 'Classe', ClassFeature::SUBCLASS_OWNER => 'Sottoclasse'])
                        ->default(ClassFeature::CLASS_OWNER)
                        ->required()
                        ->live(),

                    Select::make('owner')
                        ->label(fn (Get $get) => $get('owner_type') === ClassFeature::SUBCLASS_OWNER ? 'Sottoclasse' : 'Classe')
                        ->options(fn (Get $get) => $get('owner_type') === ClassFeature::SUBCLASS_OWNER
                            ? Subclass::query()->orderBy('class')->orderBy('name')->get()->mapWithKeys(fn (Subclass $s) => [$s->name => "{$s->name} ({$s->class})"])->all()
                            : ClassRules::names()->mapWithKeys(fn (string $c) => [$c => $c])->all())
                        ->searchable()
                        ->required(),

                    TextInput::make('level')
                        ->label('Dal livello')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->required()
                        ->helperText('Il livello in quella classe.'),

                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    Select::make('cost')
                        ->label('Costo')
                        ->options(collect(ActionCost::cases())->mapWithKeys(fn (ActionCost $c) => [$c->value => $c->label()])->all())
                        ->required(),

                    Textarea::make('text')
                        ->label('Cosa fa')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),

                    Toggle::make('on_turn')
                        ->label('Nel Turno')
                        ->default(true)
                        ->helperText('Spento: va in Storia (privilegi sociali o d\'esplorazione).'),

                    Toggle::make('our_name')
                        ->label('Nome tradotto da noi'),

                    Toggle::make('to_check')
                        ->label('Da ricontrollare sul manuale'),
                ]),

            Section::make('Usi')
                ->description('I numeri fanno comparire il contatore in scheda; la frase la legge il giocatore.')
                ->columns(3)
                ->schema([
                    TextInput::make('uses_text')
                        ->label('Gli usi, a parole')
                        ->maxLength(255)
                        ->placeholder('Una volta; torna col riposo breve o lungo.')
                        ->columnSpanFull(),

                    Select::make('uses_formula')
                        ->label('Quanti')
                        ->options(FeatureUsesFormula::options())
                        ->placeholder('Non si contano')
                        ->live(),

                    TextInput::make('uses_value')
                        ->label('Numero')
                        ->numeric()
                        ->minValue(1)
                        ->visible(fn (Get $get) => $formula($get) === FeatureUsesFormula::Fixed)
                        ->required(fn (Get $get) => $formula($get) === FeatureUsesFormula::Fixed),

                    Select::make('uses_ability')
                        ->label('Caratteristica')
                        ->options(collect(Ability::cases())->mapWithKeys(fn (Ability $a) => [$a->value => $a->fullName()])->all())
                        ->visible(fn (Get $get) => in_array($formula($get), [FeatureUsesFormula::Ability, FeatureUsesFormula::AbilityPlusOne], true))
                        ->required(fn (Get $get) => in_array($formula($get), [FeatureUsesFormula::Ability, FeatureUsesFormula::AbilityPlusOne], true)),

                    Select::make('recharge')
                        ->label('Tornano col')
                        ->options([RestType::Short->value => 'Riposo breve (e lungo)', RestType::Long->value => 'Riposo lungo'])
                        ->placeholder('Non tornano da soli')
                        ->visible(fn (Get $get) => $formula($get) !== null),

                    TextInput::make('short_rest_from_level')
                        ->label('Anche col breve dal livello')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->helperText('Come l\'Ispirazione bardica dal 5º.')
                        ->visible(fn (Get $get) => $formula($get) !== null && in_array($get('recharge'), [RestType::Long->value, RestType::Long], true)),

                    // Ogni riga: da quel livello nella classe, tanti usi. 0 vuol dire senza limite.
                    Repeater::make('uses_table')
                        ->label('Usi per livello')
                        ->schema([
                            TextInput::make('level')->label('Dal livello')->numeric()->minValue(1)->maxValue(20)->required(),
                            TextInput::make('count')->label('Usi')->numeric()->minValue(0)->required()->helperText('0 = senza limite'),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Aggiungi un livello')
                        ->visible(fn (Get $get) => $formula($get) === FeatureUsesFormula::Table)
                        ->columnSpanFull(),

                    TextInput::make('spends')
                        ->label('Consuma la riserva di')
                        ->placeholder('Punti ki')
                        ->helperText('Il nome di un altro privilegio della stessa classe, come Punti ki o Incanalare divinità.')
                        ->visible(fn (Get $get) => $formula($get) === null)
                        ->columnSpan(2),

                    TextInput::make('spends_amount')
                        ->label('Quanti')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->visible(fn (Get $get) => $formula($get) === null && filled($get('spends'))),
                ]),

            Section::make('Effetto acceso')
                ->description('Per i privilegi che si attivano e restano accesi, come l\'Ira: in scheda compare un pulsante e, acceso, un riquadro con l\'effetto.')
                ->schema([
                    Toggle::make('can_activate')
                        ->label('Si attiva')
                        ->live(),

                    Textarea::make('active_text')
                        ->label('Mentre è acceso')
                        ->rows(2)
                        ->visible(fn (Get $get) => (bool) $get('can_activate')),
                ]),
        ])->columns(1);
    }
}
