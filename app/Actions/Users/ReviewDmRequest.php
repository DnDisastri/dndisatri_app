<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\PendingChangeStatus;
use App\Models\DmRequest;
use App\Models\User;
use App\Notifications\DmRequestDecided;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Approva o rifiuta una richiesta di diventare dungeon master.
 *
 * **È l'unico punto del sistema in cui si assegna il ruolo `dm`**, insieme al
 * comando `dndisastri:admin` per gli amministratori. Il ruolo non è un campo
 * scrivibile da nessuna parte: passa da qui, lato server, per mano di un admin.
 *
 * Era il buco principale della vecchia applicazione, dove chiunque poteva
 * promuoversi dalla console del browser.
 */
final class ReviewDmRequest
{
    public function handle(
        DmRequest $request,
        User $reviewer,
        PendingChangeStatus $decision,
        ?string $note = null,
    ): DmRequest {
        if ($decision === PendingChangeStatus::Pending) {
            throw new InvalidArgumentException('Una richiesta si approva o si rifiuta, non si lascia in sospeso.');
        }

        if (! $reviewer->isAdmin()) {
            throw new RuntimeException('Solo un amministratore può decidere sulle richieste di diventare DM.');
        }

        return DB::transaction(function () use ($request, $reviewer, $decision, $note) {
            $locked = DmRequest::whereKey($request->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new RuntimeException('Questa richiesta è già stata decisa.');
            }

            $locked->forceFill([
                'status' => $decision,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            $applicant = $locked->user()->first();

            if ($decision === PendingChangeStatus::Approved) {
                $applicant?->assignRole(Role::findOrCreate(User::ROLE_DM, 'web'));
            }

            $applicant?->notify(new DmRequestDecided($locked));

            return $locked;
        });
    }
}
