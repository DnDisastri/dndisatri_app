<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\SessionBooking;

/**
 * Un posto è tornato libero: chi l'aveva si è ritirato, ha rinunciato o non
 * ha confermato in tempo. Al DM della campagna e agli admin, con le riserve:
 * nessuno entra da solo al suo posto.
 */
final class SessionSeatReleased extends InAppNotification
{
    public const WITHDRAWN = 'withdrawn';

    public const DECLINED = 'declined';

    public const EXPIRED = 'expired';

    public function __construct(
        private readonly SessionBooking $posto,
        private readonly string $motivo,
    ) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Table;
    }

    public function toArray(object $notifiable): array
    {
        $sessione = $this->posto->session;
        $nome = $this->posto->displayName();

        $riserve = $sessione->bookings()->reserves()->with('user')->get()
            ->map(fn (SessionBooking $r) => $r->displayName())
            ->join(', ', ' e ');

        return [
            'title' => match ($this->motivo) {
                self::DECLINED => "{$nome} ha rinunciato al posto",
                self::EXPIRED => "{$nome} non ha confermato in tempo",
                default => "{$nome} ha lasciato il posto",
            },
            'body' => GuestBookingMail::sessionLine($sessione)."\n"
                .'C\'è un posto libero. '
                .($riserve !== '' ? "Riserve: {$riserve}." : 'Non ci sono riserve: guarda fra le richieste.'),
            'url' => route('sessions.show', $sessione),
        ];
    }
}
