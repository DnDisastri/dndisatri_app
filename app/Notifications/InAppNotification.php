<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimited;

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

    /** Il limitatore di invii, registrato in AppServiceProvider. */
    public const LIMITATORE = 'notifiche-email';

    /**
     * Gli errori veri dopo cui arrendersi.
     *
     * Le attese del limitatore non sono errori e non contano qui: quelle le
     * governa `retryUntil`.
     */
    public int $maxExceptions = 3;

    /** A quale interruttore del profilo risponde questa notifica. */
    abstract public function category(): NotificationCategory;

    /**
     * Il freno sugli invii, e solo su quelli.
     *
     * L'hosting accetta 250 email l'ora su tutto l'account, e un DM che
     * programma il calendario di un mese ne genererebbe una per giocatore per
     * ogni serata: 240 in pochi minuti, e l'invio si blocca. Con il limitatore
     * le eccedenti tornano in coda e partono più tardi, invece di finire fra i
     * job falliti senza che nessuno se ne accorga.
     *
     * Laravel accoda un job per canale, quindi la notifica in applicazione
     * resta immediata: qui si frena la posta soltanto.
     *
     * @return list<object>
     */
    public function middleware(object $notifiable, string $channel): array
    {
        return $channel === 'mail' ? [new RateLimited(self::LIMITATORE)] : [];
    }

    /**
     * Quanto insistere.
     *
     * Serve una finestra abbastanza larga da attraversare l'ora in cui il
     * tetto è stato raggiunto: a quel punto il contatore riparte e la coda si
     * svuota da sola.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(3);
    }

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
