<?php

namespace App\Filament\Resources\GameSessions\Schemas;

use App\Models\Character;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Il modulo della sessione. Resoconto e presenze non sono mass-assignable:
 * le pagine Create/Edit li salvano con `WriteRecap` e `RecordAttendance`.
 */
class GameSessionForm
{
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
                            ->required(),

                        TextInput::make('number')
                            ->label('Numero')
                            ->helperText('La progressione dentro la campagna. Si può lasciare vuoto.')
                            ->numeric(),

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
                            ->helperText('I posti da offrire. Si può chiedere un posto anche quando sono pieni.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->default(6)
                            ->required(),

                        TextInput::make('min_players')
                            ->label('Minimo')
                            ->helperText('Sotto questo numero forse non vale la pena giocare. Non blocca niente.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->lte('max_players')
                            ->default(3)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Il resoconto')
                    ->description('Il racconto della sessione che leggono i giocatori. Di solito si scrive dalla pagina della sessione, ma puoi scriverlo o correggerlo anche qui.')
                    ->schema([
                        Textarea::make('recap')
                            ->label('Resoconto')
                            ->rows(10)
                            ->maxLength(20000)
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
                            ])
                            ->columns(2)
                            ->addActionLabel('Aggiungi presente')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ])->columns(1);
    }
}
