<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use App\Models\Warning;
use App\Notifications\WarningLifted;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Toglie un richiamo.
 *
 * La riga non si cancella: si chiude. Lo storico deve poter dire quante volte
 * una persona è stata richiamata e per quanto tempo, e un richiamo cancellato
 * quella storia la perderebbe.
 */
final class LiftWarning
{
    public function handle(Warning $warning, User $actor, ?string $note = null): Warning
    {
        return DB::transaction(function () use ($warning, $actor, $note) {
            $locked = Warning::whereKey($warning->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isActive()) {
                throw new RuntimeException('Questo richiamo era già stato tolto.');
            }

            $locked->forceFill([
                'lifted_at' => now(),
                'lifted_by' => $actor->getKey(),
                'lift_note' => $note,
            ])->save();

            $locked->user()->first()?->notify(new WarningLifted($locked));

            return $locked;
        });
    }
}
