<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\ProposeChange;
use App\Actions\Characters\RejectPendingChange;
use App\Actions\Market\AcceptTrade;
use App\Actions\Market\BuyListing;
use App\Actions\Market\CreateListing;
use App\Actions\Market\CreateTrade;
use App\Actions\Market\ResolveTrade;
use App\Actions\Users\ReviewDmRequest;
use App\Enums\PendingChangeStatus;
use App\Enums\TradeStatus;
use App\Models\Character;
use App\Models\DmRequest;
use App\Models\User;
use App\Notifications\DmRequestDecided;
use App\Notifications\ListingSold;
use App\Notifications\RequestDecided;
use App\Notifications\TradeProposed;
use App\Notifications\TradeResolved;
use Illuminate\Support\Facades\Notification;


describe('le richieste', function () {
    it('avvisano il proponente quando sono approvate', function () {
        Notification::fake();

        $character = Character::factory()->create();
        $change = app(ProposeChange::class)->edit(
            $character, $character->user, ['name' => 'Nome Nuovo'],
        );

        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        Notification::assertSentTo($character->user, RequestDecided::class);
    });

    it('e anche quando sono rifiutate', function () {
        Notification::fake();

        $character = Character::factory()->create();
        $change = app(ProposeChange::class)->edit(
            $character, $character->user, ['name' => 'Nome Nuovo'],
        );

        app(RejectPendingChange::class)->handle($change, User::factory()->dm()->create(), 'No.');

        Notification::assertSentTo($character->user, RequestDecided::class);
    });
// Le notifiche delle decisioni non espongono l'identità del revisore al giocatore.
    it('ma non dicono chi ha deciso', function () {
        $character = Character::factory()->create();
        $dm = User::factory()->dm()->create(['name' => 'Il Nome Del DM']);

        $change = app(ProposeChange::class)->edit(
            $character, $character->user, ['name' => 'Nome Nuovo'],
        );

        app(ApprovePendingChange::class)->handle($change, $dm, 'Va bene.');

        $payload = $character->user->notifications()->first()->data;

        // È la regola: gli admin non compaiono davanti ai giocatori, e
        // nominare solo i DM darebbe un elenco a metà.
        expect(json_encode($payload))->not->toContain('Il Nome Del DM')
            ->and($payload['title'])->toContain('approvata');
    });
});

describe('gli scambi', function () {
    beforeEach(function () {
        $this->anna = Character::factory()->create(['name' => 'Anna', 'gp' => 100]);
        $this->bruno = Character::factory()->create(['name' => 'Bruno', 'gp' => 100]);
    });

    it('avvisano chi riceve la proposta', function () {
        Notification::fake();

        app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 10);

        Notification::assertSentTo($this->bruno->user, TradeProposed::class);
        Notification::assertNotSentTo($this->anna->user, TradeProposed::class);
    });

    it('avvisano chi ha proposto quando viene accettata', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 10);

        Notification::fake();

        app(AcceptTrade::class)->handle($trade);

        // Bruno sa già di aver accettato: l'avviso va ad Anna.
        Notification::assertSentTo($this->anna->user, TradeResolved::class);
        Notification::assertNotSentTo($this->bruno->user, TradeResolved::class);
    });

    it('su un rifiuto avvisano chi aveva proposto', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 10);

        Notification::fake();

        app(ResolveTrade::class)->handle($trade, TradeStatus::Rejected);

        Notification::assertSentTo($this->anna->user, TradeResolved::class);
    });

    it('su un ritiro avvisano il destinatario', function () {
        $trade = app(CreateTrade::class)->handle(from: $this->anna, to: $this->bruno, giveGp: 10);

        Notification::fake();

        app(ResolveTrade::class)->handle($trade, TradeStatus::Cancelled);

        Notification::assertSentTo($this->bruno->user, TradeResolved::class);
    });
});

describe('il mercato', function () {
    it('avvisa il venditore quando qualcuno compra', function () {
        $seller = Character::factory()->create();
        $seller->addToInventory('Spada Lunga', value: 15);
        $buyer = Character::factory()->create(['gp' => 100]);

        $listing = app(CreateListing::class)->handle($seller, 'Spada Lunga', 1, 20);

        Notification::fake();

        app(BuyListing::class)->handle($listing, $buyer);

        Notification::assertSentTo($seller->user, ListingSold::class);
    });
});

describe('le richieste di diventare DM', function () {
    it('avvisano il richiedente in entrambi i casi', function () {
        Notification::fake();

        $player = User::factory()->player()->create();
        $request = DmRequest::create(['user_id' => $player->getKey(), 'message' => 'Vorrei provare.']);

        app(ReviewDmRequest::class)->handle(
            $request, User::factory()->admin()->create(), PendingChangeStatus::Approved,
        );

        Notification::assertSentTo($player, DmRequestDecided::class);
    });

    it('e l\'approvazione racconta cosa si può fare adesso', function () {
        $player = User::factory()->player()->create();
        $request = DmRequest::create(['user_id' => $player->getKey(), 'message' => 'Vorrei provare.']);

        app(ReviewDmRequest::class)->handle(
            $request, User::factory()->admin()->create(), PendingChangeStatus::Approved,
        );

        expect($player->notifications()->first()->data['title'])->toBe('Sei un dungeon master');
    });
});

describe('la pagina degli notifiche', function () {
    it('mostra quelli ricevuti', function () {
        $character = Character::factory()->create();
        $change = app(ProposeChange::class)->edit(
            $character, $character->user, ['name' => 'Nome Nuovo'],
        );
        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $this->actingAs($character->user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('approvata', false);
    });

    it('aprirla li segna come letti', function () {
        $character = Character::factory()->create();
        $change = app(ProposeChange::class)->edit(
            $character, $character->user, ['name' => 'Nome Nuovo'],
        );
        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        expect($character->user->unreadNotifications()->count())->toBe(1);

        $this->actingAs($character->user)->get(route('notifications.index'));

        expect($character->user->fresh()->unreadNotifications()->count())->toBe(0);
    });

    it('senza notifiche lo dice, invece di mostrare il vuoto', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Nessuna notifica');
    });

    it('ognuno vede solo i propri', function () {
        $character = Character::factory()->create();
        $change = app(ProposeChange::class)->edit(
            $character, $character->user, ['name' => 'Nome Segreto'],
        );
        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $this->actingAs(User::factory()->player()->create())
            ->get(route('notifications.index'))
            ->assertDontSee('Nome Segreto');
    });
});

// L'archiviazione nasconde le notifiche dalla lista attiva senza cancellarne lo storico.
describe('archiviare le notifiche', function () {
    beforeEach(function () {
        $this->tizio = User::factory()->player()->create();

        // Una notifica vera, dal ciclo delle richieste.
        $character = Character::factory()->ownedBy($this->tizio)->create();
        $change = app(ProposeChange::class)->edit($character, $this->tizio, ['name' => 'Aldo il Nuovo']);
        app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create());

        $this->notifica = $this->tizio->notifications()->latest()->first();
    });

    it('una notifica si mette via, e sparisce dalle attive', function () {
        $this->actingAs($this->tizio)
            ->post(route('notifications.archive', $this->notifica->id))
            ->assertRedirect();

        expect($this->notifica->fresh()->archived_at)->not->toBeNull();

        $this->actingAs($this->tizio)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Nessuna notifica')
            ->assertDontSee('approvata', false);
    });

    it('ma resta, e si rivede in archivio', function () {
        $this->tizio->notifications()->update(['archived_at' => now()]);

        $this->actingAs($this->tizio)
            ->get(route('notifications.index', ['archiviate' => 1]))
            ->assertOk()
            ->assertSee('approvata', false);
    });

    it('«svuota» le mette via tutte insieme', function () {
        $this->actingAs($this->tizio)
            ->post(route('notifications.clear'))
            ->assertRedirect();

        expect($this->tizio->notifications()->whereNull('archived_at')->count())->toBe(0);
    });

    it('e dall\'archivio si ripesca', function () {
        $this->notifica->update(['archived_at' => now()]);

        $this->actingAs($this->tizio)
            ->post(route('notifications.restore', $this->notifica->id))
            ->assertRedirect();

        expect($this->notifica->fresh()->archived_at)->toBeNull();
    });

    it('quella di un altro non la si tocca', function () {
        $this->actingAs(User::factory()->player()->create())
            ->post(route('notifications.archive', $this->notifica->id))
            ->assertNotFound();

        expect($this->notifica->fresh()->archived_at)->toBeNull();
    });
});
