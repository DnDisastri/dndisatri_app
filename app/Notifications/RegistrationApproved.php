<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;

/** Dice all'iscritto che può entrare: senza, lo scoprirebbe solo riprovando ad accedere. */
final class RegistrationApproved extends InAppNotification
{
    public function category(): NotificationCategory
    {
        return NotificationCategory::Requests;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'La tua iscrizione è approvata',
            'body' => 'Ti diamo il benvenuto in D&Disastri! Ora puoi accedere e creare il tuo personaggio.',
            'url' => route('login'),
        ];
    }
}
