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
 * Le notifiche del gruppo: sempre in app, e per email secondo le categorie
 * scelte nel profilo. Titolo, una riga e un link; l'email impagina lo stesso
 * testo. In coda, perché un avviso a tutto il gruppo non blocchi la richiesta.
 */
abstract class InAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Il limitatore di invii, registrato in AppServiceProvider. */
    public const LIMITATORE = 'notifiche-email';

    /** Errori veri dopo cui arrendersi; le attese del limitatore le governa `retryUntil`. */
    public int $maxExceptions = 3;

    /** A quale interruttore del profilo risponde questa notifica. */
    abstract public function category(): NotificationCategory;

    /**
     * Frena solo le email: l'hosting ne accetta 250 l'ora, e le eccedenti tornano
     * in coda invece di fallire. La notifica in app resta immediata.
     *
     * @return list<object>
     */
    public function middleware(object $notifiable, string $channel): array
    {
        return $channel === 'mail' ? [new RateLimited(self::LIMITATORE)] : [];
    }

    /** Abbastanza da superare l'ora in cui si è raggiunto il tetto. */
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
