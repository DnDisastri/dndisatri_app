<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('spiega in italiano un indirizzo che non esiste', function () {
    $this->actingAs(User::factory()->player()->create())
        ->get('/questa-pagina-non-e-mai-esistita')
        ->assertNotFound()
        ->assertSee('Pagina non trovata')
        ->assertSee('404');
});
// Il 404 deve renderizzarsi anche per un ospite senza dipendere da dati dell'utente autenticato.
it('vale anche per chi non ha fatto l\'accesso', function () {
    $this->get('/questa-pagina-non-e-mai-esistita')
        ->assertNotFound()
        ->assertSee('Pagina non trovata');
});

it('dice che non hai i permessi invece di sparire', function () {
    $altrui = Character::factory()->ownedBy(User::factory()->player()->create())->create();

    $this->actingAs(User::factory()->player()->create())
        ->get(route('characters.ledger', $altrui))
        ->assertForbidden()
        ->assertSee('Non hai i permessi');
});


it('non mostra il messaggio inglese di Laravel', function () {
    $altrui = Character::factory()->ownedBy(User::factory()->player()->create())->create();

    $this->actingAs(User::factory()->player()->create())
        ->get(route('characters.ledger', $altrui))
        ->assertForbidden()
        ->assertDontSee('This action is unauthorized');
});


it('riporta il perché quando chi ha chiuso la porta l\'ha scritto', function () {
    $html = view('errors.403', [
        'exception' => new HttpException(403, 'Serve un personaggio vivo per usare il mercato.'),
    ])->render();

    expect($html)->toContain('Serve un personaggio vivo per usare il mercato.');
});


it('ha una schermata in tema per il guasto e per la manutenzione', function (string $vista, string $atteso) {
    expect(view($vista)->render())->toContain($atteso);
})->with([
    ['errors.500', 'Qualcosa è andato storto'],
    ['errors.503', 'Torniamo subito'],
]);
// La pagina 500 deve poter essere renderizzata anche quando il database è la causa del guasto.
it('disegna il guasto senza toccare il database', function () {
    $query = 0;
    DB::listen(function () use (&$query) {
        $query++;
    });

    view('errors.500')->render();

    expect($query)->toBe(0);
});
