<?php

declare(strict_types=1);

use App\Actions\Characters\ProposeChange;
use App\Models\Character;
use App\Models\User;
use App\Notifications\ChangeAwaitingApproval;
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

it('non avvisa i giocatori che non possono approvare', function () {
    Notification::fake();

    $player = User::factory()->player()->create();
    $character = Character::factory()->ownedBy($player)->create();
    $altroGiocatore = User::factory()->player()->create();

    app(ProposeChange::class)->loot($character, $player, gp: 10);

    Notification::assertNotSentTo($altroGiocatore, ChangeAwaitingApproval::class);
});
