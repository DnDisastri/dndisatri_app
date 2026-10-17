<?php

namespace App\Filament\Resources\BaseItems\Tables;

use App\Domain\Dnd\Coins;
use App\Enums\EquipmentSlot;
use App\Enums\RulesEdition;
use App\Models\BaseItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BaseItemsTable
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

                TextColumn::make('kind')
                    ->label('Che cos\'è')
                    ->badge()
                    ->formatStateUsing(fn (EquipmentSlot $state) => $state->label()),

                TextColumn::make('numeri')
                    ->label('Numeri')
                    ->state(fn (BaseItem $record) => $record->summary())
                    ->wrap(),

                TextColumn::make('price_cp')
                    ->label('Prezzo')
                    ->formatStateUsing(fn (?int $state) => $state === null ? null : Coins::formatValue($state))
                    ->placeholder('Nessuno')
                    ->toggleable(),

                TextColumn::make('rules_edition')
                    ->label('Regole')
                    ->formatStateUsing(fn (RulesEdition $state) => $state->label())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label('Che cos\'è')
                    ->options(collect(EquipmentSlot::cases())->mapWithKeys(fn (EquipmentSlot $s) => [$s->value => $s->label()])->all()),

                SelectFilter::make('rules_edition')
                    ->label('Regole')
                    ->options(RulesEdition::options()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nessun oggetto')
            ->emptyStateDescription('Armi, armature e scudi arrivano con la migrazione; qui si aggiungono gli altri.');
    }
}
