<?php

namespace App\Filament\Resources\Campaigns\Tables;

use App\Enums\Icon;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Tavolo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Campaign $record) => $record->quest_giver
                        ? "Capogilda: {$record->quest_giver}"
                        : null),

                TextColumn::make('dm.name')
                    ->label('Dungeon master')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('stato')
                    ->label('Stato')
                    ->badge()
                    ->state(fn (Campaign $record) => $record->isActive() ? 'Aperta' : 'Conclusa')
                    ->color(fn (Campaign $record) => $record->isActive() ? 'success' : 'gray'),

                TextColumn::make('quests_count')
                    ->label('Quest')
                    ->counts('quests')
                    ->alignCenter(),

                TextColumn::make('sessions_count')
                    ->label('Sessioni')
                    ->counts('sessions')
                    ->alignCenter(),
            ])
            ->filters([
                TernaryFilter::make('aperte')
                    ->label('Stato')
                    ->placeholder('Tutte')
                    ->trueLabel('Solo aperte')
                    ->falseLabel('Solo concluse')
                    ->queries(
                        true: fn ($query) => $query->whereNull('ended_at'),
                        false: fn ($query) => $query->whereNotNull('ended_at'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),

                // Chiudere una campagna è irreversibile: passa da una conferma
                // e resta disponibile solo finché è aperta.
                Action::make('concludi')
                    ->label('Concludi')
                    ->icon(Icon::Archive)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Concludere il tavolo?')
                    ->modalDescription('Non si potrà più riaprire, né aggiungerci quest o sessioni.')
                    ->visible(fn (Campaign $record) => auth()->user()->can('end', $record))
                    ->action(function (Campaign $record) {
                        $record->forceFill(['ended_at' => now()])->save();

                        Notification::make()
                            ->title("«{$record->title}» è ora nell'archivio.")
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Nessun tavolo')
            ->emptyStateDescription('Crea una campagna per cominciare a organizzare quest e sessioni.')
            ->modifyQueryUsing(fn ($query) => $query->with('dm'));
    }
}
