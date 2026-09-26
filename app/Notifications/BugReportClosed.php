<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\BugReportStatus;
use App\Enums\NotificationCategory;
use App\Models\BugReport;

/**
 * Dice a chi ha segnalato com'è finita.
 *
 * È il motivo per cui la gente segnala una seconda volta: senza risposta, una
 * casella dei suggerimenti smette di ricevere suggerimenti.
 */
final class BugReportClosed extends InAppNotification
{
    public function __construct(private readonly BugReport $report) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Reports;
    }

    public function toArray(object $notifiable): array
    {
        $titolo = $this->report->status === BugReportStatus::Fixed
            ? 'Sistemato'
            : 'Funziona così';

        $corpo = "«{$this->report->title}»";

        if (filled($this->report->answer)) {
            $corpo .= ": {$this->report->answer}";
        } elseif ($this->report->status === BugReportStatus::Fixed) {
            $corpo .= ': il problema che avevi segnalato è risolto.';
        } else {
            $corpo .= ': non è un errore, è il comportamento previsto.';
        }

        return [
            'title' => $titolo,
            'body' => $corpo,
            'url' => null,
        ];
    }
}
