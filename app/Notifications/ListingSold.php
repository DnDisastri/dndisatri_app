<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domain\Dnd\Coins;
use App\Enums\NotificationCategory;
use App\Models\MarketListing;

/** Qualcuno ha comprato quello che avevi messo in vendita. */
final class ListingSold extends InAppNotification
{
    public function __construct(
        private readonly MarketListing $listing,
        private readonly string $buyerName,
    ) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Market;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Hai venduto '.$this->listing->qty.'× '.$this->listing->name,
            'body' => "{$this->buyerName} l'ha comprato per ".Coins::formatValue($this->listing->price_cp)
                .', che sono già nella tua borsa.',
            'url' => null,
        ];
    }
}
