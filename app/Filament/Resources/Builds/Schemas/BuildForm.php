<?php

namespace App\Filament\Resources\Builds\Schemas;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\PointBuy;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Il modulo con cui un dungeon master scrive una build.
 *
 * Due sezioni, e la divisione conta: sopra c'è **il consiglio** — quello che si
 * legge — sotto **il personaggio**, cioè i dati che finiranno davvero nella
 * creazione guidata di chi preme «usa questa build».
 *
 * Le scelte del personaggio dipendono tutte dalla classe, esattamente come nel
 * mago della creazione: cambiando classe cambiano abilità, sottoclassi,
 * equipaggiamento e incantesimi.
 */
class BuildForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Il consiglio')
                ->description('Quello che legge chi sta cercando un\'idea.')
                ->schema([
                    TextInput::make('title')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $context) {
                            if ($context === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('Link')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Si scrive da solo dal titolo. Modificalo solo se vuoi: compare nel link della build.'),

                    TextInput::make('tag')
                        ->label('Etichetta')
                        ->maxLength(255)
                        ->placeholder('Semplice · Robusto')
                        ->helperText('Due parole che dicono come si gioca.'),

                    TextInput::make('abilities_advice')
                        ->label('Su cosa puntare')
                        ->maxLength(255)
                        ->placeholder('FOR e COS')
                        ->helperText('A parole: vale anche ai livelli successivi, dove i numeri cambiano.'),

                    Textarea::make('summary')
                        ->label('In breve')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('La riga che compare sulla card.'),

                    Textarea::make('body')
                        ->label('Perché funziona')
                        ->rows(6)
                        ->columnSpanFull(),

                    Textarea::make('progression')
                        ->label('Come cresce')
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('Cosa prendere salendo di livello. Resta un consiglio scritto: non viene applicato.'),

                    FileUpload::make('cover_path')
                        ->label('Immagine')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->disk('public')
                        ->directory('build')
                        ->maxSize(4096),

                    DateTimePicker::make('published_at')
                        ->label('Pubblicata il')
                        ->helperText('Vuoto = bozza. Una data futura la tiene nascosta fino a quel giorno.'),
                ])
                ->columns(2),

            Section::make('Il personaggio di 1°')
                ->description('Quello che si trova già compilato chi usa questa build. Lasciando vuoto, resterà da scegliere.')
                ->schema([
                    Select::make('class')
                        ->label('Classe')
                        ->options(fn () => ClassRules::names()->mapWithKeys(fn ($n) => [$n => $n]))
                        ->required()
                        ->live()
                        // Cambiando classe le scelte di prima non valgono più:
                        // si azzerano invece di restare lì a mentire.
                        ->afterStateUpdated(function ($set) {
                            $set('subclass', null);
                            $set('skills', []);
                            $set('spells', []);
                            $set('equipment', []);
                        }),

                    Select::make('subclass')
                        ->label('Sottoclasse')
                        ->options(fn (Get $get) => collect(ClassRules::subclasses($get('class')))
                            ->mapWithKeys(fn ($s) => [$s => $s]))
                        ->searchable(),

                    Select::make('species')
                        ->label('Specie')
                        ->options(fn () => collect(array_keys(config('dnd.species', [])))
                            ->mapWithKeys(fn ($s) => [$s => $s]))
                        ->searchable(),

                    Select::make('background')
                        ->label('Background')
                        ->options(fn () => collect(array_keys(config('dnd.backgrounds.list', [])))
                            ->mapWithKeys(fn ($b) => [$b => $b]))
                        ->searchable(),

                    // I punteggi **comprati**, prima dei bonus di specie: sono
                    // quelli che il point buy sa rimettere sui suoi cursori.
                    Section::make('Caratteristiche')
                        ->description('Punteggi comprati col point buy, prima dei bonus di specie. Budget: '.PointBuy::budget().' punti.')
                        ->schema(
                            collect(Ability::cases())->map(fn (Ability $ability) => TextInput::make("scores.{$ability->value}")
                                ->label($ability->label())
                                ->numeric()
                                ->minValue(PointBuy::MIN_SCORE)
                                ->maxValue(PointBuy::MAX_SCORE)
                            )->all()
                        )
                        ->columns(6)
                        ->columnSpanFull(),

                    CheckboxList::make('skills')
                        ->label(fn (Get $get) => 'Abilità'.($get('class')
                            ? ' (se ne scelgono '.ClassRules::skillCount($get('class')).')'
                            : ''))
                        ->options(fn (Get $get) => collect(ClassRules::skillChoices($get('class')))
                            ->mapWithKeys(fn ($key) => [$key => config("dnd.character.skill_names.{$key}", $key)]))
                        ->columns(2)
                        ->columnSpanFull(),

                    // L'equipaggiamento iniziale è una serie di alternative
                    // A/B, e si conserva come **indici**: è la forma che il
                    // mago della creazione sa rimettere nei suoi pulsanti.
                    Section::make('Equipaggiamento')
                        ->schema(fn (Get $get) => collect(config("dnd.equipment.{$get('class')}.choices", []))
                            ->map(fn (array $choice, int $index) => Select::make("equipment.{$index}")
                                ->label($choice['label'] ?? 'Scelta '.($index + 1))
                                ->options(collect($choice['options'] ?? [])
                                    ->mapWithKeys(fn (array $option, int $i) => [$i => $option['label'] ?? 'Opzione '.($i + 1)]))
                            )->all())
                        ->columns(2)
                        ->columnSpanFull()
                        ->visible(fn (Get $get) => config("dnd.equipment.{$get('class')}.choices", []) !== []),

                    CheckboxList::make('spells')
                        ->label('Incantesimi')
                        ->options(fn (Get $get) => collect(ClassRules::spellList($get('class')))
                            ->mapWithKeys(fn ($spell) => [$spell => $spell]))
                        ->columns(2)
                        ->columnSpanFull()
                        ->visible(fn (Get $get) => ClassRules::spellList($get('class')) !== []),
                ])
                ->columns(2),
        ])->columns(1);
    }
}
