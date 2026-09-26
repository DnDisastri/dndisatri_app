<?php

declare(strict_types=1);

namespace App\Actions\Support;

use App\Actions\Approvals\AnnounceForApproval;
use App\Models\BugReport;
use App\Models\User;
use App\Notifications\BugReportFiled;

/**
 * Registra una segnalazione e avvisa gli amministratori.
 *
 * Pagina e browser si raccolgono da soli: sono quello che chi segnala non sa
 * dare e chi corregge non riesce a ricostruire. All'utente resta da scrivere
 * cosa stava facendo.
 */
final class FileBugReport
{
    public function handle(
        User $reporter,
        string $title,
        string $description,
        ?string $page = null,
        ?string $userAgent = null,
    ): BugReport {
        $report = BugReport::create([
            'user_id' => $reporter->getKey(),
            'title' => $title,
            'description' => $description,
            'page' => $page,
            // La colonna è una stringa: un browser prolisso non deve far
            // fallire la segnalazione che sta descrivendo il problema.
            'user_agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 255),
        ]);

        app(AnnounceForApproval::class)->handle(
            new BugReportFiled($report),
            ruoli: [User::ROLE_ADMIN],
        );

        return $report;
    }
}
