<?php

namespace App\Filament\Resources\PendingChanges\Schemas;

use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use App\Models\PendingChange;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PendingChangeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('La richiesta')
                ->schema([
                    TextEntry::make('type')
                        ->label('Tipo')
                        ->badge()
                        ->formatStateUsing(fn (PendingChangeType $state) => $state->label()),

                    TextEntry::make('requestedBy.name')
                        ->label('Utente'),

                    TextEntry::make('requestedBy.email')
                        ->label('Email')
                        ->copyable(),

                    TextEntry::make('character.name')
                        ->label('Personaggio'),

                    TextEntry::make('summary')
                        ->label('Riassunto')
                        ->visible(fn (PendingChange $record) => filled($record->summary)),
                ])
                ->columns(1),

            Section::make('Cosa cambia')
                ->schema([
                    ViewEntry::make('diff')
                        ->view('filament.pending-change-diff')
                        ->columnSpanFull(),
                ]),

            Section::make('La decisione')
                ->schema([
                    TextEntry::make('status')
                        ->label('Esito')
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
                ->visible(fn (PendingChange $record) => ! $record->isPending()),
        ])->columns(1);
    }
}
