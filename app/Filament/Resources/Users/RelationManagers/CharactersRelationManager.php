<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\Icon;
use App\Models\Character;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CharactersRelationManager extends RelationManager
{
    protected static string $relationship = 'characters';

    protected static ?string $title = 'Personaggi';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->weight('bold')
                    ->description(fn (Character $record) => collect([$record->class, $record->race])
                        ->filter()
                        ->implode(' · ') ?: null)
                    ->searchable(),

                TextColumn::make('level')
                    ->label('Livello')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => "liv. {$state}")
                    ->description(fn (Character $record) => $record->rank()->label())
                    ->sortable(),

                TextColumn::make('gp')
                    ->label('Oro')
                    ->numeric()
                    ->suffix(' mo')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('stato')
                    ->label('Stato')
                    ->badge()
                    ->state(fn (Character $record) => $record->isAlive() ? 'Vivo' : 'Caduto')
                    ->color(fn (Character $record) => $record->isAlive() ? 'success' : 'gray'),
            ])
            // Sola lettura: le schede si modificano dal gioco, non da qui.
            ->headerActions([])
            ->recordActions([
                Action::make('apri')
                    ->label('Apri scheda')
                    ->icon(Icon::GoTo)
                    ->color('gray')
                    ->url(fn (Character $record) => route('characters.show', $record))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Nessun personaggio')
            ->emptyStateDescription("Questo utente non ha ancora creato una scheda.");
    }
}
