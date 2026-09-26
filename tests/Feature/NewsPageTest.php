<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->admin = User::factory()->admin()->create();
});

it('elenca le news pubblicate', function () {
    Post::factory()->create([
        'title' => 'La gilda cambia sede',
        'excerpt' => 'Dal mese prossimo si gioca in cantina.',
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('news.index'))
        ->assertOk()
        ->assertSee('La gilda cambia sede')
        ->assertSee('Dal mese prossimo si gioca in cantina.');
});

it('non mostra le bozze né le programmate', function () {
    Post::factory()->draft()->create(['title' => 'Una Bozza']);
    Post::factory()->scheduled()->create(['title' => 'Esce Fra Una Settimana']);
    Post::factory()->create(['title' => 'Questa Si Vede']);

    $this->actingAs($this->giocatore)
        ->get(route('news.index'))
        ->assertOk()
        ->assertSee('Questa Si Vede')
        ->assertDontSee('Una Bozza')
        ->assertDontSee('Esce Fra Una Settimana');
});

it('mette in cima quelle in evidenza, e dice che lo sono', function () {
    Post::factory()->create([
        'title' => 'La Recente',
        'published_at' => now()->subDay(),
    ]);
    Post::factory()->pinned()->create([
        'title' => 'La Vecchia Ma Importante',
        'published_at' => now()->subMonths(6),
    ]);

    $html = $this->actingAs($this->giocatore)->get(route('news.index'))->assertOk()->getContent();

    expect(strpos($html, 'La Vecchia Ma Importante'))->toBeLessThan(strpos($html, 'La Recente'));

    $this->actingAs($this->giocatore)
        ->get(route('news.index'))
        ->assertSee('In evidenza');
});

it('lo dice quando non c\'è ancora niente', function () {
    $this->actingAs($this->giocatore)
        ->get(route('news.index'))
        ->assertOk()
        ->assertSee('Non c\'è ancora nessuna news.', false);
});

it('racconta la news per esteso', function () {
    $post = Post::factory()->create([
        'title' => 'La gilda cambia sede',
        'excerpt' => 'Dal mese prossimo si gioca in cantina.',
        'body' => 'La sala di sopra era diventata piccola per undici persone.',
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('news.show', $post))
        ->assertOk()
        ->assertSee('La gilda cambia sede')
        ->assertSee('Dal mese prossimo si gioca in cantina.')
        ->assertSee('La sala di sopra era diventata piccola')
        ->assertSee($post->author->name);
});
// Bozze e pubblicazioni programmate rispondono 404 per non rivelare l'esistenza di contenuti ancora nascosti.
it('su una news non pubblicata risponde 404 e non «non puoi»', function () {
    $bozza = Post::factory()->draft()->create();
    $programmata = Post::factory()->scheduled()->create();

    $this->actingAs($this->giocatore)->get(route('news.show', $bozza))->assertNotFound();
    $this->actingAs($this->giocatore)->get(route('news.show', $programmata))->assertNotFound();
});
// Gli admin possono aprire contenuti non pubblicati per revisionarli senza renderli visibili agli utenti.
it('all\'admin fa vedere la bozza, dicendogli che è una bozza', function () {
    $bozza = Post::factory()->draft()->create(['title' => 'Ancora Da Finire']);

    $this->actingAs($this->admin)
        ->get(route('news.show', $bozza))
        ->assertOk()
        ->assertSee('Ancora Da Finire')
        ->assertSee('bozza');
});

it('all\'admin dice quando esce una programmata', function () {
    $programmata = Post::factory()->scheduled()->create(['title' => 'Esce Fra Una Settimana']);

    $this->actingAs($this->admin)
        ->get(route('news.show', $programmata))
        ->assertOk()
        ->assertSee('Esce Fra Una Settimana')
        ->assertSee($programmata->published_at->translatedFormat('j F Y'));
});

it('mette la pillola sopra la copertina, e non accanto al titolo', function () {
    $post = Post::factory()->pinned()->create([
        'title' => 'La season 2 è cominciata',
        'cover_path' => 'news/copertina.jpg',
    ]);

    $html = $this->actingAs($this->giocatore)
        ->get(route('news.show', $post))->assertOk()->getContent();

    expect(strpos($html, 'In evidenza'))->toBeLessThan(strpos($html, '<h2'));
});

it('senza copertina la pillola torna accanto al titolo', function () {
    $post = Post::factory()->pinned()->create([
        'title' => 'La season 2 è cominciata',
        'cover_path' => null,
    ]);

    $html = $this->actingAs($this->giocatore)
        ->get(route('news.show', $post))->assertOk()->getContent();

    expect(strpos($html, 'In evidenza'))->toBeGreaterThan(strpos($html, '<h2'));
});

it('dalla Home si arriva all\'elenco e alla singola news', function () {
    $post = Post::factory()->create();

    $this->actingAs($this->giocatore)
        ->get('/')
        ->assertOk()
        ->assertSee(route('news.index'))
        ->assertSee(route('news.show', $post));
});
