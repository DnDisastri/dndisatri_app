<?php

namespace App\Filament\Resources\Subraces\Tables;

use App\Domain\Dnd\Ability;
use App\Models\Subrace;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubracesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup('race')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                // Su uno stato che è un array Filament chiamerebbe il
                // formattatore valore per valore: qui serve la riga intera.
                TextColumn::make('bonus')
                    ->label('Bonus')
                    ->placeholder('Nessuno')
                    ->state(fn (Subrace $record) => collect($record->asi ?? [])
                        ->map(fn (int $punti, string $abil) => '+'.$punti.' '.(Ability::tryFrom($abil)?->fullName() ?? $abil))
                        ->join(', ') ?: null),

                TextColumn::make('speed')
                    ->label('Velocità')
                    ->placeholder('Come la razza')
                    ->suffix(' m')
                    ->toggleable(),

                TextColumn::make('traits')
                    ->label('Tratti')
                    ->placeholder('Nessuno')
                    ->wrap()
                    ->limit(90)
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_homebrew')
                    ->label('Fatta in casa')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('race')
                    ->label('Razza')
                    ->options(fn () => collect(array_keys(config('dnd.species', [])))
                        ->mapWithKeys(fn (string $r) => [$r => $r])
                        ->all()),

                TernaryFilter::make('is_homebrew')
                    ->label('Origine')
                    ->placeholder('Tutte')
                    ->trueLabel('Fatte in casa')
                    ->falseLabel('Dal manuale'),
            ])
            ->recordActions([
                EditAction::make(),
                // Solo le fatte in casa: quelle del manuale le hanno dei personaggi.
                DeleteAction::make()->visible(fn (Subrace $record) => $record->is_homebrew),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nessuna sottorazza')
            ->emptyStateDescription('Il catalogo del manuale arriva col seeder; qui si aggiungono le vostre.');
    }
}
