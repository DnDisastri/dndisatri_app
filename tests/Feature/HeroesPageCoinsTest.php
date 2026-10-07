<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;

// Nella lista degli eroi la borsa è riassunta: le monete complete stanno nello zaino.
it('mostra platino e oro, non argento e rame', function () {
    $pg = Character::factory()->for(User::factory()->player())->create(['pp' => 12, 'gp' => 1234, 'sp' => 56, 'cp' => 789]);

    $this->actingAs($pg->user)->get(route('characters.index'))
        ->assertOk()
        ->assertSeeText('12 mp')
        ->assertSeeText('1.234 mo')
        ->assertDontSeeText('56 ma')
        ->assertDontSeeText('789 mr');
});

it('senza platino né oro mostra le monete che ci sono', function () {
    $pg = Character::factory()->for(User::factory()->player())->create(['pp' => 0, 'gp' => 0, 'sp' => 7, 'cp' => 3]);

    $this->actingAs($pg->user)->get(route('characters.index'))
        ->assertOk()
        ->assertSeeText('7 ma 3 mr')
        ->assertDontSeeText('0 mo');
});
