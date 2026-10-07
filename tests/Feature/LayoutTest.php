<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Str;

it('serve i caratteri a chi non è ancora entrato', function () {
    $risposta = $this->get(route('login'));

    $risposta->assertOk()
        ->assertSee('Bowlby One', escape: false)
        ->assertSee('Quicksand', escape: false);
});

it('serve i caratteri a chi è entrato', function () {
    $risposta = $this->actingAs(User::factory()->player()->create())->get('/');

    $risposta->assertOk()
        ->assertSee('Bowlby One', escape: false)
        ->assertSee('Quicksand', escape: false);
});

it('non aspetta i caratteri da un dominio di terzi', function () {
    $this->get(route('login'))
        ->assertDontSee('fonts.googleapis.com')
        ->assertDontSee('fonts.bunny.net');
});

it('disegna la barra di navigazione con le icone Phosphor', function () {
    $risposta = $this->actingAs(User::factory()->player()->create())->get('/');

    expect(substr_count($risposta->getContent(), '<svg'))->toBeGreaterThanOrEqual(5);
});

it('accende una voce sola della barra, quella dove sei', function (string $indirizzo, string $voce) {
    $html = $this->actingAs(User::factory()->player()->create())
        ->get($indirizzo)
        ->assertOk()
        ->getContent();

    $barra = Str::of($html)->after('Navigazione principale')->before('</nav>')->toString();

    expect(substr_count($barra, 'aria-current="page"'))->toBe(1);

    expect($barra)->toMatch('/aria-label="'.preg_quote($voce, '/').'"[^>]*aria-current="page"[^>]*bg-active/s');
})->with([
    ['/campagne', 'Campagne'],
    ['/libro-mastro', 'Libro Mastro'],
    ['/personaggi', 'Eroi'],
    ['/mercato/emporio', 'Mercato'],
    ['/eventi', 'Eventi'],
]);

it('non accende niente sulle pagine fuori dalla barra', function () {
    $html = $this->actingAs(User::factory()->player()->create())
        ->get(route('guild.index'))
        ->assertOk()
        ->getContent();

    $barra = Str::of($html)->after('Navigazione principale')->before('</nav>')->toString();

    expect($barra)->not->toContain('aria-current="page"');
});
// La voce Eroi rappresenta i propri personaggi: visitare la scheda di un altro non deve attivarla.
it('e nemmeno sulla scheda di un altro', function () {
    $mio = Character::factory()->create();
    $altrui = Character::factory()->create();

    $barra = fn (string $html) => Str::of($html)
        ->after('Navigazione principale')->before('</nav>')->toString();

    $suo = $this->actingAs($mio->user)
        ->get(route('characters.show', $mio))
        ->assertOk()
        ->getContent();

    expect($barra($suo))->toContain('aria-current="page"');

    $suoAltrui = $this->actingAs($mio->user)
        ->get(route('characters.show', $altrui))
        ->assertOk()
        ->getContent();

    expect($barra($suoAltrui))->not->toContain('aria-current="page"');
});

it('tiene le cinque voci in tre blocchi', function () {
    $html = $this->actingAs(User::factory()->player()->create())->get('/')->getContent();

    $barra = Str::of($html)->after('Navigazione principale')->before('</nav>')->toString();

    expect(substr_count($barra, 'rounded-full bg-primary p-1.5'))->toBe(2)
        ->and(substr_count($barra, 'min-[360px]:h-16 min-[360px]:w-16'))->toBe(1);
});

// Il tema viene applicato inline prima del primo rendering per evitare un lampo del tema sbagliato.
it('applica il tema salvato prima di dipingere, in tutte e due i layout', function () {

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('dndisastri:tema', false);

    $this->actingAs(User::factory()->player()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('dndisastri:tema', false)
        ->assertSee('document.documentElement.dataset.theme', false);
});

it('offre i tre stati del tema nel menù', function () {
    $pagina = $this->actingAs(User::factory()->player()->create())->get('/')->assertOk();

    foreach (['auto', 'light', 'dark'] as $scelta) {
        $pagina->assertSee('data-tema="'.$scelta.'"', false);
    }
});

it('su desktop mette le voci della barra e il menù nella barra laterale', function () {
    $html = $this->actingAs(User::factory()->player()->create())
        ->get('/campagne')
        ->assertOk()
        ->getContent();

    $laterale = Str::of($html)->after('aria-label="Menù laterale"')->before('</nav>')->toString();

    foreach (['Eroi', 'Campagne', 'Libro Mastro', 'Mercato', 'Eventi'] as $voce) {
        expect($laterale)->toContain($voce);
    }

    expect(substr_count($laterale, 'aria-current="page"'))->toBe(1)
        ->and(Str::of($html)->after('<aside')->before('</aside>')->toString())
        ->toContain('Il mio profilo')
        ->toContain('data-tema="dark"');
});

// Il DM ha Gilda e Pannello nella barra: il menù non le ripete.
it('non ripete nel menù del DM le voci che ha già nella barra', function () {
    $html = $this->actingAs(User::factory()->dm()->create())->get('/')->getContent();

    $tendina = Str::of($html)->after('<details class="relative" data-tendina>')->before('</details>')->toString();

    expect($tendina)->toContain('Il mio profilo')
        ->not->toContain('Gilda')
        ->not->toContain('Pannello');
});

it('nasconde intestazione e barra in basso da desktop, la barra laterale sotto', function () {
    $html = $this->actingAs(User::factory()->player()->create())->get('/')->getContent();

    expect($html)->toMatch('/<header class="[^"]*\blg:hidden\b/')
        ->toMatch('/<nav class="[^"]*\blg:hidden\b[^"]*" aria-label="Navigazione principale"/')
        ->toMatch('/<aside class="[^"]*\bhidden\b[^"]*\blg:flex\b/');
});

// Gli avvisi stanno dentro <x-pagina>: prendono la larghezza della pagina, non sporgono.
it('mostra gli avvisi della sessione dentro il contenitore della pagina', function () {
    $html = $this->actingAs(User::factory()->player()->create())
        ->withSession(['status' => 'Tutto fatto', 'error' => 'Qualcosa è andato storto'])
        ->get(route('notifications.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toMatch('/class="[^"]*\bmax-w-3xl\b[^"]*">\s*<div class="mb-4 space-y-2">.*Tutto fatto.*Qualcosa è andato storto/s');
});
