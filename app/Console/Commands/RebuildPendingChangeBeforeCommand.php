<?php

namespace App\Console\Commands;

use App\Enums\PendingChangeStatus;
use App\Models\Character;
use App\Models\PendingChange;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

/**
 * Ricostruisce il «prima» delle richieste approvate prima che si salvasse,
 * dai valori vecchi che il log attività ha registrato al momento dell'approvazione.
 */
class RebuildPendingChangeBeforeCommand extends Command
{
    protected $signature = 'dndisastri:ricostruisci-prima {--dry-run : Mostra cosa farebbe senza salvare}';

    protected $description = 'Ricostruisce com\'era la scheda per le richieste già approvate';

    /** Le modifiche dell'approvazione cadono nello stesso istante in cui si segna `reviewed_at`. */
    private const FINESTRA_SECONDI = 5;

    public function handle(): int
    {
        $prova = (bool) $this->option('dry-run');
        $righe = [];

        PendingChange::query()
            ->with('character')
            ->where('status', PendingChangeStatus::Approved)
            ->whereNull('before')
            ->whereNotNull('reviewed_at')
            ->orderBy('id')
            ->each(function (PendingChange $change) use ($prova, &$righe) {
                $campi = $change->columnFields();

                if ($campi === []) {
                    return;
                }

                $prima = $this->ricostruisci($change, $campi);

                $righe[] = [
                    $change->id,
                    $change->character?->name ?? "#{$change->character_id}",
                    $prima === null ? 'nessuna traccia nel log' : implode(', ', array_keys($prima)),
                ];

                if ($prima !== null && ! $prova) {
                    $change->forceFill(['before' => $prima])->saveQuietly();
                }
            });

        if ($righe === []) {
            $this->info('Nessuna richiesta da ricostruire.');

            return self::SUCCESS;
        }

        $this->table(['Richiesta', 'Personaggio', 'Campi ricostruiti'], $righe);
        $this->info($prova ? 'Prova: niente è stato salvato.' : 'Fatto.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $campi
     * @return array<string,mixed>|null
     */
    private function ricostruisci(PendingChange $change, array $campi): ?array
    {
        $attivita = Activity::query()
            ->where('subject_type', Character::class)
            ->where('subject_id', $change->character_id)
            ->whereBetween('created_at', [
                $change->reviewed_at->copy()->subSeconds(self::FINESTRA_SECONDI),
                $change->reviewed_at->copy()->addSeconds(self::FINESTRA_SECONDI),
            ])
            ->orderBy('id')
            ->get();

        if ($attivita->isEmpty()) {
            return null;
        }

        $prima = [];

        foreach ($attivita as $voce) {
            $vecchi = json_decode((string) $voce->getRawOriginal('attribute_changes'), true)['old'] ?? [];

            foreach ($campi as $campo) {
                if (! array_key_exists($campo, $prima) && array_key_exists($campo, $vecchi)) {
                    $prima[$campo] = $vecchi[$campo];
                }
            }
        }

        // Il log registra solo ciò che è cambiato: un campo assente valeva già quanto il diff.
        foreach ($campi as $campo) {
            if (! array_key_exists($campo, $prima)) {
                $prima[$campo] = $change->diff[$campo] ?? null;
            }
        }

        return $prima;
    }
}
