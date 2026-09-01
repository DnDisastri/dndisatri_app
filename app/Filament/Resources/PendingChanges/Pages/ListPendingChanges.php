<?php

namespace App\Filament\Resources\PendingChanges\Pages;

use App\Enums\PendingChangeStatus;
use App\Filament\Resources\PendingChanges\PendingChangeResource;
use App\Models\PendingChange;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPendingChanges extends ListRecords
{
    protected static string $resource = PendingChangeResource::class;

    /** Le richieste arrivano dai giocatori: non si creano dal pannello. */
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'in_attesa' => $this->tabStato('In attesa', PendingChangeStatus::Pending),
            'approvate' => $this->tabStato('Approvate', PendingChangeStatus::Approved),
            'rifiutate' => $this->tabStato('Rifiutate', PendingChangeStatus::Rejected),
            'tutte' => Tab::make('Tutte')->badge(PendingChange::query()->count()),
        ];
    }

    private function tabStato(string $etichetta, PendingChangeStatus $stato): Tab
    {
        return Tab::make($etichetta)
            ->badge(PendingChange::query()->where('status', $stato)->count())
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $stato));
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'in_attesa';
    }
}
