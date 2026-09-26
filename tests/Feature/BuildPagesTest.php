<?php

use App\Models\Build;
use App\Models\Character;
use App\Models\User;

describe('la vetrina delle build', function () {
    it('mostra le pubblicate e nasconde le bozze', function () {
        Build::factory()->complete()->create(['title' => 'Il Muro', 'slug' => 'il-muro']);
        Build::factory()->complete()->draft()->create(['title' => 'Bozza Segreta', 'slug' => 'bozza-segreta']);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('builds.index'))
            ->assertOk()
            ->assertSee('Il Muro')
            ->assertDontSee('Bozza Segreta');
    });

    it('la sfoglia anche chi ha già un personaggio', function () {
        $player = User::factory()->player()->create();
        Character::factory()->ownedBy($player)->create();
        Build::factory()->complete()->create(['title' => 'Il Muro', 'slug' => 'il-muro']);

        $this->actingAs($player)
            ->get(route('builds.index'))
            ->assertOk()
            ->assertSee('Il Muro');
    });
});

describe('il dettaglio', function () {
    it('mostra la build pubblicata', function () {
        $build = Build::factory()->complete()->create(['title' => 'Il Muro', 'slug' => 'il-muro']);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('builds.show', $build))
            ->assertOk()
            ->assertSee('Il Muro')
            ->assertSee('Usa questa build');
    });

    it('dà 404 su una bozza', function () {
        $build = Build::factory()->complete()->draft()->create(['slug' => 'bozza']);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('builds.show', $build))
            ->assertNotFound();
    });
});

describe('il pulsante «usa questa build»', function () {
    it('è attivo per chi non ha personaggi', function () {
        $build = Build::factory()->complete()->create(['slug' => 'il-muro']);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('builds.show', $build))
            ->assertSee(route('characters.create', ['build' => $build->slug]));
    });

    it('è spento per chi ha già un personaggio: uno alla volta', function () {
        $player = User::factory()->player()->create();
        Character::factory()->ownedBy($player)->create();
        $build = Build::factory()->complete()->create(['slug' => 'il-muro']);

        $this->actingAs($player)
            ->get(route('builds.show', $build))
            ->assertDontSee(route('characters.create'))
            ->assertSee('un personaggio alla volta');
    });
});
