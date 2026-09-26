<?php

namespace App\Filament\Resources\BugReports\Pages;

use App\Enums\BugReportStatus;
use App\Filament\Resources\BugReports\BugReportResource;
use App\Models\BugReport;
use Closure;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBugReports extends ListRecords
{
    protected static string $resource = BugReportResource::class;

    /** Le segnalazioni arrivano dall'app: dal pannello non se ne creano. */
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'da-vedere' => Tab::make('Da vedere')
                ->badge(BugReport::query()->open()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->open()),

            'chiuse' => Tab::make('Chiuse')
                ->modifyQueryUsing($this->inStato([BugReportStatus::Fixed, BugReportStatus::NotABug])),

            'tutte' => Tab::make('Tutte')
                ->badge(BugReport::count()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'da-vedere';
    }

    /** @param  list<BugReportStatus>  $stati */
    private function inStato(array $stati): Closure
    {
        return fn (Builder $query) => $query->whereIn('status', $stati);
    }
}
