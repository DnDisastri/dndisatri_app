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
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                // L'approvazione: chi è ancora in attesa non può entrare.
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
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                // Approvare un iscritto: gli apre la porta. Compare solo su chi è
                // ancora in attesa, e con conferma perché è un via libera.
                Action::make('approva')
                    ->label('Approva')
                    ->icon(Icon::Approve)
                    ->color('success')
                    ->visible(fn (User $record) => ! $record->isApproved())
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
            ]);
    }
}
