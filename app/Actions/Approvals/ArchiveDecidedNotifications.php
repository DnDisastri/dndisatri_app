<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Enums\BugReportStatus;
use App\Enums\PendingChangeStatus;
use App\Models\BugReport;
use App\Models\PendingChange;
use App\Models\SupervisedAction;
use App\Models\User;
use App\Notifications\BugReportFiled;
use App\Notifications\ChangeAwaitingApproval;
use App\Notifications\RegistrationAwaitingApproval;
use App\Notifications\SupervisedActionAwaitingApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Decisa una richiesta, le sue notifiche «da decidere» vanno in archivio per
 * tutti, con l'esito. La richiesta sta nella chiave `subject` dei dati.
 */
final class ArchiveDecidedNotifications
{
    /** Notifica «da decidere» => richiesta a cui si riferisce. */
    public const TIPI = [
        ChangeAwaitingApproval::class => PendingChange::class,
        SupervisedActionAwaitingApproval::class => SupervisedAction::class,
        RegistrationAwaitingApproval::class => User::class,
        BugReportFiled::class => BugReport::class,
    ];

    /** @return array{type: string, id: int} */
    public static function subject(Model $richiesta): array
    {
        return ['type' => $richiesta::class, 'id' => (int) $richiesta->getKey()];
    }

    /**
     * Dopo il commit: un errore qui non deve annullare la decisione.
     * `$chi` per le iscrizioni, che non ricordano chi le ha approvate.
     */
    public function handle(Model $richiesta, ?User $chi = null): void
    {
        DB::afterCommit(fn () => $this->archiveAll($richiesta, $chi));
    }

    private function archiveAll(Model $richiesta, ?User $chi): void
    {
        $esito = self::outcome($richiesta, $chi);

        if ($esito === null) {
            return;
        }

        $notifiche = DatabaseNotification::query()
            ->whereIn('type', array_keys(self::TIPI, $richiesta::class, true))
            ->whereNull('archived_at')
            ->where('data->subject->type', $richiesta::class)
            ->where('data->subject->id', (int) $richiesta->getKey())
            ->get();

        foreach ($notifiche as $notifica) {
            $this->archive($notifica, $esito);
        }
    }

    public function archive(DatabaseNotification $notifica, string $esito, ?Model $richiesta = null): void
    {
        $notifica->forceFill([
            'data' => [
                ...$notifica->data,
                ...($richiesta === null ? [] : ['subject' => self::subject($richiesta)]),
                'outcome' => $esito,
            ],
            'archived_at' => now(),
        ])->save();
    }

    /** «Approvata da Margherita il 12/10», o null se la richiesta aspetta ancora. */
    public static function outcome(Model $richiesta, ?User $decisoDa = null): ?string
    {
        [$parola, $chi, $quando] = match (true) {
            $richiesta instanceof PendingChange, $richiesta instanceof SupervisedAction => [
                match ($richiesta->status) {
                    PendingChangeStatus::Approved => 'Approvata',
                    PendingChangeStatus::Rejected => 'Rifiutata',
                    default => null,
                },
                $richiesta->reviewedBy?->name,
                $richiesta->reviewed_at,
            ],
            $richiesta instanceof BugReport => [
                match ($richiesta->status) {
                    BugReportStatus::Fixed => 'Risolta',
                    BugReportStatus::NotABug => 'Chiusa come non errore',
                    default => null,
                },
                $richiesta->closer?->name,
                $richiesta->closed_at,
            ],
            $richiesta instanceof User => [$richiesta->isApproved() ? 'Approvata' : null, null, $richiesta->approved_at],
            default => [null, null, null],
        };

        if ($parola === null) {
            return null;
        }

        $chi = $decisoDa?->name ?? $chi;

        return $parola
            .($chi !== null ? " da {$chi}" : '')
            .($quando instanceof Carbon ? ' il '.$quando->format('d/m') : '')
            .'.';
    }
}
