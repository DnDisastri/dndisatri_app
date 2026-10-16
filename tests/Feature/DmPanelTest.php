<?php

declare(strict_types=1);

use App\Enums\CreatureSize;
use App\Enums\CreatureType;
use App\Filament\Resources\DmRules\Pages\CreateDmRule;
use App\Livewire\CombatTracker;
use App\Models\Campaign;
use App\Models\DmRule;
use App\Models\Encounter;
use App\Models\Faq;
use App\Models\GameSession;
use App\Models\Monster;
use App\Models\TutorialStep;
use App\Models\User;
use Livewire\Livewire;

describe('il resoconto', function () {
    beforeEach(function () {
        $this->dm = User::factory()->dm()->create();
        $this->sessione = GameSession::factory()->create(['played_at' => now()->subDay()]);
    });

    it('si ferma a 3.600 caratteri, col contatore', function () {
        $this->actingAs($this->dm)
            ->post(route('sessions.recap', $this->sessione), ['recap' => str_repeat('a', 3601)])
            ->assertSessionHasErrors('recap');

        $this->actingAs($this->dm)
            ->get(route('sessions.show', $this->sessione))
            ->assertSee('/ 3.600');
    });

    it('uno scritto prima del limite si corregge, ma non si allunga', function () {
        $this->sessione->forceFill(['recap' => str_repeat('a', 5000)])->save();

        $this->actingAs($this->dm)
            ->post(route('sessions.recap', $this->sessione), ['recap' => str_repeat('b', 4800)])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->dm)
            ->post(route('sessions.recap', $this->sessione), ['recap' => str_repeat('b', 5001)])
            ->assertSessionHasErrors('recap');
    });
});

describe('le FAQ', function () {
    it('si leggono anche senza account, senza il tutorial', function () {
        Faq::factory()->create(['question' => 'Come mi iscrivo?']);
        TutorialStep::factory()->create(['title' => 'La barra in basso', 'is_published' => true]);

        $this->get(route('faq.index'))
            ->assertOk()
            ->assertSee('Come mi iscrivo?')
            ->assertDontSee('Leggi il tutorial')
            ->assertDontSee('La barra in basso');
    });

    it('ognuna si mostra nella pagina pubblica, in quella privata o in tutte e due', function () {
        Faq::factory()->create(['question' => 'Per tutti?']);
        Faq::factory()->create(['question' => 'Solo fuori?', 'show_private' => false]);
        Faq::factory()->create(['question' => 'Solo dentro?', 'show_public' => false]);

        $this->get(route('faq.index'))
            ->assertSee('Per tutti?')->assertSee('Solo fuori?')->assertDontSee('Solo dentro?');

        $this->actingAs(User::factory()->player()->create())->get(route('faq.index'))
            ->assertSee('Per tutti?')->assertSee('Solo dentro?')->assertDontSee('Solo fuori?');
    });

    it('la presentazione porta alle FAQ', function () {
        $this->get('/')->assertSee(route('faq.index'));
    });
});

describe('le regole del DM', function () {
    it('le leggono i DM, per sezione', function () {
        DmRule::factory()->inSection('Ricompense')->create([
            'title' => 'Livello 1', 'body' => 'Ai personaggi di livello 1 si danno 10 mo.',
        ]);

        $this->actingAs(User::factory()->dm()->create())
            ->get(route('dm.rules'))
            ->assertOk()
            ->assertSeeInOrder(['Ricompense', 'Livello 1', 'Ai personaggi di livello 1 si danno 10 mo.']);
    });

    it('i giocatori non le vedono', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(route('dm.rules'))
            ->assertForbidden();
    });

    it('le scrivono gli admin dal pannello, non i DM', function () {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(CreateDmRule::class)
            ->fillForm(['category' => 'Ricompense', 'title' => 'Livello 1', 'body' => '10 mo a sessione.'])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(DmRule::where('title', 'Livello 1')->exists())->toBeTrue()
            ->and(User::factory()->dm()->create()->can('create', DmRule::class))->toBeFalse();
    });
});
