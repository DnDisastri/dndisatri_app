<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\Icon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                IconColumn::make('is_pinned')
                    ->label('')
                    ->boolean()
                    ->trueIcon(Icon::Featured)
                    ->falseIcon(Icon::NotFeatured)
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                TextColumn::make('stato')
                    ->label('Stato')
                    ->badge()
                    ->state(fn ($record) => match (true) {
                        $record->isDraft() => 'Bozza',
                        ! $record->isPublished() => 'Programmata',
                        default => 'Pubblicata',
                    })
                    ->color(fn ($record) => match (true) {
                        $record->isDraft() => 'gray',
                        ! $record->isPublished() => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('published_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('author.name')
                    ->label('Autore')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('published')
                    ->label('Pubblicazione')
                    ->placeholder('Tutte')
                    ->trueLabel('Solo pubblicate')
                    ->falseLabel('Solo bozze')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('published_at')->where('published_at', '<=', now()),
                        false: fn ($query) => $query->whereNull('published_at'),
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
            ->emptyStateHeading('Nessuna news')
            ->modifyQueryUsing(fn ($query) => $query->with('author'));
    }
}
