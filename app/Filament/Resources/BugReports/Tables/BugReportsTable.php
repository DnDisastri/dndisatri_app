<?php

namespace App\Filament\Resources\BugReports\Tables;

use App\Enums\BugReportStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BugReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Problema')
                    ->searchable()
                    ->wrap()
                    ->limit(80),

                TextColumn::make('reporter.name')
                    ->label('Chi l\'ha segnalato')
                    ->placeholder('Account cancellato')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (BugReportStatus $state) => $state->label())
                    ->color(fn (BugReportStatus $state) => $state->color()),

                TextColumn::make('created_at')
                    ->label('Ricevuta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('closed_at')
                    ->label('Chiusa')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Vuoto')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(collect(BugReportStatus::cases())
                        ->mapWithKeys(fn (BugReportStatus $caso) => [$caso->value => $caso->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make()->label('Apri'),
            ])
            ->emptyStateHeading('Nessuna segnalazione')
            ->emptyStateDescription('Quando qualcuno segnalerà un problema dal menù, comparirà qui.');
    }
}
