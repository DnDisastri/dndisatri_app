<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Icon;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                // In attesa = non può ancora accedere.
                TextColumn::make('approved_at')
                    ->label('Approvato')
                    ->dateTime('d/m/Y')
                    ->placeholder('in attesa')
                    ->badge()
                    ->color(fn (?string $state) => $state ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registrato')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                TernaryFilter::make('approved_at')
                    ->label('Approvazione')
                    ->placeholder('Tutti')
                    ->trueLabel('Approvati')
                    ->falseLabel('In attesa')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('approved_at'),
                        false: fn ($query) => $query->whereNull('approved_at'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                // Solo admin, sugli account ancora in attesa; con conferma.
                Action::make('approva')
                    ->label('Approva')
                    ->icon(Icon::Approve)
                    ->color('success')
                    ->visible(fn (User $record) => ! $record->isApproved() && auth()->user()->isAdmin())
                    ->requiresConfirmation()
                    ->modalHeading('Approvare questo account?')
                    ->modalDescription('Da questo momento potrà accedere all\'applicazione.')
                    ->action(fn (User $record) => $record->forceFill(['approved_at' => now()])->save()),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nessun utente')
            ->emptyStateDescription('Qui compaiono gli iscritti: giocatori, DM e admin.');
    }
}
