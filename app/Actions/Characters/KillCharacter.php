<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Actions\Market\CancelListing;
use App\Enums\ListingStatus;
use App\Enums\TradeStatus;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\MarketListing;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * La morte di un personaggio: irreversibile, ed è l'unica strada per `died_at`.
 * Ritira i suoi annunci aperti (gli oggetti tornano nello zaino) e chiude le
 * proposte di scambio aperte in tutte e due le direzioni.
 */
final class KillCharacter
{
    /**
     * @param  string|null  $story  come è andata: è quello che resterà scritto
     *                              nel memoriale, e vale più della data
     * @param  GameSession|null  $session  la sessione in cui è successo, se c'è
     */
    public function handle(
        Character $character,
        User $actor,
        ?string $story = null,
        ?GameSession $session = null,
    ): Character {
        return DB::transaction(function () use ($character, $actor, $story, $session) {
            $victim = Character::whereKey($character->getKey())->lockForUpdate()->firstOrFail();

            if (! $victim->isAlive()) {
                throw new RuntimeException("{$victim->name} è già fra i caduti.");
            }

            $this->withdrawListings($victim, $actor);
            $this->closeTrades($victim);

            $victim->forceFill([
                'died_at' => now(),
                'death_story' => $story,
                'died_in_session_id' => $session?->getKey(),
            ])->save();

            return $victim;
        });
    }

    private function withdrawListings(Character $character, User $actor): void
    {
        $open = MarketListing::where('seller_character_id', $character->getKey())
            ->where('status', ListingStatus::Active)
            ->get();

        foreach ($open as $listing) {
            app(CancelListing::class)->handle($listing, $actor);
        }
    }

    private function closeTrades(Character $character): void
    {
        Trade::where('status', TradeStatus::Pending)
            ->where(fn ($query) => $query
                ->where('from_character_id', $character->getKey())
                ->orWhere('to_character_id', $character->getKey()))
            ->update([
                'status' => TradeStatus::Cancelled,
                'resolved_at' => now(),
            ]);
    }
}
