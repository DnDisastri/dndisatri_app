<?php

declare(strict_types=1);

use App\Actions\Characters\SpendSpellSlot;
use App\Actions\Characters\TakeRest;
use App\Enums\RestType;
use App\Livewire\HitPointTracker;
use App\Livewire\SpellSlotTracker;
use App\Models\Character;
use App\Models\User;
use Livewire\Livewire;

describe('consumare gli slot', function () {
    it('segna uno slot come usato', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 5]);

        app(SpendSpellSlot::class)->spend($character, 1);

        expect($character->fresh()->spell_slots_used)->toBe([1 => 1]);
    });

    it('si ferma quando sono finiti', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 5]);

        app(SpendSpellSlot::class)->spend($character, 3);
        app(SpendSpellSlot::class)->spend($character->fresh(), 3);

        expect(fn () => app(SpendSpellSlot::class)->spend($character->fresh(), 3))
            ->toThrow(RuntimeException::class);
    });

    it('non lascia usare slot di un livello che non si ha', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 1]);

        expect(fn () => app(SpendSpellSlot::class)->spend($character, 9))
            ->toThrow(RuntimeException::class);
    });

    it('rimette a posto uno slot segnato per sbaglio', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 5]);

        app(SpendSpellSlot::class)->spend($character, 1);
        app(SpendSpellSlot::class)->spend($character->fresh(), 1);
        app(SpendSpellSlot::class)->recover($character->fresh(), 1);

        expect($character->fresh()->spell_slots_used)->toBe([1 => 1]);

        app(SpendSpellSlot::class)->recover($character->fresh(), 1);

        expect($character->fresh()->spell_slots_used)->toBe([]);
    });
});

describe('il riposo lungo', function () {
    it('azzera tutti gli slot', function () {
        $character = Character::factory()->create([
            'class' => 'Mago', 'level' => 5,
            'spell_slots_used' => [1 => 4, 2 => 2, 3 => 1],
        ]);

        app(TakeRest::class)->handle($character, RestType::Long);

        expect($character->fresh()->spell_slots_used)->toBe([]);
    });
});

describe('il riposo breve', function () {
    it('recupera solo gli slot da patto', function () {
        $character = Character::factory()->create([
            'class' => 'Warlock', 'level' => 5,
            'spell_slots_used' => ['pact' => 2],
        ]);

        app(TakeRest::class)->handle($character, RestType::Short);

        expect($character->fresh()->spell_slots_used)->toBe([]);
    });

    it('lascia intatti quelli normali', function () {
        $character = Character::factory()->create([
            'class' => 'Mago', 'level' => 5,
            'spell_slots_used' => [1 => 3, 2 => 1],
        ]);

        app(TakeRest::class)->handle($character, RestType::Short);

        expect($character->fresh()->spell_slots_used)->toBe([1 => 3, 2 => 1]);
    });

    it('su un personaggio con entrambi, tocca solo il patto', function () {
        $character = Character::factory()->create([
            'class' => 'Warlock', 'level' => 5,
            'spell_slots_used' => ['pact' => 2, 1 => 1],
        ]);

        app(TakeRest::class)->handle($character, RestType::Short);

        expect($character->fresh()->spell_slots_used)->toBe([1 => 1]);
    });
});

describe('dalla scheda', function () {
    it('il proprietario consuma, recupera e riposa', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create(['class' => 'Mago', 'level' => 5]);

        $this->actingAs($player);

        Livewire::test(SpellSlotTracker::class, ['character' => $character])
            ->call('spend', 1)
            ->call('spend', 1);

        expect($character->fresh()->spell_slots_used)->toBe([1 => 2]);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->call('rest', 'long');

        expect($character->fresh()->spell_slots_used)->toBe([]);
    });

    it('un altro giocatore no', function () {
        $character = Character::factory()->create(['class' => 'Mago', 'level' => 5]);

        $this->actingAs(User::factory()->player()->create());

        Livewire::test(SpellSlotTracker::class, ['character' => $character])
            ->call('spend', 1)
            ->assertForbidden();

        expect($character->fresh()->spell_slots_used)->toBe([]);
    });

    it('un DM sì, per rimettere a posto quello che serve', function () {
        $character = Character::factory()->create([
            'class' => 'Mago', 'level' => 5, 'spell_slots_used' => [1 => 2],
        ]);

        $this->actingAs(User::factory()->dm()->create());

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->call('rest', 'long');

        expect($character->fresh()->spell_slots_used)->toBe([]);
    });

    it('avvisa invece di esplodere quando gli slot sono finiti', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'class' => 'Mago', 'level' => 1, 'spell_slots_used' => [1 => 2],
        ]);

        $this->actingAs($player);

        Livewire::test(SpellSlotTracker::class, ['character' => $character])
            ->call('spend', 1)
            ->assertHasErrors('slot');
    });
});


describe('i dadi vita', function () {
    it('si spendono, e curano il tirato più la Costituzione', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 5, 'hit_die' => 10, 'con' => 16, 'hp_max' => 44, 'hp_current' => 10,
        ]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->set('dadoVita', 7)
            ->call('spendHitDie')
            ->assertHasNoErrors();

        expect($character->fresh()->hp_current)->toBe(20)
            ->and($character->fresh()->hit_dice_used)->toBe(1);
    });

    it('ma non se ne hanno più', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 2, 'hit_die' => 8, 'hit_dice_used' => 2, 'hp_max' => 20, 'hp_current' => 5,
        ]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->set('dadoVita', 5)
            ->call('spendHitDie')
            ->assertHasErrors('pf');

        expect($character->fresh()->hp_current)->toBe(5);
    });

// Il valore del dado vita arriva dall'utente e deve restare entro la faccia del dado del personaggio.
    it('e un tiro impossibile viene rifiutato', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 3, 'hit_die' => 8, 'hp_max' => 24, 'hp_current' => 4,
        ]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->set('dadoVita', 30)
            ->call('spendHitDie')
            ->assertHasErrors('pf');

        expect($character->fresh()->hit_dice_used)->toBe(0);
    });


    it('il pulsante chiede il tiro, non spende da solo', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 5, 'hit_die' => 6, 'hp_max' => 30, 'hp_current' => 12,
        ]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->set('amount', 1)
            ->call('chiediDadoVita')
            ->assertSet('modaleDado', true)
            ->assertSee('Quanto hai fatto col d6?');

        expect($character->fresh()->hit_dice_used)->toBe(0)
            ->and($character->fresh()->hp_current)->toBe(12);
    });

// Spendere un dado vita per un privilegio consuma la riserva senza curare i punti ferita.
    it('e si può spendere senza curare, per un privilegio di classe', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 4, 'hit_die' => 8, 'con' => 16, 'hp_max' => 30, 'hp_current' => 11,
        ]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->call('chiediDadoVita')
            ->call('spendiDadoSenzaCura')
            ->assertHasNoErrors()
            ->assertSet('modaleDado', false)
            ->assertSee('senza cura');

        expect($character->fresh()->hit_dice_used)->toBe(1)
            ->and($character->fresh()->hp_current)->toBe(11);
    });

    it('e il riposo lungo ne restituisce metà', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 6, 'hit_die' => 8, 'hit_dice_used' => 6, 'hp_max' => 40, 'hp_current' => 3,
        ]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->call('rest', 'long');

        expect($character->fresh()->hit_dice_used)->toBe(3)
            ->and($character->fresh()->hp_current)->toBe(40);
    });
});
// La richiesta di conferma deve descrivere gli effetti del riposo senza modificare lo stato prima della conferma.
describe('la conferma del riposo', function () {
    it('non riposa: elenca quello che tornerebbe', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'class' => 'Mago', 'level' => 4, 'hit_die' => 6,
            'hp_max' => 22, 'hp_current' => 9, 'hit_dice_used' => 3,
            'spell_slots_used' => [1 => 2],
        ]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->call('chiediRiposo', 'long')
            ->assertSet('conferma', 'long')
            ->assertSee('Punti ferita al massimo')
            ->assertSee('2 spesi')
            ->assertSee('Tornano 2 dadi vita');

        expect($character->fresh()->hp_current)->toBe(9)
            ->and($character->fresh()->spell_slots_used)->toBe([1 => 2]);
    });

    it('e confermando riposa e si chiude', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'level' => 4, 'hp_max' => 22, 'hp_current' => 9,
        ]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->call('chiediRiposo', 'long')
            ->call('rest', 'long')
            ->assertSet('conferma', null);

        expect($character->fresh()->hp_current)->toBe(22);
    });


    it('e il breve avverte che i punti ferita non tornano da soli', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create([
            'class' => 'Guerriero', 'level' => 3, 'hp_max' => 28, 'hp_current' => 5,
        ]);

        $this->actingAs($player);

        Livewire::test(HitPointTracker::class, ['character' => $character])
            ->call('chiediRiposo', 'short')
            ->assertSee('non tornano da soli')
            ->assertSee('spendi un dado vita');
    });
});
