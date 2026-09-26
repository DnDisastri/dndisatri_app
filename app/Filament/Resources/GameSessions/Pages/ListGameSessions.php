<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Models\GameSession;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListGameSessions extends ListRecords
{
    protected static string $resource = GameSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'da_giocare' => Tab::make('Da giocare')
                ->badge(GameSession::query()->where('played_at', '>=', now())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('played_at', '>=', now())),

            'giocate' => Tab::make('Giocate')
                ->badge(GameSession::query()->where('played_at', '<', now())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('played_at', '<', now())),

            'tutte' => Tab::make('Tutte'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'da_giocare';
    }
}
