<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;

// I permessi dei DM sono globali e non dipendono dalla campagna del personaggio.
describe('i DM agiscono su qualsiasi personaggio', function () {
    it('modifica, concede e uccide chiunque', function () {
        $dm = User::factory()->dm()->create();
        $character = Character::factory()->create();

        expect($dm->can('update', $character))->toBeTrue()
            ->and($dm->can('grant', $character))->toBeTrue()
            ->and($dm->can('kill', $character))->toBeTrue();
    });

    it('vale per tutti i DM allo stesso modo: nessuno ha "i suoi" giocatori', function () {
        $character = Character::factory()->create();

        $dms = User::factory()->dm()->count(3)->create();

        foreach ($dms as $dm) {
            expect($dm->can('update', $character))->toBeTrue()
                ->and($dm->can('grant', $character))->toBeTrue();
        }
    });

    it('non può uccidere due volte lo stesso personaggio', function () {
        $dm = User::factory()->dm()->create();
        $character = Character::factory()->create();

// La morte passa dall'azione di dominio e non da mass assignment.
        $character->died_at = now();
        $character->save();

        expect($dm->can('kill', $character->fresh()))->toBeFalse();
    });
});

describe('i giocatori non gestiscono nulla', function () {
    it('non modificano nemmeno il proprio personaggio: propongono', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create();

        expect($player->can('update', $character))->toBeFalse()
            ->and($player->can('propose', $character))->toBeTrue();
    });

    it('non propongono modifiche al personaggio di un altro', function () {
        $player = User::factory()->player()->create();
        $someoneElse = Character::factory()->create();

        expect($player->can('propose', $someoneElse))->toBeFalse();
    });

    it('non propongono più nulla per un personaggio morto', function () {
        $player = User::factory()->player()->create();
        $fallen = Character::factory()->ownedBy($player)->fallen()->create();

        expect($player->can('propose', $fallen))->toBeFalse();
    });

    it('non concedono oro e non uccidono', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->ownedBy($player)->create();

        expect($player->can('grant', $character))->toBeFalse()
            ->and($player->can('kill', $character))->toBeFalse()
            ->and($player->can('delete', $character))->toBeFalse();
    });
});

describe('un solo personaggio vivo alla volta', function () {
    it('impedisce al giocatore di crearne un secondo', function () {
        $player = User::factory()->player()->create();

        expect($player->can('create', Character::class))->toBeTrue();

        Character::factory()->ownedBy($player)->create();

        expect($player->fresh()->can('create', Character::class))->toBeFalse();
    });

    it('si sblocca quando il personaggio muore', function () {
        $player = User::factory()->player()->create();
        Character::factory()->ownedBy($player)->fallen()->create();

        expect($player->can('create', Character::class))->toBeTrue();
    });

    it('non vale per i DM, che ne hanno bisogno di più d\'uno', function () {
        $dm = User::factory()->dm()->create();
        Character::factory()->ownedBy($dm)->create();

        expect($dm->can('create', Character::class))->toBeTrue();
    });
});

describe('gli admin sono account di sola amministrazione', function () {
    it('NON creano personaggi: non giocano', function () {
        expect(User::factory()->admin()->create()->can('create', Character::class))->toBeFalse();
    });

    it('ma gestiscono quelli degli altri, e sono gli unici a poterli cancellare', function () {
        $admin = User::factory()->admin()->create();
        $dm = User::factory()->dm()->create();
        $character = Character::factory()->create();

        expect($admin->can('update', $character))->toBeTrue()
            ->and($admin->can('grant', $character))->toBeTrue()
            ->and($admin->can('kill', $character))->toBeTrue()
            ->and($admin->can('delete', $character))->toBeTrue()
            ->and($dm->can('delete', $character))->toBeFalse();
    });

    it('non compaiono davanti ai giocatori', function () {
        $admin = User::factory()->admin()->create();
        $dm = User::factory()->dm()->create();
        $player = User::factory()->player()->create();

        $visible = User::visibleToPlayers()->pluck('id')->all();

        expect($visible)->toContain($dm->id)
            ->and($visible)->toContain($player->id)
            ->and($visible)->not->toContain($admin->id);
    });
});

describe('la Gilda è visibile a tutti', function () {
    it('anche i personaggi degli altri', function () {
        $player = User::factory()->player()->create();
        $character = Character::factory()->create();

        expect($player->can('view', $character))->toBeTrue()
            ->and($player->can('viewAny', Character::class))->toBeTrue();
    });
});
