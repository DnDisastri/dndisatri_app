<?php

declare(strict_types=1);

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\ProposeChange;
use App\Actions\Sessions\RecordAttendance;
use App\Domain\Dnd\Coins;
use App\Enums\LedgerAction;
use App\Filament\Resources\GameSessions\Pages\CreateGameSession;
use App\Filament\Resources\GameSessions\Pages\EditGameSession;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\Quest;
use App\Models\User;
use App\Notifications\ChangeAwaitingApproval;
use App\Notifications\GameSessionScheduled;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

describe('le bozze', function () {
    beforeEach(function () {
        $this->dm = User::factory()->dm()->create();
        $this->giocatore = User::factory()->player()->create();
        $this->campagna = Campaign::factory()->create(['dm_id' => $this->dm->id]);
    });

    it('una sessione in bozza non la vede nessun giocatore, i DM sì', function () {
        $bozza = GameSession::factory()->upcoming()->inCampaign($this->campagna)->draft()->create(['title' => 'Il segreto']);

        $this->actingAs($this->giocatore)->get(route('sessions.show', $bozza))->assertNotFound();
        $this->actingAs($this->giocatore)->get(route('sessions.index', ['mese' => $bozza->played_at->format('Y-m')]))->assertDontSee('Il segreto');

        $this->actingAs($this->dm)->get(route('sessions.show', $bozza))
            ->assertOk()
            ->assertSee('Bozza: i giocatori non la vedono');

        expect($bozza->acceptsBookings())->toBeFalse();
    });

    it('una quest in bozza non la vede nessun giocatore', function () {
        $bozza = Quest::factory()->inCampaign($this->campagna)->draft()->create();

        $this->actingAs($this->giocatore)->get(route('quests.show', $bozza))->assertNotFound();
        $this->actingAs($this->dm)->get(route('quests.show', $bozza))->assertOk()->assertSee('Bozza');
    });

    it('creata in bozza non avvisa; pubblicata avvisa una volta sola', function () {
        Notification::fake();

        Livewire::actingAs($this->dm)
            ->test(CreateGameSession::class)
            ->fillForm([
                'campaign_id' => $this->campagna->id,
                'title' => 'Il ritorno',
                'played_at' => now()->addWeek(),
                'bozza' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $sessione = GameSession::firstWhere('title', 'Il ritorno');
        expect($sessione->isDraft())->toBeTrue();
        Notification::assertNothingSent();

        $modifica = fn () => Livewire::actingAs($this->dm)
            ->test(EditGameSession::class, ['record' => $sessione->getRouteKey()])
            ->fillForm(['bozza' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $modifica();
        $modifica();

        expect($sessione->fresh()->isDraft())->toBeFalse();
        Notification::assertSentToTimes($this->giocatore, GameSessionScheduled::class, 1);
    });
});

it('una sessione pubblicata non torna bozza', function () {
    $sessione = GameSession::factory()->upcoming()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(EditGameSession::class, ['record' => $sessione->getRouteKey()])
        ->assertFormFieldIsHidden('bozza');
});

describe('le ricompense con gli oggetti', function () {
    it('vanno a ciascuno dei presenti, e restano nel Registro', function () {
        $dm = User::factory()->dm()->create();
        $sessione = GameSession::factory()->create(['played_at' => now()->subDay()]);
        $anna = Character::factory()->create(['name' => 'Anna']);
        $bruno = Character::factory()->create(['name' => 'Bruno']);
        app(RecordAttendance::class)->handle($sessione, [$anna->user_id => $anna->id, $bruno->user_id => $bruno->id]);

        $this->actingAs($dm)
            ->post(route('sessions.rewards', $sessione), [
                'reason' => 'Il tesoro del drago',
                'items' => [
                    ['name' => 'Pozione di guarigione', 'qty' => 2, 'value' => '50'],
                    ['name' => ''],
                ],
            ])
            ->assertSessionHas('status');

        foreach ([$anna, $bruno] as $pg) {
            expect($pg->items()->where('name', 'Pozione di guarigione')->value('qty'))->toBe(2)
                ->and($pg->ledgerEntries()->where('action', LedgerAction::DmItems)->value('message'))->toContain('2× Pozione di guarigione');
        }

        expect($sessione->fresh()->rewards[0]['items'])->toBe(['2× Pozione di guarigione']);
    });

    it('senza monete né oggetti non parte niente', function () {
        $sessione = GameSession::factory()->create(['played_at' => now()->subDay()]);
        $anna = Character::factory()->create();
        app(RecordAttendance::class)->handle($sessione, [$anna->user_id => $anna->id]);

        $this->actingAs(User::factory()->dm()->create())
            ->post(route('sessions.rewards', $sessione), ['reason' => 'Niente'])
            ->assertSessionHas('error');
    });
});

describe('le richieste ai DM della sessione', function () {
    beforeEach(function () {
        $this->titolare = User::factory()->dm()->create();
        $this->coMaster = User::factory()->dm()->create();
        $this->altroDm = User::factory()->dm()->create();
        $this->admin = User::factory()->admin()->create();

        $this->sessione = GameSession::factory()->inCampaign(Campaign::factory()->create(['dm_id' => $this->titolare->id]))
            ->create(['played_at' => now()->subDay()]);
        $this->sessione->dms()->attach($this->coMaster);

        $this->giocatore = User::factory()->player()->create();
        $this->pg = Character::factory()->ownedBy($this->giocatore)->create();
        app(RecordAttendance::class)->handle($this->sessione, [$this->giocatore->id => $this->pg->id]);
    });

    it('il bottino di una sessione avvisa i suoi DM e gli admin, non gli altri DM', function () {
        Notification::fake();

        $this->actingAs($this->giocatore)
            ->post(route('proposals.loot', $this->pg), ['coins' => ['gp' => 10], 'game_session_id' => $this->sessione->id])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo([$this->titolare, $this->coMaster, $this->admin], ChangeAwaitingApproval::class);
        Notification::assertNotSentTo($this->altroDm, ChangeAwaitingApproval::class);
    });

    it('una sessione che il personaggio non ha giocato non conta: avvisa tutti', function () {
        Notification::fake();
        $estranea = GameSession::factory()->create(['played_at' => now()->subDay()]);

        $this->actingAs($this->giocatore)
            ->post(route('proposals.loot', $this->pg), ['coins' => ['gp' => 10], 'game_session_id' => $estranea->id]);

        Notification::assertSentTo([$this->titolare, $this->altroDm, $this->admin], ChangeAwaitingApproval::class);
    });
});

describe('le richieste già decise', function () {
    it('vanno in archivio da sole per gli altri, con l\'esito', function () {
        $giocatore = User::factory()->player()->create();
        $pg = Character::factory()->ownedBy($giocatore)->create();
        $chiDecide = User::factory()->dm()->create(['name' => 'Margherita']);
        $altro = User::factory()->dm()->create();

        $richiesta = app(ProposeChange::class)->loot($pg, $giocatore, coins: new Coins(gp: 10));

        expect($altro->notifications()->whereNull('archived_at')->count())->toBe(1);

        app(ApprovePendingChange::class)->handle($richiesta, $chiDecide);

        $notifica = $altro->notifications()->first();

        expect($notifica->archived_at)->not->toBeNull()
            ->and($notifica->data['outcome'])->toBe('Approvata da Margherita il '.now()->format('d/m').'.');

        $this->actingAs($altro)
            ->get(route('notifications.index', ['archiviate' => 1]))
            ->assertSee('Approvata da Margherita');
    });

    it('il comando recupera le vecchie dal link, e in prova non tocca niente', function () {
        $giocatore = User::factory()->player()->create();
        $pg = Character::factory()->ownedBy($giocatore)->create();
        $altro = User::factory()->dm()->create();
        $richiesta = app(ProposeChange::class)->loot($pg, $giocatore, coins: new Coins(gp: 10));

        // Com'erano prima: senza il riferimento alla richiesta.
        $notifica = $altro->notifications()->first();
        $notifica->forceFill(['data' => collect($notifica->data)->except('subject')->all()])->save();
        $richiesta->forceFill(['status' => 'approved', 'reviewed_by' => User::factory()->admin()->create()->id, 'reviewed_at' => now()])->save();

        $this->artisan('dndisastri:archivia-notifiche-decise', ['--dry-run' => true])->assertSuccessful();
        expect($notifica->fresh()->archived_at)->toBeNull();

        $this->artisan('dndisastri:archivia-notifiche-decise')->assertSuccessful();
        expect($notifica->fresh()->archived_at)->not->toBeNull()
            ->and($notifica->fresh()->data['subject']['id'])->toBe($richiesta->id);
    });
});

describe('il pallino delle notifiche', function () {
    it('non conta le archiviate, e il conteggio si richiede tornando indietro', function () {
        $giocatore = User::factory()->player()->create();
        $pg = Character::factory()->ownedBy($giocatore)->create();
        $dm = User::factory()->dm()->create();
        $richiesta = app(ProposeChange::class)->loot($pg, $giocatore, coins: new Coins(gp: 10));

        $this->actingAs($dm)->getJson(route('notifications.unread'))->assertExactJson(['n' => 1]);

        app(ApprovePendingChange::class)->handle($richiesta, User::factory()->admin()->create());

        $this->actingAs($dm)->getJson(route('notifications.unread'))->assertExactJson(['n' => 0]);
        $this->actingAs($dm)->get(route('home'))->assertDontSee('data-non-lette', false);
    });
});
