<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'giocatori' => Tab::make('Giocatori')
                ->badge(User::query()->tap($this->inRole(User::ROLE_PLAYER))->count())
                ->modifyQueryUsing($this->inRole(User::ROLE_PLAYER)),

            'dm' => Tab::make('Dungeon master')
                ->badge(User::query()->tap($this->inRole(User::ROLE_DM))->count())
                ->modifyQueryUsing($this->inRole(User::ROLE_DM)),

            'admin' => Tab::make('Admin')
                ->badge(User::query()->tap($this->inRole(User::ROLE_ADMIN))->count())
                ->modifyQueryUsing($this->inRole(User::ROLE_ADMIN)),

            'tutti' => Tab::make('Tutti')
                ->badge(User::count()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'giocatori';
    }

    // whereHas invece dello scope role() di Spatie: non solleva eccezioni se il
    // ruolo non esiste ancora nel database.
    private function inRole(string $role): Closure
    {
        return fn (Builder $query) => $query->whereHas('roles', fn (Builder $q) => $q->where('name', $role));
    }
}
