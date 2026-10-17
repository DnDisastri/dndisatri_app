<?php

namespace App\Filament\Resources\MagicItems\Tables;

use App\Domain\Dnd\Ability;
use App\Enums\ItemRarity;
use App\Enums\RulesEdition;
use App\Models\MagicItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MagicItemsTable
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

                TextColumn::make('rarity')
                    ->label('Rarità')
                    ->badge()
                    ->formatStateUsing(fn (ItemRarity $state) => $state->label()),

                IconColumn::make('requires_attunement')
                    ->label('Sintonia')
                    ->boolean(),

                TextColumn::make('effetti')
                    ->label('Effetti')
                    ->state(fn (MagicItem $record) => collect([
                        $record->base ? trim($record->base.($record->magic_bonus ? ' +'.$record->magic_bonus : '')) : ($record->magic_bonus ? '+'.$record->magic_bonus : null),
                        ...collect($record->effectsCopy())->map(fn (array $e) => (Ability::tryFrom($e['ability'])?->label() ?? $e['ability'])
                            .($e['mode'] === 'set' ? ' diventa ' : ' +').$e['value']),
                    ])->filter()->join(' · ') ?: null)
                    ->placeholder('Solo descrizione')
                    ->wrap(),

                TextColumn::make('rules_edition')
                    ->label('Regole')
                    ->formatStateUsing(fn (RulesEdition $state) => $state->label())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rarity')
                    ->label('Rarità')
                    ->options(ItemRarity::options()),

                TernaryFilter::make('requires_attunement')
                    ->label('Sintonia'),

                SelectFilter::make('rules_edition')
                    ->label('Regole')
                    ->options(RulesEdition::options()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nessun oggetto magico')
            ->emptyStateDescription('Quelli da scegliere nei bottini e nelle ricompense.');
    }
}
