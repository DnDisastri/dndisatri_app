<?php

namespace App\Filament\Resources\Monsters\Tables;

use App\Models\Monster;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MonstersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('hp')
                    ->label('PF')
                    ->badge(),

                TextColumn::make('ac')
                    ->label('CA')
                    ->badge(),

                TextColumn::make('attacks')
                    ->label('Attacchi')
                    ->state(fn (Monster $record) => collect($record->attacks ?? [])
                        ->pluck('nome')->filter()->implode(', '))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('createdBy.name')
                    ->label('Scritto da')
                    ->placeholder('la gilda')
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Bestiario vuoto')
            ->emptyStateDescription('Aggiungi i mostri che rivedi: al tavolo li peschi invece di riscriverli.')
            ->modifyQueryUsing(fn ($query) => $query->with('createdBy'));
    }
}
