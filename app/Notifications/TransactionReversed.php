<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * «Una transazione è stata annullata».
 *
 * Va a **entrambe** le parti, anche a chi ci ha guadagnato dall'annullamento:
 * scoprire che il proprio inventario è cambiato senza sapere perché sarebbe
 * peggio del torto iniziale.
 *
 * Come tuttie le altre notifiche non dice chi ha deciso — l'annullamento lo fa un
 * admin, e gli admin non compaiono davanti ai giocatori. Dice però **perché**,
 * con le parole di chi è intervenuto.
 */
final class TransactionReversed extends InAppNotification
{
    public function __construct(
        private readonly string $what,
        private readonly string $reason,
    ) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Una transazione è stata annullata',
            'body' => "{$this->what}. Motivo: {$this->reason} "
                .'Controlla il Registro: oggetti e oro sono già tornati a posto.',
            'url' => null,
        ];
    }
}
