<?php

namespace App\Filament\Resources\DmRequests\Tables;

use App\Enums\PendingChangeStatus;
use App\Models\DmRequest;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DmRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Le richieste aperte per prime, poi le più recenti.
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Chi ha chiesto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (PendingChangeStatus $state) => $state->label())
                    ->color(fn (PendingChangeStatus $state) => match ($state) {
                        PendingChangeStatus::Pending => 'warning',
                        PendingChangeStatus::Approved => 'success',
                        PendingChangeStatus::Rejected => 'danger',
                    }),

                TextColumn::make('created_at')
                    ->label('Ricevuta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('reviewedBy.name')
                    ->label('Decisa da')
                    ->placeholder('Vuoto')
                    ->toggleable(),

                TextColumn::make('reviewed_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Vuoto')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(collect(PendingChangeStatus::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                        ->all())
                    ->default(PendingChangeStatus::Pending->value),
            ])
            ->recordActions([
                ViewAction::make()->label('Apri'),
            ])
            ->emptyStateHeading('Nessuna richiesta')
            ->emptyStateDescription('Quando qualcuno chiederà di diventare DM, comparirà qui.')
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'reviewedBy']));
    }

    /** @return class-string<DmRequest> */
    public static function model(): string
    {
        return DmRequest::class;
    }
}
