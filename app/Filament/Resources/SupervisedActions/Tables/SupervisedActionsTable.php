<?php

namespace App\Filament\Resources\SupervisedActions\Tables;

use App\Enums\PendingChangeStatus;
use App\Enums\SupervisedActionType;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupervisedActionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'reviewedBy']))
            // Le più vecchie in cima fra quelle aperte: chi aspetta da più
            // tempo è quello di cui ci si è dimenticati, e qui l'attesa non è
            // una scomodità — è un giocatore fermo che non può vendere.
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Chi ha chiesto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Cosa')
                    ->badge()
                    ->formatStateUsing(fn (SupervisedActionType $state) => $state->label()),

                // Il riassunto è scritto quando l'azione viene messa in attesa:
                // «Vende Spada lunga +1 per 40 mo a Grimm». Serve a decidere
                // quali aprire, non a decidere.
                TextColumn::make('summary')
                    ->label('In breve')
                    ->wrap()
                    ->limit(90)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Da quando aspetta')
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('d/m/Y H:i'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Esito')
                    ->badge()
                    ->formatStateUsing(fn (PendingChangeStatus $state) => $state->label())
                    ->color(fn (PendingChangeStatus $state) => match ($state) {
                        PendingChangeStatus::Pending => 'warning',
                        PendingChangeStatus::Approved => 'success',
                        PendingChangeStatus::Rejected => 'danger',
                    }),

                TextColumn::make('reviewedBy.name')
                    ->label('Decisa da')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('reviewed_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Si apre su quelle che aspettano: è la ragione per cui uno
                // entra qui.
                SelectFilter::make('status')
                    ->label('Esito')
                    ->options(collect(PendingChangeStatus::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                        ->all())
                    ->default(PendingChangeStatus::Pending->value),

                SelectFilter::make('type')
                    ->label('Cosa')
                    ->options(collect(SupervisedActionType::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make()->label('Apri'),
            ])
            // Niente approvazioni di gruppo: ognuna va guardata, ed è il senso
            // di averle messe in attesa.
            ->toolbarActions([]);
    }
}
