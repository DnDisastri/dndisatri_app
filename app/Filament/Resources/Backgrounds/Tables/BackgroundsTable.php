<?php

namespace App\Filament\Resources\Backgrounds\Tables;

use App\Enums\RulesEdition;
use App\Models\Background;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BackgroundsTable
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

                TextColumn::make('rules_edition')
                    ->label('Regole')
                    ->formatStateUsing(fn (RulesEdition $state) => $state->label())
                    ->badge(),

                TextColumn::make('abilita')
                    ->label('Abilità')
                    ->state(fn (Background $record) => collect($record->skills ?? [])
                        ->map(fn (string $s) => config("dnd.character.skill_names.{$s}", $s))
                        ->join(', ') ?: ($record->free_skills ? $record->free_skills.' a scelta' : null)),

                TextColumn::make('origin_feat')
                    ->label('Talento di origine')
                    ->placeholder('Nessuno')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('rules_edition')
                    ->label('Regole')
                    ->options(RulesEdition::options()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nessun background')
            ->emptyStateDescription('Quelli del manuale arrivano con la migrazione.');
    }
}
