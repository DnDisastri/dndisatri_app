<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Enums\PendingChangeStatus;
use App\Models\PendingChange;
use App\Models\User;
use App\Notifications\RequestDecided;
use RuntimeException;

/** Non tocca il personaggio, ma fotografa com'era: la scheda può cambiare dopo. */
final class RejectPendingChange
{
    public function handle(PendingChange $change, User $reviewer, ?string $note = null): PendingChange
    {
        if (! $change->isPending()) {
            throw new RuntimeException('Questa richiesta è già stata decisa.');
        }

        $change->forceFill([
            'before' => $change->character ? $change->snapshotOf($change->character) : null,
            'status' => PendingChangeStatus::Rejected,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        // Senza richiesta che la aspetti, la foto resterebbe sul disco per sempre.
        app(CharacterPhoto::class)->discard($change->diff['photo_path'] ?? null);

        $change->requestedBy()->first()?->notify(new RequestDecided($change));

        return $change;
    }
}
