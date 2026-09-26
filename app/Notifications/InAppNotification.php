<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Le notifiche del gruppo.
 *
 * Restano sempre in applicazione, così chi rientra dopo una settimana trova
 * quello che si è perso. In più partono per email, categoria per categoria,
 * secondo quello che il giocatore ha scelto nel profilo.
 *
 * Ogni notifica dice tre cose: un titolo, una riga di spiegazione e dove
 * andare a vedere. Niente di più, e l'email non le riscrive: le impagina
 * soltanto. Un testo solo, e nessun rischio che i due si scollino.
 *
 * Vanno in coda: un evento pubblicato avvisa tutto il gruppo, e trenta invii
 * dentro una richiesta la farebbero scadere.
 */
abstract class InAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** A quale interruttore del profilo risponde questa notifica. */
    abstract public function category(): NotificationCategory;

    /** @return array{title: string, body: string, url: string|null} */
    abstract public function toArray(object $notifiable): array;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $canali = ['database'];

        if ($notifiable instanceof User && $notifiable->wantsEmailFor($this->category())) {
            $canali[] = 'mail';
        }

        return $canali;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $contenuto = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($contenuto['title'])
            ->view('emails.notifica', [
                'titolo' => $contenuto['title'],
                'corpo' => $contenuto['body'],
                'indirizzo' => $contenuto['url'],
                'destinatario' => $notifiable,
                'categoria' => $this->category(),
            ]);
    }
}
