<?php

declare(strict_types=1);

use App\Domain\Dnd\AdventurerRank;
use App\Models\Character;
use Illuminate\Support\Facades\Blade;


it('assegna il grado giusto a ogni livello', function (int $livello, AdventurerRank $atteso) {
    expect(AdventurerRank::fromLevel($livello))->toBe($atteso);
})->with([
    'novizio 1' => [1, AdventurerRank::Novizio],
    'novizio 2' => [2, AdventurerRank::Novizio],
    'apprendista 3' => [3, AdventurerRank::Apprendista],
    'apprendista 4' => [4, AdventurerRank::Apprendista],
    'professionista 5' => [5, AdventurerRank::Professionista],
    'professionista 8' => [8, AdventurerRank::Professionista],
    'maestro 9' => [9, AdventurerRank::Maestro],
    'maestro 12' => [12, AdventurerRank::Maestro],
    'leggendario 13' => [13, AdventurerRank::Leggendario],
    'leggendario 20' => [20, AdventurerRank::Leggendario],
]);

it('le fasce non si sovrappongono: ogni livello 1-20 cade in un grado solo', function () {
    foreach (range(1, 20) as $livello) {
        expect(AdventurerRank::fromLevel($livello))->toBeInstanceOf(AdventurerRank::class);
    }

    expect(AdventurerRank::fromLevel(2))->not->toBe(AdventurerRank::fromLevel(3));
    expect(AdventurerRank::fromLevel(4))->not->toBe(AdventurerRank::fromLevel(5));
    expect(AdventurerRank::fromLevel(8))->not->toBe(AdventurerRank::fromLevel(9));
    expect(AdventurerRank::fromLevel(12))->not->toBe(AdventurerRank::fromLevel(13));
});

it('dà a ogni grado un metallo e un colore distinti', function () {
    $metalli = collect(AdventurerRank::cases())->map->metal();
    $colori = collect(AdventurerRank::cases())->map->color();

    expect($metalli->unique())->toHaveCount(5);
    expect($colori->unique())->toHaveCount(5);
    expect($metalli->all())->toBe(['legno', 'bronzo', 'argento', 'oro', 'platino']);
});

it('il personaggio deduce il grado dal suo livello', function () {
    $pg = Character::factory()->make(['level' => 10]);

    expect($pg->rank())->toBe(AdventurerRank::Maestro);
});

it('il componente mostra il medaglione col nome e il colore del metallo', function () {
    $html = Blade::render('<x-grado :level="9" />');

    expect($html)
        ->toContain('Maestro')   
        ->toContain('#d4af37')   
        ->toContain('<svg');    
});

it('può nascondere il nome e mostrare solo il medaglione', function () {
    $html = Blade::render('<x-grado :level="1" :conNome="false" />');

    expect($html)
        ->toContain('#9c6b3f')        
        ->not->toContain('>Novizio<'); 
});
