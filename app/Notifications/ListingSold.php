<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MarketListing;

/** Qualcuno ha comprato quello che avevi messo in vendita. */
final class ListingSold extends InAppNotification
{
    public function __construct(
        private readonly MarketListing $listing,
        private readonly string $buyerName,
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Hai venduto '.$this->listing->qty.'× '.$this->listing->name,
            'body' => "{$this->buyerName} l'ha comprato per {$this->listing->price} mo, "
                .'che sono già sul tuo conto.',
            'url' => null,
        ];
    }
}
