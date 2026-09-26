<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->description('Dati di accesso e stato dell\'utente.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required(),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required(),
                    TextInput::make('avatar_path')
                        ->label('Avatar')
                        ->helperText('Percorso dell\'immagine del profilo.'),
                    DateTimePicker::make('email_verified_at')
                        ->label('Email verificata il'),
                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        // In modifica il campo vuoto lascia la password invariata;
                        // obbligatoria solo alla creazione.
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create'),
                ]),
        ])->columns(1);
    }
}
