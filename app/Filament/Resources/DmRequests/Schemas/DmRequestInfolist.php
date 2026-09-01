<?php

namespace App\Filament\Resources\DmRequests\Schemas;

use App\Enums\PendingChangeStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DmRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('La richiesta')
                ->schema([
                    TextEntry::make('user.name')->label('Chi ha chiesto'),
                    TextEntry::make('user.email')->label('Email'),
                    TextEntry::make('created_at')->label('Ricevuta')->dateTime('d/m/Y H:i'),
                    TextEntry::make('message')
                        ->label('Messaggio')
                        ->placeholder('Nessun messaggio')
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('La decisione')
                ->schema([
                    TextEntry::make('status')
                        ->label('Stato')
                        ->badge()
                        ->formatStateUsing(fn (PendingChangeStatus $state) => $state->label())
                        ->color(fn (PendingChangeStatus $state) => match ($state) {
                            PendingChangeStatus::Pending => 'warning',
                            PendingChangeStatus::Approved => 'success',
                            PendingChangeStatus::Rejected => 'danger',
                        }),
                    TextEntry::make('reviewedBy.name')->label('Decisa da')->placeholder('Vuoto'),
                    TextEntry::make('reviewed_at')->label('Quando')->dateTime('d/m/Y H:i')->placeholder('Vuoto'),
                    TextEntry::make('review_note')
                        ->label('Nota')
                        ->placeholder('Nessuna nota')
                        ->columnSpanFull(),
                ])
                ->columns(3)
                // Prima che qualcuno decida non c'è niente da mostrare.
                ->visible(fn ($record) => ! $record->isPending()),
        ]);
    }
}
