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
 * Il via libera a un'azione controllata: qui l'intenzione diventa realtà.
 *
 * L'intenzione salvata viene **rigiocata attraverso l'azione vera**, non
 * riapplicata a mano. È la stessa ragione per cui l'approvazione di una
 * richiesta non riscrive la scheda da sé: le regole stanno in un posto solo, e
 * quel posto le fa rispettare anche adesso.
 *
 * Il che significa che un via libera può **fallire**. Fra la richiesta e la
 * decisione il mondo si muove: l'oggetto può essere stato venduto, l'oro speso,
 * l'annuncio ritirato. In quel caso l'azione si rifiuta come farebbe
 * normalmente, e chi decide legge il perché.
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
                giveGp: (int) ($payload['give_gp'] ?? 0),
                wantGp: (int) ($payload['want_gp'] ?? 0),
                message: $payload['message'] ?? null,
            ),

            SupervisedActionType::TradeAcceptance => app(AcceptTrade::class)->handle(
                Trade::findOrFail($payload['trade_id']),
            ),

            SupervisedActionType::ListingCreation => app(CreateListing::class)->handle(
                seller: $this->character($payload['character_id']),
                itemName: $payload['name'],
                qty: (int) $payload['qty'],
                price: (int) $payload['price'],
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
