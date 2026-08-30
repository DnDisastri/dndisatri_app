<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use App\Models\Warning;
use App\Notifications\WarningIssued;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Dà un richiamo a un giocatore (D13).
 *
 * Da qui in poi le sue azioni di mercato passano da un'approvazione. Non è una
 * punizione automatica e non scade da sola: la toglie una persona, quando
 * ritiene che il giocatore si sia comportato bene.
 *
 * **Uno alla volta.** Un secondo richiamo mentre il primo è aperto non
 * aggiungerebbe niente — l'effetto è già in corso — e sporcherebbe il conteggio
 * dello storico, che deve raccontare quante volte è servito intervenire.
 */
final class IssueWarning
{
    public function handle(User $target, User $issuer, string $reason): Warning
    {
        if (blank($reason)) {
            throw new InvalidArgumentException('Un richiamo senza motivo non si dà.');
        }

        if ($target->is($issuer)) {
            throw new RuntimeException('Non ci si richiama da soli.');
        }

        if ($target->isAdmin()) {
            throw new RuntimeException('Gli amministratori non si richiamano: non giocano.');
        }

        return DB::transaction(function () use ($target, $issuer, $reason) {
            if ($target->isUnderWarning()) {
                throw new RuntimeException("{$target->name} è già sotto richiamo.");
            }

            $warning = Warning::create([
                'user_id' => $target->getKey(),
                'issued_by' => $issuer->getKey(),
                'reason' => $reason,
            ]);

            // Il richiamato deve sapere subito che cosa cambia per lui, o
            // scoprirebbe il controllo sbattendoci contro al primo scambio.
            $target->notify(new WarningIssued($warning));

            return $warning;
        });
    }
}
