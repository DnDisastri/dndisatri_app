<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Models\GameSession;
use App\Models\SessionTable;
use App\Models\User;

/**
 * Scrive o corregge il resoconto di una sessione.
 *
 * Passa da un'azione perché il recap porta con sé chi l'ha scritto e quando:
 * sono campi non mass-assignable, così non esiste modo di aggiornare il testo
 * lasciando indietro la firma. Vale anche per il resoconto di un tavolo.
 */
final class WriteRecap
{
    public function handle(GameSession|SessionTable $session, User $author, string $recap): GameSession|SessionTable
    {
        $session->forceFill([
            'recap' => $recap,
            'recap_written_by' => $author->getKey(),
            'recap_written_at' => now(),
        ])->save();

        return $session;
    }
}
