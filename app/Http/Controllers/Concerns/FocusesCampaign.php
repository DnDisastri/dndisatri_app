<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Collection;

/** La campagna su cui lavora la Regia: quella dell'indirizzo, poi la prima delle mie, poi un'altra attiva. */
trait FocusesCampaign
{
    /** @return array{0: Collection<int, Campaign>, 1: Collection<int, Campaign>} le mie e quelle degli altri */
    protected function campagneDelDm(User $user): array
    {
        $mie = Campaign::query()->active()->runBy($user)
            ->orderByDesc('season')->orderBy('title')->get();

        $altre = Campaign::query()->active()
            ->where('dm_id', '!=', $user->getKey())
            ->with('dm')
            ->orderByDesc('season')->orderBy('title')->get();

        return [$mie, $altre];
    }

    protected function campagnaAFuoco(?string $slug, Collection $mie, Collection $altre): ?Campaign
    {
        if (filled($slug)) {
            $scelta = $mie->firstWhere('slug', $slug) ?? $altre->firstWhere('slug', $slug);

            if ($scelta !== null) {
                return $scelta;
            }
        }

        return $mie->first() ?? $altre->first();
    }
}
