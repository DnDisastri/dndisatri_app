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
            // Le più vecchie per prime: chi aspetta da più tempo va servito prima.
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
                    ->searchable()
                    ->visibleFrom('md'),

                TextColumn::make('summary')
                    ->label('In breve')
                    ->placeholder('Vuoto')
                    ->limit(50)
                    ->toggleable()
                    ->visibleFrom('md'),

                TextColumn::make('created_at')
                    ->label('Da')
                    ->since()
                    ->sortable(),

                // Avvisa se la scheda è cambiata dopo la proposta.
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
                    ->toggleable()
                    ->visibleFrom('md'),

                TextColumn::make('reviewedBy.name')
                    ->label('Decisa da')
                    ->placeholder('Vuoto')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Lo stato si sceglie dalle tab in cima; qui resta il tipo.
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(collect(PendingChangeType::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make()->label('Esamina'),
            ])
            ->emptyStateHeading('Nessuna richiesta')
            ->emptyStateDescription('Quando un giocatore proporrà una modifica, comparirà qui.')
            ->modifyQueryUsing(fn ($query) => $query->with(['character', 'requestedBy', 'reviewedBy']));
    }
}
