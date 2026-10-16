<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Models\Character;
use App\Models\MarketListing;
use App\Models\PendingChange;
use App\Models\SessionBooking;
use App\Models\Trade;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Ritira un personaggio: resta nel database, con Registro e storico, ma sparisce
 * dall'app e libera il posto per uno nuovo. Le richieste in attesa si rifiutano;
 * annunci, scambi e prenotazioni aperti vanno chiusi prima, a mano.
 */
final class RetireCharacter
{
    public function handle(Character $character, User $actor): void
    {
        if (! $actor->isDm() && ! $actor->isAdmin()) {
            throw new RuntimeException('Solo un DM o un admin ritira un personaggio.');
        }

        $this->assertNothingOpen($character);

        DB::transaction(function () use ($character, $actor) {
            PendingChange::query()->where('character_id', $character->getKey())->pending()->get()
                ->each(fn (PendingChange $change) => app(RejectPendingChange::class)->handle($change, $actor, 'Personaggio ritirato.'));

            $character->forceFill(['retired_by' => $actor->getKey()])->save();
            $character->delete();
        });
    }

    private function assertNothingOpen(Character $character): void
    {
        $id = $character->getKey();

        $aperti = array_filter([
            MarketListing::open()->where('seller_character_id', $id)->exists() ? 'un annuncio nel mercato' : null,
            Trade::pending()->where(fn ($q) => $q->where('from_character_id', $id)->orWhere('to_character_id', $id))->exists() ? 'uno scambio' : null,
            TradeRequest::pending()->where(fn ($q) => $q->where('from_character_id', $id)->orWhere('to_character_id', $id))->exists() ? 'una richiesta di scambio' : null,
            SessionBooking::holdingSeat()->where('character_id', $id)->whereHas('session', fn ($q) => $q->upcoming())->exists() ? 'un posto in una sessione futura' : null,
        ]);

        if ($aperti !== []) {
            throw new RuntimeException("{$character->name} ha ancora ".implode(', ', $aperti).': chiudi prima quello.');
        }
    }
}
