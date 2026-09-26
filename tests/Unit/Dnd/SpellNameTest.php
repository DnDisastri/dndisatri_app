<?php

declare(strict_types=1);

use App\Domain\Dnd\SpellName;

describe('normalizzazione dei nomi', function () {
    it('rende uguali le varianti di scrittura', function (string $written) {
        expect(SpellName::normalize($written))->toBe('palladifuoco');
    })->with([
        'Palla di Fuoco',
        'palla di fuoco',
        'PALLA DI FUOCO',
        'Palla  di   Fuoco',
        'Palla-di-Fuoco',
    ]);

    it('toglie gli accenti', function () {
        expect(SpellName::normalize('Individuazione del Magico'))->toBe('individuazionedelmagico')
            ->and(SpellName::normalize('Velocità'))->toBe('velocita');
    });

    it('regge nomi vuoti o assenti', function () {
        expect(SpellName::normalize(''))->toBe('')
            ->and(SpellName::normalize(null))->toBe('');
    });

    it('tiene distinti incantesimi diversi', function () {
        expect(SpellName::normalize('Cura Ferite'))
            ->not->toBe(SpellName::normalize('Parola Guaritrice'));
    });
});

describe('libreria degli incantesimi', function () {
    it('trova la descrizione anche con una scrittura approssimativa', function () {
        expect(SpellName::description('palla di fuoco'))->toContain('8d6');
    });

    it('restituisce null per un incantesimo che non conosce', function () {
        expect(SpellName::description('Evoca Pizza'))->toBeNull();
    });

    it('copre tutti gli incantesimi a catalogo', function () {
        foreach (array_keys(config('dnd.spells.library')) as $spell) {
            expect(SpellName::description($spell))->not->toBeNull();
        }
    });
});
