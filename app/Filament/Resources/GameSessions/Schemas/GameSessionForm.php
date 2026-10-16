<?php

namespace App\Filament\Resources\GameSessions\Schemas;

use App\Enums\SpecialSessionType;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Il modulo della sessione. Resoconto e presenze non sono mass-assignable:
 * le pagine Create/Edit li salvano con `WriteRecap` e `RecordAttendance`.
 */
class GameSessionForm
{
    /** La campagna scelta è quella delle sessioni speciali. */
    private static function speciale(Get $get): bool
    {
        return (bool) Campaign::query()->whereKey($get('campaign_id'))->value('is_special');
    }

    /** @return array<int, string> i tavoli già salvati della sessione aperta */
    private static function tavoli(object $livewire): array
    {
        $sessione = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

        return $sessione instanceof GameSession ? $sessione->tables->pluck('name', 'id')->all() : [];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('La sessione')
                    ->description('Le presenze si segnano dalla pagina della sessione, a fine partita.')
                    ->schema([
                        Select::make('campaign_id')
                            ->label('Campagna')
                            ->relationship('campaign', 'title')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),

                        Select::make('special_type')
                            ->label('Tipo di sessione speciale')
                            ->options(SpecialSessionType::options())
                            ->default(SpecialSessionType::Opening->value)
                            ->required(fn (Get $get) => self::speciale($get))
                            ->visible(fn (Get $get) => self::speciale($get)),

                        TextInput::make('season')
                            ->label('Stagione')
                            ->numeric()
                            ->minValue(1)
                            ->default(fn () => max(Campaign::seasons() ?: [1]))
                            ->required(fn (Get $get) => self::speciale($get))
                            ->visible(fn (Get $get) => self::speciale($get)),

                        Select::make('dms')
                            ->label('Altri DM')
                            ->helperText('Co-master o sostituti, oltre al DM della campagna. Non possono prenotarsi a questa sessione.')
                            ->relationship('dms', 'name', fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_DM)))
                            ->multiple()
                            ->searchable()
                            ->preload(),

                        TextInput::make('number')
                            ->label('Numero')
                            ->helperText('La progressione dentro la campagna. Si può lasciare vuoto.')
                            ->numeric()
                            ->hidden(fn (Get $get) => self::speciale($get)),

                        TextInput::make('title')
                            ->label('Titolo')
                            ->helperText('«La Torre Nera». Facoltativo: senza, resta «Sessione 12».')
                            ->maxLength(255),

                        DateTimePicker::make('played_at')
                            ->label('Quando si gioca')
                            ->seconds(false)
                            ->required(),

                        TextInput::make('max_players')
                            ->label('Posti')
                            ->helperText('I posti da offrire. Si può chiedere un posto anche quando sono pieni. Con i tavoli si calcola da solo.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->default(6)
                            ->required(),

                        TextInput::make('min_players')
                            ->label('Minimo')
                            ->helperText('Sotto questo numero forse non vale la pena giocare. Non blocca niente.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->lte('max_players')
                            ->default(3)
                            ->required(),

                        Toggle::make('notify_players')
                            ->label('Avvisa i giocatori')
                            ->helperText('Solo per una sessione futura: quelle già giocate non avvisano comunque.')
                            ->default(true)
                            ->dehydrated(false)
                            ->visibleOn('create'),
                    ])
                    ->columns(2),

                // Solo per la campagna speciale: la multitavolo. Ci si prenota alla sessione, poi i DM assegnano il tavolo.
                Section::make('I tavoli')
                    ->description('Per una multitavolo: ogni tavolo ha i suoi posti e i suoi DM. I posti della sessione sono la somma. Per una speciale con un solo tavolo lascia vuoto.')
                    ->visible(fn (Get $get) => self::speciale($get))
                    ->schema([
                        Repeater::make('tables')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('position')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(60)
                                    ->default(fn (Get $get) => 'Tavolo '.(count($get('../../tables') ?? []) ?: 1)),

                                TextInput::make('seats')
                                    ->label('Posti')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(12)
                                    ->default(5)
                                    ->required(),

                                Select::make('dms')
                                    ->label('DM del tavolo')
                                    ->relationship('dms', 'name', fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_DM)))
                                    ->multiple()
                                    ->searchable()
                                    ->preload(),
                            ])
                            ->columns(3)
                            ->addActionLabel('Aggiungi un tavolo')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                Section::make('Il resoconto')
                    ->description('Il racconto della sessione che leggono i giocatori. Di solito si scrive dalla pagina della sessione, ma puoi scriverlo o correggerlo anche qui.')
                    ->schema([
                        Textarea::make('recap')
                            ->label('Resoconto')
                            ->rows(10)
                            ->maxLength(fn (?GameSession $record) => GameSession::recapLimit($record?->recap))
                            ->live(debounce: 500)
                            ->hint(fn (?string $state, ?GameSession $record) => mb_strlen((string) $state).' / '.number_format(GameSession::recapLimit($record?->recap), 0, ',', '.'))
                            ->columnSpanFull(),
                    ]),

                Section::make('Le presenze')
                    ->description('Chi c\'era e con quale personaggio. Si può segnare anche dopo, non solo a fine sessione. Il personaggio è facoltativo.')
                    ->schema([
                        Repeater::make('presenze')
                            ->hiddenLabel()
                            ->schema([
                                Select::make('user_id')
                                    ->label('Giocatore')
                                    ->options(fn () => User::visibleToPlayers()->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->distinct()
                                    ->live()
                                    // Cambiando giocatore, il personaggio scelto
                                    // prima non è più suo: si azzera.
                                    ->afterStateUpdated(fn ($set) => $set('character_id', null)),

                                Select::make('character_id')
                                    ->label('Personaggio')
                                    ->options(fn (Get $get) => $get('user_id')
                                        ? Character::query()->where('user_id', $get('user_id'))->orderBy('name')->pluck('name', 'id')
                                        : [])
                                    ->searchable()
                                    ->placeholder('Nessuno (ospite o DM)'),

                                // Solo dopo aver salvato i tavoli: prima non hanno un id.
                                Select::make('session_table_id')
                                    ->label('Tavolo')
                                    ->options(fn ($livewire) => self::tavoli($livewire))
                                    ->visible(fn ($livewire) => self::tavoli($livewire) !== [])
                                    ->placeholder('Nessuno'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Aggiungi presente')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ])->columns(1);
    }
}
