<?php

namespace App\Console\Commands;

use App\Actions\Approvals\ArchiveDecidedNotifications;
use App\Models\BugReport;
use App\Models\PendingChange;
use App\Models\SupervisedAction;
use App\Notifications\BugReportFiled;
use App\Notifications\ChangeAwaitingApproval;
use App\Notifications\SupervisedActionAwaitingApproval;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Le vecchie notifiche «da decidere», senza `subject`: la richiesta si ricava dal
 * link. Le iscrizioni no, il loro link non ha l'id.
 */
class ArchiveDecidedNotificationsCommand extends Command
{
    protected $signature = 'dndisastri:archivia-notifiche-decise
        {--dry-run : Mostra cosa archivierebbe senza toccare niente}';

    protected $description = 'Archivia le vecchie notifiche delle richieste già decise';

    /** Notifica => [richiesta, pezzo del link prima dell'id]. */
    private const DAL_LINK = [
        ChangeAwaitingApproval::class => [PendingChange::class, 'pending-changes'],
        SupervisedActionAwaitingApproval::class => [SupervisedAction::class, 'supervised-actions'],
        BugReportFiled::class => [BugReport::class, 'bug-reports'],
    ];

    public function handle(ArchiveDecidedNotifications $archivio): int
    {
        $prova = (bool) $this->option('dry-run');
        $righe = [];

        DatabaseNotification::query()
            ->whereIn('type', array_keys(self::DAL_LINK))
            ->whereNull('archived_at')
            ->orderBy('created_at')
            ->each(function (DatabaseNotification $notifica) use ($archivio, $prova, &$righe) {
                $richiesta = $this->richiesta($notifica);
                $esito = $richiesta === null ? null : ArchiveDecidedNotifications::outcome($richiesta);

                if ($esito === null) {
                    return;
                }

                $righe[] = [$notifica->notifiable_id, $notifica->data['title'] ?? '', $esito];

                if (! $prova) {
                    $archivio->archive($notifica, $esito, $richiesta);
                }
            });

        if ($righe === []) {
            $this->info('Nessuna notifica da archiviare.');

            return self::SUCCESS;
        }

        $this->table(['Utente', 'Notifica', 'Esito'], $righe);
        $this->info(($prova ? 'Prova: niente è stato toccato. Si archivierebbero ' : 'Fatto. Archiviate ').count($righe).' notifiche.');

        return self::SUCCESS;
    }

    private function richiesta(DatabaseNotification $notifica): ?Model
    {
        [$classe, $pezzo] = self::DAL_LINK[$notifica->type];

        $id = $notifica->data['subject']['id'] ?? null;

        if ($id === null && preg_match('#/'.$pezzo.'/(\d+)#', (string) ($notifica->data['url'] ?? ''), $m)) {
            $id = (int) $m[1];
        }

        return $id === null ? null : $classe::find($id);
    }
}
