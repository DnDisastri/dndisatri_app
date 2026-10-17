<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\ProposeChange;
use App\Enums\PendingChangeType;
use App\Models\Character;
use App\Models\User;

beforeEach(function () {
    $this->mago = Character::factory()->create(['class' => 'Mago', 'level' => 5, 'gp' => 200, 'sp' => 0, 'cp' => 0, 'pp' => 0]);
    $this->pergamena = $this->mago->addToInventory(name: 'Pergamena di Palla di Fuoco', category: 'Oggetti Magici');
});

describe('la richiesta', function () {
    it('costa 50 mo per livello e indica la pergamena', function () {
        $change = app(ProposeChange::class)->scrollSpell($this->mago, $this->mago->user, 'Palla di Fuoco', $this->pergamena->id);

        expect($change->type)->toBe(PendingChangeType::ScrollSpell)
            ->and($change->diff['scroll']['cost_cp'])->toBe(15000)
            ->and($change->summary)->toContain('Palla di Fuoco')
            ->and($change->summary)->toContain('Pergamena di Palla di Fuoco');
    });

    it('solo un Mago', function () {
        $chierico = Character::factory()->create(['class' => 'Chierico', 'level' => 5]);

        expect(fn () => app(ProposeChange::class)->scrollSpell($chierico, $chierico->user, 'Palla di Fuoco', null))
            ->toThrow(InvalidArgumentException::class, 'Solo un Mago');
    });

    it('non oltre il livello degli slot', function () {
        expect(fn () => app(ProposeChange::class)->scrollSpell($this->mago, $this->mago->user, 'Cono di Freddo', $this->pergamena->id))
            ->toThrow(InvalidArgumentException::class, 'non hai ancora slot');
    });

    it('servono le monete', function () {
        $this->mago->update(['gp' => 100]);

        expect(fn () => app(ProposeChange::class)->scrollSpell($this->mago->fresh(), $this->mago->user, 'Palla di Fuoco', $this->pergamena->id))
            ->toThrow(InvalidArgumentException::class, 'non bastano le monete');
    });

    it('e la pergamena nello zaino', function () {
        expect(fn () => app(ProposeChange::class)->scrollSpell($this->mago, $this->mago->user, 'Palla di Fuoco', null))
            ->toThrow(InvalidArgumentException::class, 'Scegli la pergamena');
    });
});

describe("l'approvazione", function () {
    it('paga, consuma la pergamena e scrive nel libro', function () {
        $change = app(ProposeChange::class)->scrollSpell($this->mago, $this->mago->user, 'Palla di Fuoco', $this->pergamena->id);

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $mago = $this->mago->fresh();

        expect($mago->purseValue())->toBe(5000)
            ->and($mago->items()->where('name', 'Pergamena di Palla di Fuoco')->exists())->toBeFalse()
            ->and($mago->spells()->where('name', 'Palla di Fuoco')->first())
            ->level->toBe(3)
            ->prepared->toBeFalse();
    });

    it('si ferma se nel frattempo la pergamena non c\'è più', function () {
        $change = app(ProposeChange::class)->scrollSpell($this->mago, $this->mago->user, 'Palla di Fuoco', $this->pergamena->id);
        $this->pergamena->delete();

        expect(fn () => app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create()))
            ->toThrow(RuntimeException::class, 'non ha più');
    });
});

describe('la pagina', function () {
    it('il Mago la trova nel menu e ci sceglie incantesimo e pergamena', function () {
        $this->actingAs($this->mago->user)
            ->get(route('characters.show', $this->mago))
            ->assertSee('Copia da una pergamena');

        $this->actingAs($this->mago->user)
            ->get(route('proposals.scroll', $this->mago))
            ->assertOk()
            ->assertSee('Palla di Fuoco')
            ->assertSee('Pergamena di Palla di Fuoco');
    });

    it('agli altri non compare', function () {
        $guerriero = Character::factory()->create(['class' => 'Guerriero']);

        $this->actingAs($guerriero->user)
            ->get(route('characters.show', $guerriero))
            ->assertDontSee('Copia da una pergamena');

        $this->actingAs($guerriero->user)
            ->get(route('proposals.scroll', $guerriero))
            ->assertNotFound();
    });

    it('il modulo crea la richiesta', function () {
        $this->actingAs($this->mago->user)
            ->post(route('proposals.scroll', $this->mago), ['spell' => 'Palla di Fuoco', 'item_id' => $this->pergamena->id])
            ->assertRedirect(route('characters.show', $this->mago));

        expect($this->mago->pendingChanges()->where('type', PendingChangeType::ScrollSpell)->exists())->toBeTrue();
    });
});
