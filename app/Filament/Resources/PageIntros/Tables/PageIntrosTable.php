<?php

namespace App\Filament\Resources\PageIntros\Tables;

use App\Models\PageIntro;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PageIntrosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->paginated(false)
            ->columns([
                TextColumn::make('page')
                    ->label('Pagina')
                    ->formatStateUsing(fn (PageIntro $record) => $record->page->label()),

                TextColumn::make('body')
                    ->label('Testo')
                    ->limit(80)
                    ->wrap(),

                TextColumn::make('updated_at')
                    ->label('Aggiornata')
                    ->dateTime('d/m/Y H:i')
                    ->visibleFrom('md'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Nessuna introduzione');
    }
}
