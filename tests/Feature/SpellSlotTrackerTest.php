<?php

declare(strict_types=1);

use App\Livewire\SpellSlotTracker;
use App\Models\Character;
use Livewire\Livewire;


function conClassi(Character $character, array $livelli): Character
{
    $prima = true;
    foreach ($livelli as $classe => $livello) {
        $character->classes()->create(['class' => $classe, 'level' => $livello, 'is_primary' => $prima]);
        $prima = false;
    }

    return $character->fresh();
}
// Nei multiclassi con Warlock gli slot normali e gli slot da patto sono due riserve indipendenti.
describe('il Warlock multiclasse', function () {
    beforeEach(function () {
        $this->pg = conClassi(
            Character::factory()->create(['class' => 'Warlock', 'level' => 5, 'spell_ability' => 'cha']),
            ['Warlock' => 3, 'Stregone' => 2],
        );
    });

    it('mostra tutte e due le riserve, ognuna con la sua etichetta', function () {
        Livewire::actingAs($this->pg->user)
            ->test(SpellSlotTracker::class, ['character' => $this->pg])
            ->assertSee('Slot disponibili')
            ->assertSee('Slot da patto')
            ->assertSee('tornano anche col riposo breve');
    });

    it('e i due conti non si confondono', function () {
        expect($this->pg->spellSlots()->slots)->toBe([1 => 3])
            ->and($this->pg->spellSlots()->isPact)->toBeFalse()
            ->and($this->pg->pactSlots()->total())->toBe(2)
            ->and($this->pg->pactSlots()->isPact)->toBeTrue();
    });
// `pact` non è un livello numerico: il tracker deve instradare la spesa alla riserva da patto corretta.
    it('lascia spendere uno slot da patto', function () {
        Livewire::actingAs($this->pg->user)
            ->test(SpellSlotTracker::class, ['character' => $this->pg])
            ->call('spend', 'pact')
            ->assertHasNoErrors('slot');

        expect($this->pg->fresh()->spell_slots_used)->toBe(['pact' => 1]);
    });

    it('e i normali restano una riserva a parte', function () {
        Livewire::actingAs($this->pg->user)
            ->test(SpellSlotTracker::class, ['character' => $this->pg])
            ->call('spend', '1')
            ->assertHasNoErrors('slot');

        expect($this->pg->fresh()->spell_slots_used)->toBe([1 => 1]);
    });
});

describe('chi ha una riserva sola', function () {
    it('il Warlock puro mostra il patto, e non lo raddoppia', function () {
        $warlock = conClassi(
            Character::factory()->create(['class' => 'Warlock', 'level' => 3, 'spell_ability' => 'cha']),
            ['Warlock' => 3],
        );

        $html = Livewire::actingAs($warlock->user)
            ->test(SpellSlotTracker::class, ['character' => $warlock])
            ->assertSee('Slot da patto')
            ->assertDontSee('Slot disponibili')
            ->html();

        expect(substr_count($html, 'liv. 2'))->toBe(1);
    });

    it('lo Stregone puro mostra i normali e nessun patto', function () {
        $stregone = conClassi(
            Character::factory()->create(['class' => 'Stregone', 'level' => 5, 'spell_ability' => 'cha']),
            ['Stregone' => 5],
        );

        Livewire::actingAs($stregone->user)
            ->test(SpellSlotTracker::class, ['character' => $stregone])
            ->assertSee('Slot disponibili')
            ->assertDontSee('Slot da patto');
    });
});
