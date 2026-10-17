<?php

namespace App\Filament\Resources\GameClasses\Schemas;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\CasterType;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\ItemCatalogue;
use App\Enums\RulesEdition;
use App\Models\CharacterItem;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class GameClassForm
{
    public static function configure(Schema $schema): Schema
    {
        $caratteristiche = collect(Ability::cases())->mapWithKeys(fn (Ability $a) => [$a->value => $a->fullName()])->all();
        $abilita = config('dnd.character.skill_names', []);
        $lancia = fn (Get $get) => ! in_array($get('caster_type'), [null, '', CasterType::None->value, CasterType::None], true);

        return $schema->components([
            Section::make()
                ->columns(3)
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit')
                        ->helperText('I personaggi la indicano per nome: dopo averla creata non si cambia.'),

                    Select::make('rules_edition')
                        ->label('Regole')
                        ->options(RulesEdition::options())
                        ->default(RulesEdition::Classic->value)
                        ->required()
                        ->selectablePlaceholder(false),

                    Select::make('hit_die')
                        ->label('Dado vita')
                        ->options([6 => 'd6', 8 => 'd8', 10 => 'd10', 12 => 'd12'])
                        ->default(8)
                        ->required(),

                    CheckboxList::make('saves')
                        ->label('Tiri salvezza competenti')
                        ->options($caratteristiche)
                        ->columns(3)
                        ->required()
                        ->maxItems(2)
                        ->helperText('Due. Valgono solo per la prima classe del personaggio.')
                        ->columnSpan(2),

                    TextInput::make('position')
                        ->label('Posizione')
                        ->numeric()
                        ->default(99)
                        ->helperText('Ordine nella creazione.'),
                ]),

            Section::make('Abilità e competenze')
                ->columns(2)
                ->schema([
                    TextInput::make('skill_count')
                        ->label('Abilità da scegliere in creazione')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(6)
                        ->default(2)
                        ->required(),

                    CheckboxList::make('skill_choices')
                        ->label('Fra quali')
                        ->options($abilita)
                        ->columns(3)
                        ->bulkToggleable()
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('proficiencies')
                        ->label('Competenze')
                        ->rows(2)
                        ->helperText('Armature, armi, strumenti: si leggono nella creazione.'),

                    Textarea::make('equipment_summary')
                        ->label('Equipaggiamento in breve')
                        ->rows(2)
                        ->helperText('Il riassunto che compare nelle build.'),
                ]),

            Section::make('Incantesimi')
                ->columns(3)
                ->schema([
                    Select::make('caster_type')
                        ->label('Che incantatore è')
                        ->options(CasterType::options())
                        ->default(CasterType::None->value)
                        ->required()
                        ->live()
                        ->helperText('Decide gli slot e come conta nel multiclasse.')
                        ->columnSpan(2),

                    Select::make('spell_ability')
                        ->label('Caratteristica')
                        ->options($caratteristiche)
                        ->visible($lancia)
                        ->required($lancia),

                    TextInput::make('cantrips_at_1')
                        ->label('Trucchetti al 1° livello')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->visible($lancia),

                    TextInput::make('spells_at_1')
                        ->label('Incantesimi al 1° livello')
                        ->numeric()
                        ->minValue(0)
                        ->visible($lancia)
                        ->helperText('Vuoto per chi prepara: il numero lo dà la regola qui sotto.'),

                    Toggle::make('prepares')
                        ->label('Prepara gli incantesimi')
                        ->live()
                        ->visible($lancia),

                    TextInput::make('prepare_from_level')
                        ->label('Prepara dal livello')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(1)
                        ->visible(fn (Get $get) => $lancia($get) && $get('prepares')),

                    Toggle::make('prepare_half_level')
                        ->label('Con metà livello (per difetto)')
                        ->helperText('Spento: caratteristica + livello. Acceso: caratteristica + metà livello.')
                        ->visible(fn (Get $get) => $lancia($get) && $get('prepares')),

                    Toggle::make('swaps_spells')
                        ->label('Sostituisce un incantesimo salendo di livello')
                        ->visible($lancia),

                    Toggle::make('copies_scrolls')
                        ->label('Copia incantesimi dalle pergamene')
                        ->visible($lancia),

                    CheckboxList::make('spell_list')
                        ->label('Lista degli incantesimi')
                        ->options(fn () => collect(array_keys(config('dnd.spells.library', [])))
                            ->mapWithKeys(fn (string $nome) => [$nome => $nome.' ('.(ClassRules::spellLevel($nome) ?: 'trucchetto').')'])
                            ->sort()
                            ->all())
                        ->columns(3)
                        ->searchable()
                        ->bulkToggleable()
                        ->helperText('Fra il numero è il livello. Mancano incantesimi? Vanno aggiunti alla libreria.')
                        ->visible($lancia)
                        ->columnSpanFull(),
                ]),

            Section::make('Sottoclasse e multiclasse')
                ->columns(2)
                ->schema([
                    TextInput::make('subclass_level')
                        ->label('Sceglie la sottoclasse al livello')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(3)
                        ->required()
                        ->helperText('Le sottoclassi si aggiungono dalla loro pagina.'),

                    TextInput::make('multiclass_skill_count')
                        ->label('Abilità entrando come seconda classe')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(3)
                        ->default(0)
                        ->live(),

                    // Ogni gruppo: basta una delle caratteristiche spuntate; fra gruppi servono tutti.
                    Repeater::make('multiclass_requirements')
                        ->label('Requisiti per il multiclasse (13 o più)')
                        ->schema([
                            CheckboxList::make('abilities')
                                ->hiddenLabel()
                                ->options($caratteristiche)
                                ->columns(6)
                                ->required(),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Aggiungi un requisito')
                        ->helperText('Un gruppo con Forza e Destrezza vuol dire «una delle due»; due gruppi vogliono dire «entrambe».')
                        ->columnSpanFull(),

                    CheckboxList::make('multiclass_skill_choices')
                        ->label('Fra quali abilità')
                        ->options($abilita)
                        ->columns(3)
                        ->bulkToggleable()
                        ->visible(fn (Get $get) => (int) $get('multiclass_skill_count') > 0)
                        ->columnSpanFull(),
                ]),

            Section::make('Equipaggiamento iniziale')
                ->description('Quello che il personaggio riceve in creazione, oltre al kit del background. Il primo oggetto adatto a ogni slot viene indossato da solo.')
                ->collapsible()
                ->schema([
                    Repeater::make('starting_equipment.fixed')
                        ->label('Sempre')
                        ->schema(self::oggetto())
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Aggiungi un oggetto'),

                    Repeater::make('starting_equipment.choices')
                        ->label('Scelte')
                        ->schema([
                            TextInput::make('label')
                                ->label('Cosa si sceglie')
                                ->placeholder('Arma principale')
                                ->required(),

                            Repeater::make('options')
                                ->label('Alternative')
                                ->schema([
                                    TextInput::make('label')
                                        ->label('Come si legge')
                                        ->placeholder('Ascia bipenne')
                                        ->required(),

                                    Repeater::make('items')
                                        ->label('Oggetti')
                                        ->schema(self::oggetto())
                                        ->columns(3)
                                        ->minItems(1)
                                        ->addActionLabel('Aggiungi un oggetto'),
                                ])
                                ->minItems(2)
                                ->addActionLabel('Aggiungi un\'alternativa'),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['label'] ?? null)
                        ->addActionLabel('Aggiungi una scelta'),
                ]),
        ])->columns(1);
    }

    /** @return list<Component> */
    private static function oggetto(): array
    {
        return [
            TextInput::make('name')
                ->label('Oggetto')
                ->required()
                ->maxLength(100)
                ->datalist(fn () => app(ItemCatalogue::class)->all()->keys()->all())
                ->helperText('Col nome di un\'arma o armatura del catalogo prende i suoi numeri.'),

            TextInput::make('qty')
                ->label('Quantità')
                ->numeric()
                ->minValue(1)
                ->default(1),

            Select::make('category')
                ->label('Categoria')
                ->options(array_combine(CharacterItem::CATEGORIES, CharacterItem::CATEGORIES)),
        ];
    }
}
