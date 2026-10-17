<?php

namespace App\Filament\Resources\GameClasses\Tables;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\CasterType;
use App\Models\GameClass;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GameClassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('hit_die')
                    ->label('Dado vita')
                    ->prefix('d'),

                TextColumn::make('saves')
                    ->label('Tiri salvezza')
                    ->state(fn (GameClass $record) => collect($record->saves ?? [])
                        ->map(fn (string $a) => Ability::tryFrom($a)?->label() ?? $a)
                        ->join(', ')),

                TextColumn::make('caster_type')
                    ->label('Incantatore')
                    ->formatStateUsing(fn (CasterType $state) => $state->label())
                    ->wrap(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nessuna classe')
            ->emptyStateDescription('Quelle del manuale arrivano con la migrazione.');
    }
}
