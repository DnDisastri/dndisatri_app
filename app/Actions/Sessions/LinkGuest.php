<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Models\SessionBooking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * L'ospite si è registrato: il suo posto passa al suo account, con lo stesso stato.
 * Se era già segnato presente, la presenza passa a lui. Con un solo eroe vivo il posto prende quello;
 * altrimenti il personaggio lo sceglie lui dalla sessione, e intanto nei combattimenti resta un ospite.
 */
final class LinkGuest
{
    public function handle(SessionBooking $posto, User $giocatore): SessionBooking
    {
        if (! $posto->isGuest()) {
            throw new InvalidArgumentException('Questo posto è già di un giocatore registrato.');
        }

        $sessione = $posto->session;

        if ($sessione->players()->whereKey($giocatore->getKey())->exists()) {
            throw new InvalidArgumentException("{$giocatore->name} ha già una prenotazione in questa sessione.");
        }

        return DB::transaction(function () use ($posto, $giocatore, $sessione) {
            $eroi = $giocatore->characters()->alive()->pluck('id');
            $unicoEroe = $eroi->count() === 1 ? $eroi->first() : null;

            if ($posto->guest_attended && ! $sessione->attended($giocatore)) {
                $sessione->attendees()->attach($giocatore, ['character_id' => $unicoEroe]);
            }

            $posto->forceFill([
                'user_id' => $giocatore->getKey(),
                'character_id' => $unicoEroe,
                'guest_name' => null,
                'guest_character' => null,
                'guest_note' => null,
                'guest_email' => null,
                'guest_phone' => null,
                'guest_social' => null,
                'guest_token' => null,
                'guest_attended' => false,
            ])->save();

            return $posto;
        });
    }
}
