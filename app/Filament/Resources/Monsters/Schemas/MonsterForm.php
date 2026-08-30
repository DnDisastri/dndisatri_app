<?php

namespace App\Filament\Resources\Monsters\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Il modulo con cui si scrive un mostro.
 *
 * Due sezioni: **l'essenziale** che serve a farlo combattere — quello che nel
 * tracker si vede in riga — e lo **statblock esteso**, che nel tracker si apre
 * solo al clic. Riempire l'essenziale basta; il resto vale la pena per i mostri
 * che si rivedono.
 */
class MonsterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('L\'essenziale')
                ->description('Quello che serve per farlo combattere, e che si vede in riga nel tracker.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('hp')
                        ->label('Punti ferita')
                        ->numeric()
                        ->required()
                        ->minValue(1),

                    TextInput::make('ac')
                        ->label('Classe Armatura')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(40),

                    TextInput::make('speed')
                        ->label('Velocità')
                        ->maxLength(255)
                        ->placeholder('9 m'),
                ])
                ->columns(3),

            Section::make('Statblock esteso')
                ->description('Facoltativo: gli attacchi e il resto, per il modale che si apre al clic.')
                ->schema([
                    Repeater::make('attacks')
                        ->label('Attacchi')
                        ->schema([
                            TextInput::make('nome')->label('Nome')->required(),
                            TextInput::make('bonus')->label('Per colpire')->placeholder('+4'),
                            TextInput::make('danni')->label('Danni')->placeholder('1d6+2'),
                        ])
                        ->columns(3)
                        ->addActionLabel('Aggiungi attacco')
                        ->reorderable(false)
                        // Parte vuoto: un mostro senza attacchi (una trappola,
                        // uno sciame) è legittimo, e una riga vuota col nome
                        // obbligatorio bloccherebbe il salvataggio a chi non la
                        // vuole.
                        ->defaultItems(0)
                        ->columnSpanFull(),

                    Textarea::make('traits')
                        ->label('Tratti e note')
                        ->rows(5)
                        ->placeholder('Resistenze, immunità, azioni speciali, tiri salvezza…')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
