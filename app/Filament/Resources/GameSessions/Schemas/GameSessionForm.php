<?php

namespace App\Filament\Resources\GameSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Fissare una serata è lavoro da scrivania: che tavolo, che numero, quando.
 *
 * **Il resoconto non è più qui.** Si scrive sulla pagina della serata (M13),
 * dove i giocatori lo leggeranno. Scriverlo da un modulo amministrativo non
 * era solo scomodo: saltava `WriteRecap`, che è l'unico punto in cui il testo
 * si porta dietro chi l'ha scritto e quando. Con la casella qui il racconto
 * restava senza firma, oppure la firma andava digitata a mano — c'erano
 * davvero un campo numerico per l'id dell'autore e un selettore di data.
 *
 * È lo stesso motivo per cui `completed_at` e `closed_at` sono spariti dal
 * modulo della quest.
 */
class GameSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('La serata')
                    ->description('Il resoconto e le presenze si segnano dalla pagina della serata, a fine partita.')
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
            ]);
    }
}
