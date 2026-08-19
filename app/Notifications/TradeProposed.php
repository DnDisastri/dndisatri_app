<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Trade;

/** Qualcuno ti ha proposto uno scambio: tocca a te rispondere. */
final class TradeProposed extends InAppNotification
{
    public function __construct(
        private readonly Trade $trade,
        private readonly string $fromName,
    ) {}

    public function toArray(object $notifiable): array
    {
        $offered = $this->trade->givenItems()->pluck('name')->all();
        $wanted = $this->trade->wantedItems()->pluck('name')->all();

        return [
            'title' => "{$this->fromName} ti propone uno scambio",
            'body' => $this->describe($offered, $this->trade->give_gp)
                .' in cambio di '
                .$this->describe($wanted, $this->trade->want_gp).'.',
            'url' => null,
        ];
    }

    /** @param list<string> $items */
    private function describe(array $items, int $gp): string
    {
        $parts = array_filter([
            $items === [] ? null : implode(', ', $items),
            $gp > 0 ? "{$gp} mo" : null,
        ]);

        return $parts === [] ? 'niente' : implode(' e ', $parts);
    }
}
