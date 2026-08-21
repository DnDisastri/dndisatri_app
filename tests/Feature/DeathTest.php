<?php

declare(strict_types=1);

use App\Actions\Characters\KillCharacter;
use App\Actions\Market\CreateListing;
use App\Actions\Market\CreateTrade;
use App\Enums\ListingStatus;
use App\Enums\TradeStatus;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\User;

describe('dichiarare caduto un personaggio', function () {
    it('segna la data e lo toglie dalla Gilda', function () {
        $character = Character::factory()->create();

        app(KillCharacter::class)->handle($character, User::factory()->dm()->create());

        expect($character->fresh()->isAlive())->toBeFalse()
            ->and($character->fresh()->died_at)->not->toBeNull()
            ->and(Character::alive()->whereKey($character->getKey())->exists())->toBeFalse()
            ->and(Character::fallen()->whereKey($character->getKey())->exists())->toBeTrue();
    });

    it('non si muore due volte', function () {
        $character = Character::factory()->fallen()->create();

        expect(fn () => app(KillCharacter::class)->handle($character, User::factory()->dm()->create()))
            ->toThrow(RuntimeException::class, 'già fra i caduti');
    });
});

describe('il racconto della morte', function () {
    it('resta scritto insieme alla serata in cui è successo', function () {
        $character = Character::factory()->create();
        $session = GameSession::factory()->create();

        app(KillCharacter::class)->handle(
            $character,
            User::factory()->dm()->create(),
            story: 'Ha tenuto il ponte da solo mentre gli altri passavano.',
            session: $session,
        );

        $fallen = $character->fresh();

        expect($fallen->death_story)->toContain('Ha tenuto il ponte')
            ->and($fallen->diedInSession->is($session))->toBeTrue();
    });

    it('e si può morire senza una serata a cui appenderlo', function () {
        $character = Character::factory()->create();

        app(KillCharacter::class)->handle(
            $character,
            User::factory()->dm()->create(),
            story: 'Trovato senza vita nella sua stanza, il mattino dopo.',
        );

        $fallen = $character->fresh();

        expect($fallen->died_in_session_id)->toBeNull()
            ->and($fallen->death_story)->not->toBeNull();
    });
// La relazione con la serata è opzionale: cancellarla non deve rimuovere il racconto né riaprire il personaggio.
    it('cancellare la serata non cancella il caduto', function () {
        $character = Character::factory()->create();
        $session = GameSession::factory()->create();

        app(KillCharacter::class)->handle(
            $character, User::factory()->dm()->create(),
            story: 'Caduto nel crollo.', session: $session,
        );

        $session->delete();

        $fallen = $character->fresh();

        expect($fallen->isAlive())->toBeFalse()
            ->and($fallen->death_story)->toBe('Caduto nel crollo.')
            ->and($fallen->died_in_session_id)->toBeNull();
    });
});
// La morte chiude le operazioni di mercato ancora aperte senza riscrivere quelle già concluse.
describe('cosa lascia in sospeso', function () {
    it('i suoi annunci si ritirano e la roba gli torna', function () {
        $seller = Character::factory()->create();
        $seller->addToInventory('Spada Lunga', value: 15);

        $listing = app(CreateListing::class)->handle($seller, 'Spada Lunga', 1, 20);

        expect($seller->fresh()->ownsItem('Spada Lunga'))->toBeFalse();

        app(KillCharacter::class)->handle($seller->fresh(), User::factory()->dm()->create());

        expect($listing->fresh()->status)->toBe(ListingStatus::Cancelled)
            ->and($seller->fresh()->ownsItem('Spada Lunga'))->toBeTrue();
    });

    it('le proposte di scambio si chiudono, da tutte e due i lati', function () {
        $vittima = Character::factory()->create(['gp' => 100]);
        $altro = Character::factory()->create(['gp' => 100]);

        $mandata = app(CreateTrade::class)->handle(from: $vittima, to: $altro, giveGp: 10);
        $ricevuta = app(CreateTrade::class)->handle(from: $altro, to: $vittima, giveGp: 5);

        app(KillCharacter::class)->handle($vittima, User::factory()->dm()->create());

        expect($mandata->fresh()->status)->toBe(TradeStatus::Cancelled)
            ->and($ricevuta->fresh()->status)->toBe(TradeStatus::Cancelled);
    });

    it('gli scambi già conclusi restano come sono', function () {
        $vittima = Character::factory()->create(['gp' => 100]);
        $altro = Character::factory()->create(['gp' => 100]);

        $vecchio = app(CreateTrade::class)->handle(from: $vittima, to: $altro, giveGp: 10);
        app(App\Actions\Market\AcceptTrade::class)->handle($vecchio);

        app(KillCharacter::class)->handle($vittima->fresh(), User::factory()->dm()->create());

        expect($vecchio->fresh()->status)->toBe(TradeStatus::Accepted);
    });
});

describe('chi può', function () {
    it('un DM o un admin, su un personaggio vivo', function () {
        $character = Character::factory()->create();

        expect(User::factory()->dm()->create()->can('kill', $character))->toBeTrue()
            ->and(User::factory()->admin()->create()->can('kill', $character))->toBeTrue();
    });

    it('non il proprietario', function () {
        $character = Character::factory()->create();

        expect($character->user->can('kill', $character))->toBeFalse();
    });

    it('e su un caduto nemmeno un DM: la morte è definitiva', function () {
        $character = Character::factory()->fallen()->create();

        expect(User::factory()->dm()->create()->can('kill', $character))->toBeFalse();
    });
});
