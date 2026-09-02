<?php

namespace App\Filament\Resources\SupervisedActions\Schemas;

use App\Enums\PendingChangeStatus;
use App\Enums\SupervisedActionType;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupervisedActionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('La richiesta')
                ->schema([
                    TextEntry::make('type')
                        ->label('Cosa')
                        ->badge()
                        ->formatStateUsing(fn (SupervisedActionType $state) => $state->label()),
                    TextEntry::make('user.name')->label('Chi ha chiesto'),
                    TextEntry::make('created_at')->label('Quando')->dateTime('d/m/Y H:i'),
                    TextEntry::make('summary')
                        ->label('In breve')
                        ->placeholder('Vuoto')
                        ->columnSpanFull(),
                ])
                ->columns(3),

            // Il dettaglio vero: cosa esce e cosa entra. Un'azione di mercato
            // non si giudica dal riassunto di una riga.
            Section::make('Cosa succederebbe')
                ->schema([
                    ViewEntry::make('payload')
                        ->view('filament.supervised-action-details')
                        ->columnSpanFull(),
                ]),

            /*
             * Sotto quale richiamo è stata chiesta. A richiamo chiuso è la riga
             * che racconta se il controllo è servito: si legge il motivo per cui
             * quella persona era osservata, accanto a quello che voleva fare.
             */
            Section::make('Il richiamo per cui è sotto controllo')
                ->schema([
                    TextEntry::make('warning.reason')
                        ->label('Perché era stato dato')
                        ->placeholder('Il richiamo non è più rintracciabile')
                        ->columnSpanFull(),
                    TextEntry::make('warning.created_at')->label('Dal')->dateTime('d/m/Y')->placeholder('Vuoto'),
                    TextEntry::make('warning.lifted_at')
                        ->label('Tolto il')
                        ->dateTime('d/m/Y')
                        ->placeholder('ancora in corso'),
                ])
                ->columns(2)
                ->visible(fn ($record) => $record->warning_id !== null),

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
                        ->label('Motivo')
                        ->placeholder('Nessuna nota')
                        ->columnSpanFull(),
                ])
                ->columns(3)
                ->visible(fn ($record) => ! $record->isPending()),
        ]);
    }
}
