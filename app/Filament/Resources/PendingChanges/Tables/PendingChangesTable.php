<?php

namespace App\Filament\Resources\PendingChanges\Tables;

use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use App\Models\PendingChange;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PendingChangesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Le più vecchie per prime: chi aspetta da più tempo va servito
            // prima. È il contrario dell'ordinamento di una bacheca di news.
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (PendingChangeType $state) => $state->label())
                    ->color(fn (PendingChangeType $state) => match ($state) {
                        PendingChangeType::LevelUp => 'info',
                        PendingChangeType::Loot => 'success',
                        PendingChangeType::ItemEffect => 'warning',
                        PendingChangeType::CharacterEdit => 'gray',
                    }),

                TextColumn::make('character.name')
                    ->label('Personaggio')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (PendingChange $record) => $record->character
                        ? $record->character->class.' · liv. '.$record->character->level
                        : null),

                TextColumn::make('requestedBy.name')
                    ->label('Proposta da')
                    ->searchable(),

                TextColumn::make('summary')
                    ->label('In breve')
                    ->placeholder('—')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Da')
                    ->since()
                    ->sortable(),

                // La scheda si è mossa fra la proposta e adesso: chi approva
                // deve saperlo prima di premere il pulsante.
                TextColumn::make('avviso')
                    ->label('')
                    ->badge()
                    ->color('danger')
                    ->state(fn (PendingChange $record) => $record->isStale() ? 'Scheda cambiata' : null),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (PendingChangeStatus $state) => $state->label())
                    ->color(fn (PendingChangeStatus $state) => match ($state) {
                        PendingChangeStatus::Pending => 'warning',
                        PendingChangeStatus::Approved => 'success',
                        PendingChangeStatus::Rejected => 'danger',
                    })
                    ->toggleable(),

                TextColumn::make('reviewedBy.name')
                    ->label('Decisa da')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(collect(PendingChangeStatus::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                        ->all())
                    ->default(PendingChangeStatus::Pending->value),

                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(collect(PendingChangeType::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make()->label('Esamina'),
            ])
            ->emptyStateHeading('Nessuna richiesta in attesa')
            ->emptyStateDescription('Quando un giocatore proporrà una modifica, comparirà qui.')
            ->modifyQueryUsing(fn ($query) => $query->with(['character', 'requestedBy', 'reviewedBy']));
    }
}
