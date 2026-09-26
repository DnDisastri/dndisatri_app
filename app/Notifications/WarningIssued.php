<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Warning;

/**
 * «Hai ricevuto un richiamo».
 *
 * Questa notifica **dice il motivo**, e deve dirlo: è l'unica del sistema che
 * annuncia una limitazione, e una limitazione senza spiegazione è il modo più
 * rapido di trasformare una misura di controllo in un sospetto.
 */
final class WarningIssued extends InAppNotification
{
    public function __construct(private readonly Warning $warning) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Hai ricevuto un richiamo',
            'body' => $this->warning->reason
                .' Finché resta attivo, i tuoi scambi, gli annunci e gli acquisti'
                .' da altri giocatori devono essere approvati da un DM.'
                .' Il negozio della gilda resta libero.',
            'url' => null,
        ];
    }
}
