<?php

declare(strict_types=1);

namespace App\Actions\Supervision;

use App\Actions\Approvals\AnnounceForApproval;
use App\Actions\Market\AcceptTrade;
use App\Actions\Market\BuyListing;
use App\Actions\Market\CreateListing;
use App\Actions\Market\CreateTrade;
use App\Enums\SupervisedActionType;
use App\Notifications\SupervisedActionAwaitingApproval;
use App\Models\Character;
use App\Models\MarketListing;
use App\Models\SupervisedAction;
use App\Models\Trade;
use App\Models\User;

/**
 * Il posto da cui passano le quattro azioni di mercato controllabili (D13).
 *
 * **Le pagine chiamano sempre questo, mai le azioni dirette.** Qui si decide se
 * l'operazione avviene subito o se resta in attesa di un via libera, e la
 * decisione dipende da una cosa sola: se chi agisce è sotto richiamo.
 *
 * Chi non lo è non si accorge di niente — il metodo esegue e restituisce quello
 * che avrebbe restituito l'azione diretta. Chi lo è ottiene invece una
 * `SupervisedAction`, cioè una richiesta in attesa, e chi chiama distingue i
 * due casi guardando il tipo di ritorno.
 *
 * Le azioni dirette restano raggiungibili, e servono: è `ApproveSupervisedAction`
 * a chiamarle quando il via libera arriva. Passare di lì di nuovo creerebbe un
 * ciclo senza fine, perché il richiamo nel frattempo è ancora attivo.
 */
final class Supervisor
{
    /** @param list<array{name: string, qty?: int}> $give */
    public function proposeTrade(
        User $actor,
        Character $from,
        Character $to,
        array $give = [],
        array $want = [],
        int $giveGp = 0,
        int $wantGp = 0,
        ?string $message = null,
    ): Trade|SupervisedAction {
        if (! $actor->isUnderWarning()) {
            return app(CreateTrade::class)->handle($from, $to, $give, $want, $giveGp, $wantGp, $message);
        }

        return $this->hold($actor, SupervisedActionType::TradeProposal, [
            'from_character_id' => $from->getKey(),
            'to_character_id' => $to->getKey(),
            'give' => $give,
            'want' => $want,
            'give_gp' => $giveGp,
            'want_gp' => $wantGp,
            'message' => $message,
        ], "Vuole proporre uno scambio a {$to->name}");
    }

    public function acceptTrade(User $actor, Trade $trade): Trade|SupervisedAction
    {
        if (! $actor->isUnderWarning()) {
            return app(AcceptTrade::class)->handle($trade, $actor);
        }

        $from = $trade->from()->first();

        return $this->hold($actor, SupervisedActionType::TradeAcceptance, [
            'trade_id' => $trade->getKey(),
            'from_character_id' => $trade->from_character_id,
            'to_character_id' => $trade->to_character_id,
        ], 'Vuole accettare lo scambio proposto da '.($from?->name ?? 'un altro giocatore'));
    }

    public function createListing(
        User $actor,
        Character $seller,
        string $itemName,
        int $qty,
        int $price,
    ): MarketListing|SupervisedAction {
        if (! $actor->isUnderWarning()) {
            return app(CreateListing::class)->handle($seller, $itemName, $qty, $price, $actor);
        }

        return $this->hold($actor, SupervisedActionType::ListingCreation, [
            'character_id' => $seller->getKey(),
            'name' => $itemName,
            'qty' => $qty,
            'price' => $price,
        ], "Vuole mettere in vendita {$qty}× {$itemName} per {$price} mo");
    }

    public function buyListing(User $actor, MarketListing $listing, Character $buyer): MarketListing|SupervisedAction
    {
        if (! $actor->isUnderWarning()) {
            return app(BuyListing::class)->handle($listing, $buyer, $actor);
        }

        return $this->hold($actor, SupervisedActionType::ListingPurchase, [
            'listing_id' => $listing->getKey(),
            'buyer_character_id' => $buyer->getKey(),
            'seller_character_id' => $listing->seller_character_id,
        ], "Vuole comprare {$listing->qty}× {$listing->name} per {$listing->price} mo");
    }

    /** @param array<string,mixed> $payload */
    private function hold(
        User $actor,
        SupervisedActionType $type,
        array $payload,
        string $summary,
    ): SupervisedAction {
        $action = SupervisedAction::create([
            'user_id' => $actor->getKey(),
            // Si annota sotto quale richiamo è stata chiesta: a richiamo
            // chiuso, è quello che racconta se il controllo è servito.
            'warning_id' => $actor->activeWarning()?->getKey(),
            'type' => $type,
            'payload' => $payload,
            'summary' => $summary,
        ]);

        app(AnnounceForApproval::class)->handle(new SupervisedActionAwaitingApproval($action), $actor);

        return $action;
    }
}
