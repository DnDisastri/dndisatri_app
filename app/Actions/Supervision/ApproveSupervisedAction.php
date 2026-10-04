<?php

declare(strict_types=1);

namespace App\Actions\Supervision;

use App\Actions\Market\AcceptTrade;
use App\Actions\Market\BuyListing;
use App\Actions\Market\CreateListing;
use App\Actions\Market\CreateTrade;
use App\Enums\PendingChangeStatus;
use App\Enums\SupervisedActionType;
use App\Models\Character;
use App\Models\MarketListing;
use App\Models\SupervisedAction;
use App\Models\Trade;
use App\Models\User;
use App\Notifications\SupervisedActionDecided;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * L'intenzione salvata si rigioca attraverso l'azione vera, che rifà i suoi
 * controlli: un via libera può quindi fallire se nel frattempo il mondo è cambiato.
 */
final class ApproveSupervisedAction
{
    public function handle(SupervisedAction $action, User $reviewer): Trade|MarketListing
    {
        return DB::transaction(function () use ($action, $reviewer) {
            $locked = SupervisedAction::whereKey($action->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new RuntimeException('Questa richiesta è già stata decisa.');
            }

            $result = $this->replay($locked);

            $locked->forceFill([
                'status' => PendingChangeStatus::Approved,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
            ])->save();

            $locked->user()->first()?->notify(new SupervisedActionDecided($locked));

            return $result;
        });
    }

    private function replay(SupervisedAction $action): Trade|MarketListing
    {
        $payload = $action->payload ?? [];

        return match ($action->type) {
            SupervisedActionType::TradeProposal => app(CreateTrade::class)->handle(
                from: $this->character($payload['from_character_id']),
                to: $this->character($payload['to_character_id']),
                give: $payload['give'] ?? [],
                want: $payload['want'] ?? [],
                giveCp: (int) ($payload['give_cp'] ?? 0),
                wantCp: (int) ($payload['want_cp'] ?? 0),
                message: $payload['message'] ?? null,
            ),

            SupervisedActionType::TradeAcceptance => app(AcceptTrade::class)->handle(
                Trade::findOrFail($payload['trade_id']),
            ),

            SupervisedActionType::ListingCreation => app(CreateListing::class)->handle(
                seller: $this->character($payload['character_id']),
                itemName: $payload['name'],
                qty: (int) $payload['qty'],
                priceCp: (int) $payload['price_cp'],
            ),

            SupervisedActionType::ListingPurchase => app(BuyListing::class)->handle(
                listing: MarketListing::findOrFail($payload['listing_id']),
                buyer: $this->character($payload['buyer_character_id']),
            ),
        };
    }

    private function character(int|string $id): Character
    {
        return Character::findOrFail($id);
    }
}
