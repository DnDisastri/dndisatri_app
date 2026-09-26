<?php

declare(strict_types=1);

use App\Domain\Dnd\AdventurerRank;
use App\Enums\QuestDifficulty;
use App\Models\User;
use Illuminate\Support\Facades\Blade;


it('mappa ogni difficoltà ai due gradi giusti', function (QuestDifficulty $d, AdventurerRank $da, AdventurerRank $a) {
    expect($d->ranks())->toBe([$da, $a]);
})->with([
    'facile' => [QuestDifficulty::Facile, AdventurerRank::Novizio, AdventurerRank::Apprendista],
    'media' => [QuestDifficulty::Media, AdventurerRank::Apprendista, AdventurerRank::Professionista],
    'difficile' => [QuestDifficulty::Difficile, AdventurerRank::Professionista, AdventurerRank::Maestro],
    'epica' => [QuestDifficulty::Epica, AdventurerRank::Maestro, AdventurerRank::Leggendario],
]);

it('dà a ogni difficoltà una fascia di livelli e una descrizione', function () {
    expect(QuestDifficulty::Facile->suggestedLevels())->toBe('1–4')
        ->and(QuestDifficulty::Epica->suggestedLevels())->toBe('9+');

    foreach (QuestDifficulty::cases() as $d) {
        expect($d->description())->not->toBeEmpty();
    }
});

it('la legenda elenca tutte e quattro le difficoltà coi gradi', function () {
    $html = Blade::render('<x-legenda-difficolta />');

    expect($html)
        ->toContain('Facile')
        ->toContain('Media')
        ->toContain('Difficile')
        ->toContain('Epica')
        ->toContain('Professionista')  
        ->toContain('liv. 5–12');     
});

it('la legenda compare sulla lista delle quest', function () {
    $this->actingAs(User::factory()->player()->create())
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee('Cosa vuol dire la difficoltà?')
        ->assertSee('liv. 5–12');
});
