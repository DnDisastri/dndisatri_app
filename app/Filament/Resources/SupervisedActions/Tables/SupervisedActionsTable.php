<?php

namespace App\Filament\Resources\SupervisedActions\Tables;

use App\Enums\PendingChangeStatus;
use App\Enums\SupervisedActionType;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupervisedActionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'reviewedBy']))
            // Le più vecchie in cima: chi aspetta da più tempo va servito prima.
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Chi ha chiesto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Cosa')
                    ->badge()
                    ->formatStateUsing(fn (SupervisedActionType $state) => $state->label()),

                // Riassunto scritto quando l'azione è messa in attesa.
                TextColumn::make('summary')
                    ->label('In breve')
                    ->wrap()
                    ->limit(90)
                    ->placeholder('Vuoto')
                    ->visibleFrom('md'),

                TextColumn::make('created_at')
                    ->label('Da quando aspetta')
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('d/m/Y H:i'))
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('status')
                    ->label('Esito')
                    ->badge()
                    ->formatStateUsing(fn (PendingChangeStatus $state) => $state->label())
                    ->color(fn (PendingChangeStatus $state) => match ($state) {
                        PendingChangeStatus::Pending => 'warning',
                        PendingChangeStatus::Approved => 'success',
                        PendingChangeStatus::Rejected => 'danger',
                    }),

                TextColumn::make('reviewedBy.name')
                    ->label('Decisa da')
                    ->placeholder('Vuoto')
                    ->toggleable()
                    ->visibleFrom('md'),

                TextColumn::make('reviewed_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Vuoto')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Esito')
                    ->options(collect(PendingChangeStatus::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                        ->all())
                    ->default(PendingChangeStatus::Pending->value),

                SelectFilter::make('type')
                    ->label('Cosa')
                    ->options(collect(SupervisedActionType::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make()->label('Apri'),
            ])
            // Niente azioni di gruppo: ogni richiesta va valutata singolarmente.
            ->toolbarActions([]);
    }
}
