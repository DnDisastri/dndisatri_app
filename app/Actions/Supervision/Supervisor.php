<?php

declare(strict_types=1);

namespace App\Actions\Supervision;

use App\Actions\Approvals\AnnounceForApproval;
use App\Actions\Market\AcceptTrade;
use App\Actions\Market\BuyListing;
use App\Actions\Market\CreateListing;
use App\Actions\Market\CreateTrade;
use App\Domain\Dnd\Coins;
use App\Enums\SupervisedActionType;
use App\Models\Character;
use App\Models\MarketListing;
use App\Models\SupervisedAction;
use App\Models\Trade;
use App\Models\User;
use App\Notifications\SupervisedActionAwaitingApproval;

/**
 * Le pagine chiamano sempre questo, mai le azioni dirette: sotto richiamo
 * l'azione resta in attesa (`SupervisedAction`), altrimenti si esegue subito.
 * `ApproveSupervisedAction` chiama invece le azioni dirette: ripassare di qui,
 * col richiamo ancora attivo, girerebbe in tondo.
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
        int $giveCp = 0,
        int $wantCp = 0,
        ?string $message = null,
    ): Trade|SupervisedAction {
        if (! $actor->isUnderWarning()) {
            return app(CreateTrade::class)->handle($from, $to, $give, $want, $giveCp, $wantCp, $message);
        }

        return $this->hold($actor, SupervisedActionType::TradeProposal, [
            'from_character_id' => $from->getKey(),
            'to_character_id' => $to->getKey(),
            'give' => $give,
            'want' => $want,
            'give_cp' => $giveCp,
            'want_cp' => $wantCp,
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
        int $priceCp,
    ): MarketListing|SupervisedAction {
        if (! $actor->isUnderWarning()) {
            return app(CreateListing::class)->handle($seller, $itemName, $qty, $priceCp, $actor);
        }

        return $this->hold($actor, SupervisedActionType::ListingCreation, [
            'character_id' => $seller->getKey(),
            'name' => $itemName,
            'qty' => $qty,
            'price_cp' => $priceCp,
        ], "Vuole mettere in vendita {$qty}× {$itemName} per ".Coins::formatValue($priceCp));
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
        ], "Vuole comprare {$listing->qty}× {$listing->name} per ".Coins::formatValue($listing->price_cp));
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
