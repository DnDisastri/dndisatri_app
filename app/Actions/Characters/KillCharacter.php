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
 * La morte di un personaggio.
 *
 * Il permesso e il memoriale esistevano da sempre; l'azione che scrive la data
 * di morte no, e finché non c'è stata un personaggio poteva essere dichiarato
 * caduto solo intervenendo a mano sul database.
 *
 * **È irreversibile.** `died_at` non è mass-assignable, quindi questa è l'unica
 * strada che porta lì, e non esiste il ritorno: un personaggio non risorge.
 *
 * Non basta però segnare la data, perché il caduto lascia in giro due cose che
 * non potranno mai andare a buon fine:
 *
 * - **gli annunci aperti**, che resterebbero comprabili — chi compra pagherebbe
 *   un morto. Si ritirano, e gli oggetti rientrano nel suo inventario, dove
 *   restano a raccontare con cosa è morto;
 * - **le proposte di scambio aperte**, in entrambe le direzioni, che
 *   fallirebbero comunque all'accettazione.
 */
final class KillCharacter
{
    /**
     * @param  string|null  $story  come è andata: è quello che resterà scritto
     *                              nel memoriale, e vale più della data
     * @param  GameSession|null  $session  la serata in cui è successo, se è
     *                                     successo a un tavolo
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
