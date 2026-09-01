<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label('Nome'),

                    TextEntry::make('email')
                        ->label('Email'),

                    TextEntry::make('ruolo')
                        ->label('Ruolo')
                        ->badge()
                        ->state(fn (User $record) => match (true) {
                            $record->isAdmin() => 'Admin',
                            $record->isDm() => 'Dungeon master',
                            default => 'Giocatore',
                        }),

                    TextEntry::make('approved_at')
                        ->label('Stato')
                        ->badge()
                        ->state(fn (User $record) => $record->isApproved() ? 'Approvato' : 'In attesa')
                        ->color(fn (User $record) => $record->isApproved() ? 'success' : 'warning'),

                    TextEntry::make('email_verified_at')
                        ->label('Email verificata il')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('Non verificata'),

                    TextEntry::make('created_at')
                        ->label('Registrato il')
                        ->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }
}
