<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Le notifiche del gruppo, tutte sullo stesso canale.
 *
 * Vanno **in applicazione**, non per email: il gruppo si vede al tavolo e la
 * posta non è ancora configurata. Restano salvate, così chi rientra dopo una
 * settimana trova quello che si è perso.
 *
 * Ogni notifica dice tre cose: un titolo, una riga di spiegazione e dove
 * andare a vedere. Niente di più — la scheda è a un clic, e ripetere qui i
 * dettagli significherebbe tenerli allineati in due posti.
 */
abstract class InAppNotification extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{title: string, body: string, url: string|null} */
    abstract public function toArray(object $notifiable): array;
}
