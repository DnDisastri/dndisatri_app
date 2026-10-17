<?php

namespace App\Filament\Resources\Species\Tables;

use App\Domain\Dnd\Ability;
use App\Models\Species;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpeciesTable
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

                TextColumn::make('bonus')
                    ->label('Bonus')
                    ->state(fn (Species $record) => collect($record->asi ?? [])
                        ->map(fn ($punti, string $abil) => '+'.$punti.' '.($abil === 'all' ? 'a tutte' : (Ability::tryFrom($abil)?->label() ?? $abil)))
                        ->push($record->free_bonuses ? $record->free_bonuses.' × +1 a scelta' : null)
                        ->filter()
                        ->join(', ') ?: null)
                    ->placeholder('Nessuno'),

                TextColumn::make('speed')
                    ->label('Velocità')
                    ->suffix(' m'),

                TextColumn::make('scelte')
                    ->label('Scelte')
                    ->state(fn (Species $record) => collect([
                        $record->skill_choices ? $record->skill_choices.' abilità' : null,
                        $record->grants_feat ? 'talento' : null,
                    ])->filter()->join(', ') ?: null)
                    ->placeholder('Nessuna'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nessuna specie')
            ->emptyStateDescription('Quelle del manuale arrivano con la migrazione.');
    }
}
