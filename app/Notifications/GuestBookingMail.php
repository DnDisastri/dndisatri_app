<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\SeatStatus;
use App\Models\GameSession;
use App\Models\SessionBooking;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Carbon;

/**
 * Le email a un ospite senza account: verificare l'indirizzo, confermare il
 * posto, restare come riserva, sapere che un DM l'ha aggiunto. Il link porta alla sua pagina personale.
 */
final class GuestBookingMail extends Notification implements ShouldQueue
{
    use Queueable;

    public const VERIFY = 'verify';

    public const OFFERED = 'offered';

    public const RESERVE = 'reserve';

    /** Aggiunto da un DM: confermato, o fra le riserve se era pieno. */
    public const ADDED = 'added';

    public int $maxExceptions = 3;

    /** @param  list<string>  $sessioni  per verificare più richieste insieme: una riga per sessione */
    public function __construct(
        private readonly SessionBooking $posto,
        private readonly string $tipo,
        private readonly array $sessioni = [],
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** @return list<object> */
    public function middleware(object $notifiable, string $channel): array
    {
        return [new RateLimited(InAppNotification::LIMITATORE)];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(3);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sessione = $this->posto->session;
        $riga = self::sessionLine($sessione);

        [$titolo, $corpo, $pulsante] = match ($this->tipo) {
            self::VERIFY => count($this->sessioni) > 1
                ? [
                    'Conferma la tua email',
                    "Hai chiesto un posto a queste sessioni:\n- ".implode("\n- ", $this->sessioni)
                        ."\nApri il link per confermare le richieste, tutte insieme: senza questo passaggio il dungeon master non le vede.",
                    'Conferma le richieste',
                ]
                : [
                    'Conferma la tua email',
                    $riga."\nApri il link per confermare la richiesta: senza questo passaggio il dungeon master non la vede.",
                    'Conferma la richiesta',
                ],
            self::OFFERED => [
                'Hai un posto: confermi?',
                $riga."\nIl posto è tuo se confermi entro ".self::when($this->posto->offer_expires_at)
                    .'. Se non puoi venire, rinuncia: il posto passa a qualcun altro.',
                'Conferma o rinuncia',
            ],
            self::ADDED => $this->posto->status === SeatStatus::Confirmed
                ? [
                    'Hai un posto confermato',
                    $riga."\nIl dungeon master ti ha aggiunto alla sessione. Se non puoi più venire, disdici dal link: il posto passa a qualcun altro.",
                    'Vedi la prenotazione',
                ]
                : [
                    'Sei fra le riserve',
                    $riga."\nI posti sono pieni: il dungeon master ti ha messo fra le riserve. Se si libera un posto potresti essere chiamato, ma non è garantito.",
                    'Vedi la prenotazione',
                ],
            default => [
                'Sessione piena: resti fra le riserve?',
                $riga."\nI posti sono tutti confermati. Se resti fra le riserve, potresti essere chiamato "
                    .'se qualcuno si ritira, ma non è garantito.',
                'Rispondi',
            ],
        };

        return (new MailMessage)
            ->subject($titolo)
            ->view('emails.notifica', [
                'titolo' => $titolo,
                'corpo' => $corpo,
                'indirizzo' => $this->tipo === self::VERIFY
                    ? route('guest-bookings.verify', $this->posto->guest_token)
                    : $this->posto->guestUrl(),
                'nome' => $this->posto->guest_name,
                'categoria' => null,
                'pulsante' => $pulsante,
            ]);
    }

    /** «Campagna», titolo e data: la stessa riga in tutte le email delle prenotazioni. */
    public static function sessionLine(GameSession $sessione): string
    {
        $campagna = $sessione->campaign?->title;

        return ($campagna ? "«{$campagna}», " : '').$sessione->displayTitle().', '.self::when($sessione->played_at).'.';
    }

    public static function when(?Carbon $momento): string
    {
        return $momento?->translatedFormat('l j F \\a\\l\\l\\e H:i') ?? '';
    }
}
