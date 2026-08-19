<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\PendingChangeStatus;
use App\Models\SupervisedAction;

/**
 * L'esito di un'azione controllata.
 *
 * A differenza di tuttie le altre notifiche, quando l'esito è un blocco questo
 * **riporta per intero le parole di chi ha deciso**: è la spiegazione che il
 * gruppo ha voluto rendere obbligatoria, e riassumerla la snaturerebbe.
 *
 * Come gli altri, non dice **chi** ha deciso.
 */
final class SupervisedActionDecided extends InAppNotification
{
    public function __construct(private readonly SupervisedAction $action) {}

    public function toArray(object $notifiable): array
    {
        $approved = $this->action->status === PendingChangeStatus::Approved;
        $what = $this->action->type->label();

        return [
            'title' => $approved
                ? "{$what}: via libera"
                : "{$what}: bloccata",
            'body' => $approved
                ? $this->action->summary.'. L\'operazione è stata eseguita.'
                : (string) $this->action->review_note,
            // Porta dov'è scritto per intero: la pagina delle proprie azioni
            // controllate (P29). Prima non portava da nessuna parte, e chi
            // voleva rileggere un blocco doveva ricordarselo a memoria.
            'url' => route('market.supervision'),
        ];
    }
}
