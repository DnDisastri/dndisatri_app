<?php

namespace App\Filament\Resources\ClassFeatures\Tables;

use App\Domain\Dnd\ClassRules;
use App\Enums\ActionCost;
use App\Enums\FeatureUsesFormula;
use App\Models\ClassFeature;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClassFeaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup(Group::make('owner')->label('Di'))
            ->defaultSort('level')
            ->columns([
                TextColumn::make('level')
                    ->label('Liv.')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->description(fn (ClassFeature $record) => $record->uses_text),

                TextColumn::make('cost')
                    ->label('Costo')
                    ->formatStateUsing(fn (ActionCost $state) => $state->label())
                    ->toggleable(),

                TextColumn::make('uses_formula')
                    ->label('Contatore')
                    ->formatStateUsing(fn (?FeatureUsesFormula $state) => $state?->label())
                    ->placeholder('Nessuno')
                    ->wrap(),

                IconColumn::make('can_activate')
                    ->label('Si attiva')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('classe')
                    ->label('Classe')
                    ->options(fn () => ClassRules::names()->mapWithKeys(fn (string $c) => [$c => $c])->all())
                    // Anche i privilegi delle sue sottoclassi.
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn (Builder $q, string $classe) => $q->where(fn (Builder $q) => $q
                        ->where(fn (Builder $q) => $q->where('owner_type', ClassFeature::CLASS_OWNER)->where('owner', $classe))
                        ->orWhere(fn (Builder $q) => $q->where('owner_type', ClassFeature::SUBCLASS_OWNER)
                            ->whereIn('owner', ClassRules::subclasses($classe)))))),

                TernaryFilter::make('con_contatore')
                    ->label('Contatore')
                    ->placeholder('Tutti')
                    ->trueLabel('Con contatore')
                    ->falseLabel('Usi solo a parole')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('uses_formula'),
                        false: fn (Builder $q) => $q->whereNull('uses_formula')->whereNull('spends')->whereNotNull('uses_text'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nessun privilegio')
            ->emptyStateDescription('Quelli del manuale arrivano con la migrazione.');
    }
}
