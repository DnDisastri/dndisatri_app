<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Reaction as ReactionType;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * «Questa cosa accetta le reaction.»
 *
 * Metterlo su un modello è tutto quello che serve: da lì in poi la pagina può
 * disegnare `<x-reactions>` e la rotta accetta il tipo, purché stia anche in
 * `App\Enums\Reactable`.
 */
trait HasReactions
{
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Quante per faccina, solo quelle che qualcuno ha davvero messo.
     *
     * Una query sola con un `group by`, e non dieci conteggi: su una pagina
     * con dieci reaction possibili sarebbe la differenza fra una richiesta al
     * database e undici.
     *
     * @return Collection<string, int> chiave della reaction => quante
     */
    public function reactionCounts(): Collection
    {
        return $this->reactions()
            ->selectRaw('type, count(*) as quante')
            ->groupBy('type')
            ->pluck('quante', 'type');
    }

    /** Quella di una persona, se l'ha messa: è quella che si vede accesa. */
    public function reactionOf(?User $user): ?ReactionType
    {
        if ($user === null) {
            return null;
        }

        return $this->reactions()->where('user_id', $user->getKey())->first()?->type;
    }

    /**
     * Se in questo momento ha senso reagire.
     *
     * Il caso che conta è la serata: prima che il resoconto sia scritto non
     * c'è niente da applaudire, e la reaction andrebbe a una pagina che dice
     * soltanto quando si gioca. I modelli che hanno un «finito» lo
     * ridefiniscono; per gli altri una cosa pubblicata è già pronta.
     */
    public function acceptsReactions(): bool
    {
        return true;
    }
}
