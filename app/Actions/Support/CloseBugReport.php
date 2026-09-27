<?php

declare(strict_types=1);

namespace App\Actions\Support;

use App\Enums\BugReportStatus;
use App\Models\BugReport;
use App\Models\User;
use App\Notifications\BugReportClosed;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Chiude una segnalazione e lo dice a chi l'aveva aperta.
 *
 * L'avviso è il punto: una segnalazione senza risposta insegna a non
 * segnalare più.
 */
final class CloseBugReport
{
    public function handle(
        BugReport $report,
        User $admin,
        BugReportStatus $esito,
        ?string $answer = null,
    ): BugReport {
        if (! $esito->isClosed()) {
            throw new InvalidArgumentException('Una segnalazione si chiude come risolta o come non errore.');
        }

        if (! $admin->isAdmin()) {
            throw new RuntimeException('Solo un amministratore chiude le segnalazioni.');
        }

        return DB::transaction(function () use ($report, $admin, $esito, $answer) {
            $locked = BugReport::whereKey($report->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isClosed()) {
                throw new RuntimeException('Questa segnalazione è già chiusa.');
            }

            $locked->forceFill([
                'status' => $esito,
                'closed_by' => $admin->getKey(),
                'closed_at' => now(),
                'answer' => $answer,
            ])->save();

            $locked->reporter?->notify(new BugReportClosed($locked));

            return $locked;
        });
    }
}
