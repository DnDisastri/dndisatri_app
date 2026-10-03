<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\BugReport;

/** Avvisa gli admin che è arrivata una segnalazione. */
final class BugReportFiled extends InAppNotification
{
    public function __construct(private readonly BugReport $report) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Approvals;
    }

    public function toArray(object $notifiable): array
    {
        $chi = $this->report->reporter?->name ?? 'Qualcuno';

        return [
            'title' => 'Una segnalazione da leggere',
            'body' => "{$chi}: «{$this->report->title}».",
            'url' => route('filament.admin.resources.bug-reports.view', $this->report),
        ];
    }
}
