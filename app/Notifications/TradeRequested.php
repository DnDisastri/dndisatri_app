<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domain\Dnd\Coins;
use App\Enums\NotificationCategory;
use App\Models\TradeRequest;

/** Qualcuno ti ha chiesto un oggetto: sei l'unico che sa se ce l'hai. */
final class TradeRequested extends InAppNotification
{
    public function __construct(
        private readonly TradeRequest $request,
        private readonly string $fromName,
    ) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Market;
    }

    public function toArray(object $notifiable): array
    {
        $offerto = array_filter([
            $this->request->offeredNames()->implode(', ') ?: null,
            $this->request->offered_cp > 0 ? Coins::formatValue($this->request->offered_cp) : null,
        ]);

        return [
            'title' => "{$this->fromName} ti chiede un oggetto",
            'body' => "Vorrebbe «{$this->request->wanted}» e offre "
                .($offerto === [] ? 'niente' : implode(' e ', $offerto)).'.',
            'url' => route('market.trades'),
        ];
    }
}
