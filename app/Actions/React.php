<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Reaction as ReactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Mette, cambia o toglie la reaction di una persona.
 *
 * È un gesto solo con tre esiti, e il pulsante è sempre lo stesso: toccare la
 * faccina che hai già messo la toglie, toccarne un'altra sostituisce quella di
 * prima. Non c'è un pulsante «togli» da nessuna parte — sarebbe un secondo
 * comando per la stessa cosa, e su un telefono anche un secondo bersaglio da
 * centrare.
 *
 * Ripetuta uguale, la richiesta non fa danni: due tocchi rapidi finiscono per
 * mettere e togliere, non per contare due volte. L'unicità vera però la tiene
 * l'indice del database, non questo metodo.
 */
final class React
{
    /** @return ReactionType|null quella rimasta, o niente se è stata tolta */
    public function handle(Model $reactable, User $user, ReactionType $reaction): ?ReactionType
    {
        $esistente = $reactable->reactions()->where('user_id', $user->getKey())->first();

        if ($esistente?->type === $reaction) {
            $esistente->delete();

            return null;
        }

        $reactable->reactions()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['type' => $reaction],
        );

        return $reaction;
    }
}
