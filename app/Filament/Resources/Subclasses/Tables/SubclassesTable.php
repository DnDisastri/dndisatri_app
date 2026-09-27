<?php

namespace App\Filament\Resources\Subclasses\Tables;

use App\Domain\Dnd\ClassRules;
use App\Models\Subclass;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubclassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup('class')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('In una riga')
                    ->placeholder('Nessuna')
                    ->wrap()
                    ->limit(90)
                    ->toggleable(),

                IconColumn::make('third_caster')
                    ->label('Lancia')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('is_homebrew')
                    ->label('Fatta in casa')
                    ->boolean(),

                TextColumn::make('position')
                    ->label('Posizione')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('class')
                    ->label('Classe')
                    ->options(fn () => ClassRules::names()->mapWithKeys(fn (string $c) => [$c => $c])->all()),

                TernaryFilter::make('is_homebrew')
                    ->label('Origine')
                    ->placeholder('Tutte')
                    ->trueLabel('Fatte in casa')
                    ->falseLabel('Dal manuale'),
            ])
            ->recordActions([
                EditAction::make(),
                // Solo le homebrew: quelle da manuale le hanno dei personaggi.
                DeleteAction::make()->visible(fn (Subclass $record) => $record->is_homebrew),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nessuna sottoclasse')
            ->emptyStateDescription('Il catalogo del manuale arriva col seeder; qui si aggiungono le vostre.');
    }
}
