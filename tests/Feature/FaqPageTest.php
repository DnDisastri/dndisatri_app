<?php

declare(strict_types=1);

use App\Models\Faq;
use App\Models\User;

beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
});

it('mostra le voci pubblicate, raggruppate per sezione', function () {
    Faq::factory()->inSection('Eroi')->create([
        'question' => 'Come creo un personaggio?',
        'answer' => 'Dalla sezione Eroi, con il pulsante Nuovo.',
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('faq.index'))
        ->assertOk()
        ->assertSee('Eroi')
        ->assertSee('Come creo un personaggio?')
        ->assertSee('Dalla sezione Eroi, con il pulsante Nuovo.');
});

it('non mostra le bozze', function () {
    Faq::factory()->create(['question' => 'Questa Si Vede']);
    Faq::factory()->draft()->create(['question' => 'Questa E Una Bozza']);

    $this->actingAs($this->giocatore)
        ->get(route('faq.index'))
        ->assertOk()
        ->assertSee('Questa Si Vede')
        ->assertDontSee('Questa E Una Bozza');
});

it('rispetta l\'ordine deciso nel pannello', function () {
    Faq::factory()->create(['question' => 'La Seconda', 'position' => 2]);
    Faq::factory()->create(['question' => 'La Prima', 'position' => 1]);

    $html = $this->actingAs($this->giocatore)
        ->get(route('faq.index'))->assertOk()->getContent();

    expect(strpos($html, 'La Prima'))->toBeLessThan(strpos($html, 'La Seconda'));
});

it('lo dice quando la guida è ancora vuota', function () {
    $this->actingAs($this->giocatore)
        ->get(route('faq.index'))
        ->assertOk()
        ->assertSee('La guida non è ancora pronta.');
});

it('si raggiunge dal menù in alto', function () {
    $this->actingAs($this->giocatore)
        ->get('/')
        ->assertOk()
        ->assertSee(route('faq.index'));
});

it('mostra il pulsante e i passi del tutorial pubblicati', function () {
    \App\Models\TutorialStep::factory()->create([
        'title' => 'La barra in basso',
        'body' => 'Cinque destinazioni sempre a portata di pollice.',
        'is_published' => true,
    ]);

    $this->actingAs($this->giocatore)
        ->get(route('faq.index'))
        ->assertOk()
        ->assertSee('Leggi il tutorial')
        ->assertSee('La barra in basso')
        ->assertSee('Cinque destinazioni sempre a portata di pollice.');
});

it('non mostra i passi del tutorial in bozza', function () {
    \App\Models\TutorialStep::factory()->draft()->create(['title' => 'Passo In Bozza']);

    $this->actingAs($this->giocatore)
        ->get(route('faq.index'))
        ->assertOk()
        ->assertDontSee('Passo In Bozza');
});

it('senza passi non mostra il pulsante del tutorial', function () {
    $this->actingAs($this->giocatore)
        ->get(route('faq.index'))
        ->assertOk()
        ->assertDontSee('Leggi il tutorial');
});
