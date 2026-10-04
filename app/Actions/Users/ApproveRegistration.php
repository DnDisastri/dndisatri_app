<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\RegistrationApproved;
use RuntimeException;

final class ApproveRegistration
{
    public function handle(User $target, User $admin): void
    {
        if (! $admin->isAdmin()) {
            throw new RuntimeException('Solo un amministratore può approvare un iscritto.');
        }

        // L'update condizionato evita la doppia email se due admin approvano insieme.
        $approvato = User::whereKey($target->getKey())
            ->whereNull('approved_at')
            ->update(['approved_at' => now()]);

        $target->refresh();

        if ($approvato === 0 || ! self::sendsConfirmation($target)) {
            return;
        }

        $target->notify(new RegistrationApproved);
    }

    /**
     * Solo chi si è iscritto dopo l'arrivo della conferma: la domanda «Hai già fatto
     * sessioni con noi?» è nata con lei ed è obbligatoria, chi c'era prima ce l'ha vuota.
     */
    public static function sendsConfirmation(User $user): bool
    {
        return $user->played_before !== null;
    }
}
