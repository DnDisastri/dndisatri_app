<?php

namespace App\Filament\Resources\Feats\Tables;

use App\Enums\RulesEdition;
use App\Models\Feat;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FeatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Categoria')
                    ->formatStateUsing(fn (?string $state) => Feat::CATEGORIES[$state] ?? null)
                    ->placeholder('Nessuna')
                    ->toggleable(),

                TextColumn::make('prerequisite')
                    ->label('Prerequisito')
                    ->placeholder('Nessuno')
                    ->toggleable(),

                TextColumn::make('rules_edition')
                    ->label('Regole')
                    ->formatStateUsing(fn (RulesEdition $state) => $state->label())
                    ->badge(),

                TextColumn::make('description')
                    ->label('Cosa fa')
                    ->placeholder('Da scrivere')
                    ->wrap()
                    ->limit(90)
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_homebrew')
                    ->label('Fatto in casa')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('rules_edition')
                    ->label('Regole')
                    ->options(RulesEdition::options()),

                TernaryFilter::make('is_homebrew')
                    ->label('Origine')
                    ->placeholder('Tutti')
                    ->trueLabel('Fatti in casa')
                    ->falseLabel('Dal manuale'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (Feat $record) => $record->is_homebrew),
            ])
            ->emptyStateHeading('Nessun talento')
            ->emptyStateDescription('Quelli del manuale arrivano col seeder; qui si aggiungono gli altri.');
    }
}
