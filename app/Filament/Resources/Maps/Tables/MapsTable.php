<?php

namespace App\Filament\Resources\Maps\Tables;

use App\Models\Map;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MapsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            // uploadedBy si legge in una description: va precaricato, altrimenti
            // col lazy loading disattivato la tabella esplode appena c'è una mappa.
            ->modifyQueryUsing(fn ($query) => $query->with('uploadedBy'))
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Mappa')
                    ->disk('public'),

                TextColumn::make('title')
                    ->label('Titolo')
                    ->description(fn (Map $record) => $record->uploadedBy?->name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('campaign.title')
                    ->label('Campagna')
                    ->placeholder('Generale')
                    ->searchable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                TernaryFilter::make('generali')
                    ->label('Solo generali')
                    ->queries(
                        true: fn ($query) => $query->whereNull('campaign_id'),
                        false: fn ($query) => $query->whereNotNull('campaign_id'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nessuna mappa')
            ->emptyStateDescription('Carica una mappa per una campagna o per tutto il gruppo.');
    }
}
