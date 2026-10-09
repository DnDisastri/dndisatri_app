<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Models\SessionBooking;
use App\Models\User;
use App\Notifications\SessionSeatReleased;
use Illuminate\Support\Facades\Notification;

/** Un posto libero lo sanno il DM della campagna e gli admin: decidono loro chi chiamare. */
final class NotifySeatReleased
{
    public function handle(SessionBooking $posto, string $motivo): void
    {
        $destinatari = User::whereHas('roles', fn ($ruoli) => $ruoli->where('name', User::ROLE_ADMIN))->get();

        if ($dm = $posto->session->campaign?->dm) {
            $destinatari->push($dm);
        }

        Notification::send($destinatari->unique('id'), new SessionSeatReleased($posto, $motivo));
    }
}
