<?php

declare(strict_types=1);

use App\Actions\Characters\KillCharacter;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\User;


beforeEach(function () {
    $this->chiGuarda = User::factory()->player()->create();

    $this->delia = User::factory()->player()->create(['name' => 'Delia']);
    $this->yorick = Character::factory()->ownedBy($this->delia)->create([
        'name' => 'Povero Yorick', 'race' => 'Halfling', 'class' => 'Bardo', 'level' => 4,
    ]);
});

function uccidi(Character $character, ?string $racconto = null, ?GameSession $serata = null): Character
{
    return app(KillCharacter::class)->handle(
        $character, User::factory()->dm()->create(), $racconto, $serata,
    );
}

describe('il memoriale di un caduto', function () {
    it('racconta chi era e come è andata', function () {
        uccidi($this->yorick, 'Si è messo in mezzo fra il drago e la bambina.');

        $this->actingAs($this->chiGuarda)
            ->get(route('fallen.show', $this->yorick))
            ->assertOk()
            ->assertSee('Povero Yorick')
            ->assertSee('Halfling')
            ->assertSee('Bardo')
            ->assertSee('Delia')
            ->assertSee('Caduto il')
            ->assertSee('Si è messo in mezzo fra il drago e la bambina.');
    });
// Il memoriale può collegarsi a una serata, ma la morte deve poter essere registrata anche senza sessione.
    it('e quando è morto a un tavolo, porta a quella serata', function () {
        $campagna = Campaign::factory()->create(['title' => 'I Tre Regni']);
        $serata = GameSession::factory()->for($campagna)->create([
            'number' => 12, 'title' => 'La Torre Nera',
        ]);

        uccidi($this->yorick, 'Caduto dalla torre.', $serata);

        $this->actingAs($this->chiGuarda)
            ->get(route('fallen.show', $this->yorick))
            ->assertOk()
            ->assertSee('Sessione 12')
            ->assertSee('La Torre Nera')
            ->assertSee('I Tre Regni')
            ->assertSee(route('sessions.show', $serata), false);
    });

    it('e quando è morto fuori dal tavolo, la serata non si inventa', function () {
        uccidi($this->yorick, 'Un incidente stupido, in una locanda.');

        $this->actingAs($this->chiGuarda)
            ->get(route('fallen.show', $this->yorick))
            ->assertOk()
            ->assertDontSee('Sessione');
    });

    it('e senza racconto lo dice, invece di sparire', function () {
        uccidi($this->yorick);

        $this->actingAs($this->chiGuarda)
            ->get(route('fallen.show', $this->yorick))
            ->assertOk()
            ->assertSee('Com\'è andata')
            ->assertSee('non è rimasto scritto niente');
    });
// Anche il racconto scritto dal DM è testo libero e deve essere mostrato senza eseguire HTML arbitrario.
    it('e il racconto non esegue HTML', function () {

        uccidi($this->yorick, '<script>alert(1)</script> morte onorevole');

        $this->actingAs($this->chiGuarda)
            ->get(route('fallen.show', $this->yorick))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', escape: false)
            ->assertSee('morte onorevole');
    });
// Il memoriale esiste solo per i personaggi effettivamente caduti.
    it('ma di chi è vivo non c\'è nessun memoriale', function () {
        $this->actingAs($this->chiGuarda)
            ->get(route('fallen.show', $this->yorick))
            ->assertNotFound();
    });

    it('ed è chiuso agli ospiti come tutto il resto', function () {
        uccidi($this->yorick);

        $this->get(route('fallen.show', $this->yorick))->assertRedirect(route('login'));
    });

    it('e da lì si torna alla sua scheda', function () {
        uccidi($this->yorick);

        $this->actingAs($this->chiGuarda)
            ->get(route('fallen.show', $this->yorick))
            ->assertOk()
            ->assertSee(route('characters.show', $this->yorick), false);
    });

    it('e in cima si torna fra i caduti', function () {
        uccidi($this->yorick);

        $this->actingAs($this->chiGuarda)
            ->get(route('fallen.show', $this->yorick))
            ->assertOk()
            ->assertSee('Torna alla Gilda')
            ->assertSee(route('guild.index').'#caduti', false);
    });
});
