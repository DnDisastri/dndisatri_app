<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\ProposeChange;
use App\Enums\NotificationCategory;
use App\Models\Character;
use App\Models\User;
use App\Notifications\ChangeAwaitingApproval;
use App\Notifications\RequestDecided;
use Illuminate\Support\Facades\Notification;

it('avvisa DM e admin quando arriva una modifica da approvare', function () {
    Notification::fake();

    $player = User::factory()->player()->create();
    $character = Character::factory()->ownedBy($player)->create();
    $dm = User::factory()->dm()->create();
    $admin = User::factory()->admin()->create();

    app(ProposeChange::class)->loot($character, $player, gp: 50);

    Notification::assertSentTo([$dm, $admin], ChangeAwaitingApproval::class);
    // Chi ha proposto non si avvisa: la richiesta è sua.
    Notification::assertNotSentTo($player, ChangeAwaitingApproval::class);
});

it('non avvisa il DM che ha proposto la modifica, ma gli altri sì', function () {
    Notification::fake();

    $dmProponente = User::factory()->dm()->create();
    $character = Character::factory()->ownedBy($dmProponente)->create();
    $altroDm = User::factory()->dm()->create();

    app(ProposeChange::class)->loot($character, $dmProponente, gp: 10);

    Notification::assertSentTo($altroDm, ChangeAwaitingApproval::class);
    Notification::assertNotSentTo($dmProponente, ChangeAwaitingApproval::class);
});

// Nelle email un indirizzo relativo («/admin/...») non porta da nessuna parte.
it('porta un link completo alla richiesta, che funziona anche da email', function () {
    $player = User::factory()->player()->create();
    $character = Character::factory()->ownedBy($player)->create();
    $change = app(ProposeChange::class)->loot($character, $player, gp: 10);

    $url = (new ChangeAwaitingApproval($change))->toArray(User::factory()->dm()->create())['url'];

    expect($url)->toStartWith(config('app.url'))
        ->and($url)->toEndWith("/admin/pending-changes/{$change->id}");
});

it('dice al DM cosa chiede la richiesta, nella categoria «Da approvare»', function () {
    $player = User::factory()->player()->create();
    $character = Character::factory()->ownedBy($player)->create(['name' => 'Andrea']);
    $change = app(ProposeChange::class)->loot($character, $player, gp: 200);

    $notifica = new ChangeAwaitingApproval($change);

    expect($notifica->category())->toBe(NotificationCategory::Approvals)
        ->and($notifica->toArray($player)['body'])->toContain('Richiesta di Andrea')
        ->and($notifica->toArray($player)['body'])->toContain('Bottino: 200 mo');
});

it('al giocatore tiene la nota del DM separata dal riassunto', function () {
    $player = User::factory()->player()->create();
    $character = Character::factory()->ownedBy($player)->create();
    $change = app(ProposeChange::class)->loot($character, $player, gp: 10);
    app(ApprovePendingChange::class)->handle($change, User::factory()->dm()->create(), 'Goditelo.');

    $body = (new RequestDecided($change->fresh()))->toArray($player)['body'];

    expect($body)->toBe("Bottino: 10 mo\n\nNota: «Goditelo.»");
});

it('non avvisa i giocatori che non possono approvare', function () {
    Notification::fake();

    $player = User::factory()->player()->create();
    $character = Character::factory()->ownedBy($player)->create();
    $altroGiocatore = User::factory()->player()->create();

    app(ProposeChange::class)->loot($character, $player, gp: 10);

    Notification::assertNotSentTo($altroGiocatore, ChangeAwaitingApproval::class);
});
