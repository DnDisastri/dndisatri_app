<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\SessionBooking;

/** Il DM ti ha scelto: il posto è tuo se confermi entro la scadenza. */
final class SessionSeatOffered extends InAppNotification
{
    public function __construct(private readonly SessionBooking $posto) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Table;
    }

    public function alwaysEmail(): bool
    {
        return true;
    }

    public function buttonLabel(): string
    {
        return 'Conferma o rinuncia';
    }

    public function toArray(object $notifiable): array
    {
        $sessione = $this->posto->session;

        return [
            'title' => 'Hai un posto: confermi?',
            'body' => GuestBookingMail::sessionLine($sessione)."\n"
                .'Il posto è tuo se confermi entro '.GuestBookingMail::when($this->posto->offer_expires_at)
                .'. Se non puoi venire, rinuncia: il posto passa a qualcun altro.',
            'url' => route('sessions.show', $sessione),
        ];
    }
}
