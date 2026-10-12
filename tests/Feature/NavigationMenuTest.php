<?php

declare(strict_types=1);

use App\Enums\SeatStatus;
use App\Models\GameSession;
use App\Models\SessionBooking;
use App\Models\User;
use App\Support\Navigazione;

function vociDelMenu(User $utente): array
{
    return array_column(Navigazione::menu($utente), 'nome');
}

describe('«Le mie prenotazioni» nel menu', function () {
    it('c\'è per i giocatori', function () {
        expect(vociDelMenu(User::factory()->player()->create()))->toContain('Le mie prenotazioni');
    });

    it('non c\'è per DM e admin', function () {
        expect(vociDelMenu(User::factory()->dm()->create()))->not->toContain('Le mie prenotazioni')
            ->and(vociDelMenu(User::factory()->admin()->create()))->not->toContain('Le mie prenotazioni');
    });

    it('torna per un DM che si è prenotato come giocatore', function () {
        $dm = User::factory()->dm()->create();

        SessionBooking::forceCreate([
            'game_session_id' => GameSession::factory()->upcoming()->create()->id,
            'user_id' => $dm->id,
            'status' => SeatStatus::Requested->value,
        ]);

        expect(vociDelMenu($dm))->toContain('Le mie prenotazioni');
    });
});
