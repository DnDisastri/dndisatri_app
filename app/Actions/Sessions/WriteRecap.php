<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Models\GameSession;
use App\Models\User;

/**
 * Scrive o corregge il resoconto di una serata.
 *
 * Passa da un'azione perché il recap porta con sé chi l'ha scritto e quando:
 * sono campi non mass-assignable, così non esiste modo di aggiornare il testo
 * lasciando indietro la firma.
 */
final class WriteRecap
{
    public function handle(GameSession $session, User $author, string $recap): GameSession
    {
        $session->forceFill([
            'recap' => $recap,
            'recap_written_by' => $author->getKey(),
            'recap_written_at' => now(),
        ])->save();

        return $session;
    }
}
