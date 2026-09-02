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
 * Il modulo della serata: tavolo, numero, quando, e il resoconto.
 *
 * Il resoconto non è mass-assignable: le pagine Create/Edit lo salvano tramite
 * `WriteRecap`, così il testo si porta dietro chi l'ha scritto e quando, invece
 * di restare senza firma.
 */
class GameSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('La serata')
                    ->description('Le presenze si segnano dalla pagina della serata, a fine partita.')
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
                    ])
                    ->columns(2),

                Section::make('Il resoconto')
                    ->description('Il racconto della serata che leggono i giocatori. Di solito si scrive dalla pagina della serata, ma puoi scriverlo o correggerlo anche qui.')
                    ->schema([
                        Textarea::make('recap')
                            ->label('Resoconto')
                            ->rows(10)
                            ->maxLength(20000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Le presenze')
                    ->description('Chi c\'era e con quale personaggio. Si può segnare anche dopo, non solo a fine serata. Il personaggio è facoltativo.')
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
