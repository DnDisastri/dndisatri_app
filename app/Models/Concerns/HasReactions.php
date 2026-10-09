<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Reaction as ReactionType;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Un modello che accetta le reaction; il tipo va aggiunto anche in `App\Enums\Reactable`.
 */
trait HasReactions
{
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Quante per faccina, in una query sola.
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

    /** Se ha senso reagire adesso; lo ridefinisce chi ha un «finito», come la sessione. */
    public function acceptsReactions(): bool
    {
        return true;
    }
}
