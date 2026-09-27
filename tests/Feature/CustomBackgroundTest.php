<?php

declare(strict_types=1);

use App\Actions\Characters\CreateCharacter;
use App\Livewire\CharacterWizard;
use App\Models\User;
use Livewire\Livewire;

// I tredici background del manuale danno due abilità fisse. «Personalizzato»
// lascia scegliere quali, per chi non si riconosce in nessuno di loro.
$punteggi = ['str' => 8, 'dex' => 15, 'con' => 14, 'int' => 13, 'wis' => 12, 'cha' => 10];

describe('le abilità a scelta', function () use ($punteggi) {
    it('diventano competenze come quelle fisse degli altri', function () use ($punteggi) {
        $eroe = app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(),
            name: 'Nadia',
            class: 'Guerriero',
            species: 'Tiefling',
            background: 'Personalizzato',
            boughtScores: $punteggi,
            skills: ['athletics', 'intimidation'],
            backgroundSkills: ['arcana', 'medicine'],
        );

        expect($eroe->skills)->toMatchArray([
            'athletics' => 'proficient',
            'intimidation' => 'proficient',
            'arcana' => 'proficient',
            'medicine' => 'proficient',
        ]);
    });

    it('sono obbligatorie, e nel numero giusto', function () use ($punteggi) {
        $crea = fn (array $abilita) => app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(),
            name: 'Nadia',
            class: 'Guerriero',
            species: 'Tiefling',
            background: 'Personalizzato',
            boughtScores: $punteggi,
            skills: ['athletics', 'intimidation'],
            backgroundSkills: $abilita,
        );

        expect(fn () => $crea([]))->toThrow(InvalidArgumentException::class, '2 abilità');
        expect(fn () => $crea(['arcana']))->toThrow(InvalidArgumentException::class);
        expect(fn () => $crea(['arcana', 'medicine', 'nature']))->toThrow(InvalidArgumentException::class);
    });

    it('e devono esistere davvero', function () use ($punteggi) {
        expect(fn () => app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(),
            name: 'Nadia',
            class: 'Guerriero',
            species: 'Tiefling',
            background: 'Personalizzato',
            boughtScores: $punteggi,
            skills: ['athletics', 'intimidation'],
            backgroundSkills: ['arcana', 'cucinare'],
        ))->toThrow(InvalidArgumentException::class, 'Abilità sconosciuta');
    });

    it('gli altri background non ne chiedono', function () use ($punteggi) {
        $eroe = app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(),
            name: 'Ismaele',
            class: 'Guerriero',
            species: 'Tiefling',
            background: 'Accolito',
            boughtScores: $punteggi,
            skills: ['athletics', 'intimidation'],
        );

        expect($eroe->skills)->toHaveKeys(['insight', 'religion']);
    });
});

describe('lo zaino', function () use ($punteggi) {
    it('finisce nell\'inventario', function () use ($punteggi) {
        $eroe = app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(),
            name: 'Nadia',
            class: 'Guerriero',
            species: 'Tiefling',
            background: 'Personalizzato',
            boughtScores: $punteggi,
            skills: ['athletics', 'intimidation'],
            backgroundSkills: ['arcana', 'medicine'],
            pack: 2,
        );

        expect($eroe->items()->pluck('name'))->toContain('Zaino da Studioso');
    });

    it('non scegliendolo si prende il primo', function () use ($punteggi) {
        $eroe = app(CreateCharacter::class)->handle(
            owner: User::factory()->player()->create(),
            name: 'Nadia',
            class: 'Guerriero',
            species: 'Tiefling',
            background: 'Personalizzato',
            boughtScores: $punteggi,
            skills: ['athletics', 'intimidation'],
            backgroundSkills: ['arcana', 'medicine'],
        );

        expect($eroe->items()->pluck('name'))->toContain('Zaino da Esploratore');
    });

    it('gli altri background non ne hanno uno a scelta', function () {
        expect(CreateCharacter::pack('Accolito', 2))->toBeNull();
    });
});

describe('nel wizard', function () {
    it('non si va avanti senza aver scelto le abilità', function () {
        $componente = Livewire::actingAs(User::factory()->player()->create())
            ->test(CharacterWizard::class)
            ->set('step', 4)
            ->call('selectBackground', 'Personalizzato');

        expect($componente->instance()->canAdvance())->toBeFalse();

        $componente->set('backgroundSkills', ['arcana', 'medicine']);

        expect($componente->instance()->canAdvance())->toBeTrue();
    });

    it('e nemmeno scegliendo due volte la stessa', function () {
        $componente = Livewire::actingAs(User::factory()->player()->create())
            ->test(CharacterWizard::class)
            ->set('step', 4)
            ->call('selectBackground', 'Personalizzato')
            ->set('backgroundSkills', ['arcana', 'arcana']);

        expect($componente->instance()->canAdvance())->toBeFalse();
    });

    it('un background normale si supera scegliendolo e basta', function () {
        $componente = Livewire::actingAs(User::factory()->player()->create())
            ->test(CharacterWizard::class)
            ->set('step', 4)
            ->call('selectBackground', 'Accolito');

        expect($componente->instance()->canAdvance())->toBeTrue();
    });

    // Appartengono al background di prima.
    it('cambiando background le scelte si azzerano', function () {
        $componente = Livewire::actingAs(User::factory()->player()->create())
            ->test(CharacterWizard::class)
            ->call('selectBackground', 'Personalizzato')
            ->set('backgroundSkills', ['arcana', 'medicine'])
            ->set('pack', 3)
            ->call('selectBackground', 'Accolito');

        expect($componente->get('backgroundSkills'))->toBe([])
            ->and($componente->get('pack'))->toBeNull();
    });

    // Sceglierle di nuovo al passo della classe sprecherebbe una scelta.
    it('quelle prese dal background risultano già ottenute', function () {
        $componente = Livewire::actingAs(User::factory()->player()->create())
            ->test(CharacterWizard::class)
            ->set('class', 'Ladro')
            ->call('selectBackground', 'Personalizzato')
            ->set('backgroundSkills', ['athletics', 'perception']);

        $opzioni = $componente->instance()->skillOptions();

        expect($opzioni['athletics']['fromBackground'])->toBeTrue()
            ->and($opzioni['perception']['fromBackground'])->toBeTrue()
            ->and($opzioni['stealth']['fromBackground'])->toBeFalse();
    });
});
