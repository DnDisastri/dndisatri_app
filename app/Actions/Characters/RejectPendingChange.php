<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Enums\PendingChangeStatus;
use App\Models\PendingChange;
use App\Models\User;
use App\Notifications\RequestDecided;
use RuntimeException;

/**
 * Rifiuta una richiesta: non tocca il personaggio, ma resta a registro con
 * chi ha deciso e perché.
 */
final class RejectPendingChange
{
    public function handle(PendingChange $change, User $reviewer, ?string $note = null): PendingChange
    {
        if (! $change->isPending()) {
            throw new RuntimeException('Questa richiesta è già stata decisa.');
        }

        $change->forceFill([
            'status' => PendingChangeStatus::Rejected,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        // Una foto rifiutata non ha più nessuno che la aspetti: resterebbe sul
        // disco per sempre, e nessun percorso porterebbe più a lei.
        app(CharacterPhoto::class)->discard($change->diff['photo_path'] ?? null);

        // Un rifiuto senza avviso è il caso peggiore: il giocatore resterebbe
        // ad aspettare una cosa già decisa.
        $change->requestedBy()->first()?->notify(new RequestDecided($change));

        return $change;
    }
}
