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
