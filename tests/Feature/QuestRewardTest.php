<?php

declare(strict_types=1);

use App\Filament\Resources\Quests\Pages\CreateQuest;
use App\Models\Campaign;
use App\Models\Quest;
use App\Models\User;
use Livewire\Livewire;

// Una quest è valida se possiede almeno una forma di ricompensa tra oro, oggetti o testo libero.
describe('hasReward', function () {
    it('è vera con il solo oro', function () {
        expect(Quest::factory()->make(['reward_gold' => 100, 'reward_items' => null, 'rewards' => null])->hasReward())
            ->toBeTrue();
    });

    it('è vera con i soli oggetti', function () {
        expect(Quest::factory()->make(['reward_gold' => null, 'reward_items' => ['Spada +1'], 'rewards' => null])->hasReward())
            ->toBeTrue();
    });

    it('è vera col solo testo libero', function () {
        expect(Quest::factory()->make(['reward_gold' => null, 'reward_items' => null, 'rewards' => 'Un favore del barone'])->hasReward())
            ->toBeTrue();
    });

    it('è falsa quando non c\'è niente', function () {
        expect(Quest::factory()->make(['reward_gold' => 0, 'reward_items' => [], 'rewards' => null])->hasReward())
            ->toBeFalse();
    });
});

describe('la pagina della quest', function () {
    it('mostra oro, oggetti e testo libero, ciascuno se c\'è', function () {
        $quest = Quest::factory()->create([
            'reward_gold' => 100,
            'reward_items' => ['Pozione di guarigione superiore', 'Mantello elfico'],
            'rewards' => 'Il favore della gilda dei ladri.',
        ]);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('quests.show', $quest))
            ->assertOk()
            ->assertSee('100 mo')
            ->assertSee('Pozione di guarigione superiore')
            ->assertSee('Mantello elfico')
            ->assertSee('Il favore della gilda dei ladri.');
    });

    it('non stampa l\'oro quando è zero', function () {
        $quest = Quest::factory()->create([
            'reward_gold' => 0,
            'reward_items' => ['Un anello misterioso'],
            'rewards' => null,
        ]);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('quests.show', $quest))
            ->assertOk()
            ->assertSee('Un anello misterioso')
            ->assertDontSee('0 mo');
    });
});

describe('il modulo del dungeon master', function () {
    it('rifiuta una quest senza nessuna ricompensa', function () {
        $this->actingAs(User::factory()->admin()->create());
        $campagna = Campaign::factory()->create();

        Livewire::test(CreateQuest::class)
            ->fillForm([
                'campaign_id' => $campagna->id,
                'difficulty' => 'Media',
                'title' => 'Senza ricompensa',
                'slug' => 'senza-ricompensa',
                'description' => 'Una quest a cui manca il compenso.',
                'reward_gold' => null,
                'reward_items' => [],
                'rewards' => null,
                'min_participants' => 3,
                'max_participants' => 5,
            ])
            ->call('create')
            ->assertHasFormErrors(['reward_gold']);
    });

    it('accetta una quest col solo oro', function () {
        $this->actingAs(User::factory()->admin()->create());
        $campagna = Campaign::factory()->create();

        Livewire::test(CreateQuest::class)
            ->fillForm([
                'campaign_id' => $campagna->id,
                'difficulty' => 'Media',
                'title' => 'Con oro',
                'slug' => 'con-oro',
                'description' => 'Una quest che paga in monete.',
                'reward_gold' => 150,
                'min_participants' => 3,
                'max_participants' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Quest::where('slug', 'con-oro')->first()->reward_gold)->toBe(150);
    });
});
