<?php

declare(strict_types=1);

use App\Actions\Quests\ConcludeQuest;
use App\Actions\Quests\ScheduleQuest;
use App\Enums\QuestOutcome;
use App\Exceptions\QuestUnavailableException;
use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\Quest;
use App\Models\User;
use App\Notifications\QuestScheduled;
use Illuminate\Support\Facades\Notification;

describe('ciclo di vita', function () {
    it('nasce attiva', function () {
        $quest = Quest::factory()->create();

        expect($quest->isActive())->toBeTrue()
            ->and($quest->outcome())->toBe(QuestOutcome::Active)
            ->and($quest->isArchived())->toBeFalse();
    });

    it('completata e chiusa restano stati distinti', function () {

        $completed = Quest::factory()->completed()->create();
        $closed = Quest::factory()->closed()->create();

        expect($completed->outcome())->toBe(QuestOutcome::Completed)
            ->and($closed->outcome())->toBe(QuestOutcome::Closed)
            ->and($completed->isArchived())->toBeTrue()
            ->and($closed->isArchived())->toBeTrue();
    });

    it('completare è irreversibile', function () {
        $quest = Quest::factory()->create();

        app(ConcludeQuest::class)->handle($quest, QuestOutcome::Completed);

        expect($quest->fresh()->isActive())->toBeFalse();

        expect(fn () => app(ConcludeQuest::class)->handle($quest->fresh(), QuestOutcome::Closed))
            ->toThrow(QuestUnavailableException::class);
    });

    it('non si può riportare attiva una quest conclusa', function () {
        $quest = Quest::factory()->completed()->create();

        expect(fn () => app(ConcludeQuest::class)->handle($quest, QuestOutcome::Active))
            ->toThrow(InvalidArgumentException::class);
    });
    // Le date di conclusione passano dall'azione di dominio per preservare l'irreversibilità dello stato.
    it('le date non sono scrivibili da un form', function () {

        $quest = Quest::factory()->create();

        $quest->update(['completed_at' => now(), 'closed_at' => now()]);

        expect($quest->fresh()->isActive())->toBeTrue();
    });
});

describe('«mi interessa»', function () {
    it('si segna e si toglie dalla pagina della quest', function () {
        $quest = Quest::factory()->create();
        $giocatore = User::factory()->player()->create();

        $this->actingAs($giocatore)->post(route('quests.interest', $quest))->assertRedirect();
        expect($quest->isInterested($giocatore))->toBeTrue();

        $this->actingAs($giocatore)->post(route('quests.interest', $quest))->assertRedirect();
        expect($quest->isInterested($giocatore))->toBeFalse();
    });

    it('su una quest conclusa non si segna', function () {
        $quest = Quest::factory()->completed()->create();

        $this->actingAs(User::factory()->player()->create())
            ->post(route('quests.interest', $quest))
            ->assertForbidden();
    });
});

describe('mettere una quest in una sessione', function () {
    it('avvisa chi ha detto «mi interessa», una volta sola', function () {
        Notification::fake();

        $dm = User::factory()->dm()->create();
        $campagna = Campaign::factory()->runBy($dm)->create();
        $quest = Quest::factory()->inCampaign($campagna)->create();
        $sessione = GameSession::factory()->upcoming()->inCampaign($campagna)->create();
        $interessato = User::factory()->player()->create();
        $quest->interested()->attach($interessato, ['joined_at' => now()]);

        $this->actingAs($dm)->post(route('quests.schedule', $quest), ['game_session_id' => $sessione->id])->assertRedirect();
        $this->actingAs($dm)->post(route('quests.schedule', $quest), ['game_session_id' => $sessione->id]);

        expect($quest->fresh()->game_session_id)->toBe($sessione->id)
            ->and($quest->fresh()->isScheduled())->toBeTrue();
        Notification::assertSentToTimes($interessato, QuestScheduled::class, 1);
    });

    it('si toglie dalla sessione', function () {
        $sessione = GameSession::factory()->upcoming()->create();
        $quest = Quest::factory()->inCampaign($sessione->campaign)->create(['game_session_id' => null]);
        app(ScheduleQuest::class)->handle($quest, $sessione);

        app(ScheduleQuest::class)->handle($quest->fresh(), null);

        expect($quest->fresh()->game_session_id)->toBeNull();
    });

    it('solo in una sessione futura della stessa campagna', function () {
        $quest = Quest::factory()->create();

        expect(fn () => app(ScheduleQuest::class)->handle($quest, GameSession::factory()->upcoming()->create()))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => app(ScheduleQuest::class)->handle($quest, GameSession::factory()->inCampaign($quest->campaign)->create()))
            ->toThrow(InvalidArgumentException::class);
    });

    it('spetta al DM della campagna', function () {
        $owner = User::factory()->dm()->create();
        $quest = Quest::factory()->inCampaign(Campaign::factory()->runBy($owner)->create())->create();

        expect($owner->can('schedule', $quest))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('schedule', $quest))->toBeFalse()
            ->and(User::factory()->player()->create()->can('schedule', $quest))->toBeFalse();
    });

    it('la pagina della sessione mostra le sue quest', function () {
        $sessione = GameSession::factory()->upcoming()->create();
        $quest = Quest::factory()->inCampaign($sessione->campaign)->create(['title' => 'La torre del faro']);
        app(ScheduleQuest::class)->handle($quest, $sessione);

        $this->actingAs(User::factory()->player()->create())->get(route('sessions.show', $sessione))
            ->assertOk()
            ->assertSee('Le quest della sessione')
            ->assertSee('La torre del faro');
    });
});

describe('il Libro Mastro', function () {
    it('raccoglie completate e chiuse, non le attive', function () {
        $campaign = Campaign::factory()->create();
        Quest::factory()->inCampaign($campaign)->count(2)->create();
        $done = Quest::factory()->inCampaign($campaign)->completed()->create();
        $abandoned = Quest::factory()->inCampaign($campaign)->closed()->create();

        expect(Quest::archived()->pluck('id')->sort()->values()->all())
            ->toBe([$done->id, $abandoned->id])
            ->and(Quest::active()->count())->toBe(2)
            ->and(Quest::completed()->count())->toBe(1)
            ->and(Quest::closed()->count())->toBe(1);
    });
});
// Le autorizzazioni delle quest restano legate al DM della campagna, a differenza dei permessi globali sui personaggi.
describe('permessi', function () {
    it('le quest le crea il DM della campagna, non un DM qualsiasi', function () {

        $owner = User::factory()->dm()->create();
        $otherDm = User::factory()->dm()->create();
        $campaign = Campaign::factory()->runBy($owner)->create();

        expect($owner->can('create', [Quest::class, $campaign]))->toBeTrue()
            ->and($otherDm->can('create', [Quest::class, $campaign]))->toBeFalse()
            ->and(User::factory()->admin()->create()->can('create', [Quest::class, $campaign]))->toBeTrue()
            ->and(User::factory()->player()->create()->can('create', [Quest::class, $campaign]))->toBeFalse();
    });

    it('in una campagna conclusa non si creano più quest', function () {
        $dm = User::factory()->dm()->create();
        $ended = Campaign::factory()->runBy($dm)->ended()->create();

        expect($dm->can('create', [Quest::class, $ended]))->toBeFalse();
    });

    it('concludere una quest spetta al DM della campagna', function () {
        $owner = User::factory()->dm()->create();
        $quest = Quest::factory()->inCampaign(Campaign::factory()->runBy($owner)->create())->create();

        expect($owner->can('conclude', $quest))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('conclude', $quest))->toBeFalse();
    });

});

describe('la campagna', function () {
    it('porta via le sue quest quando viene cancellata', function () {
        $campaign = Campaign::factory()->create();
        Quest::factory()->inCampaign($campaign)->count(3)->create();

        $campaign->delete();

        expect(Quest::count())->toBe(0);
    });

    it('espone le proprie quest', function () {
        $campaign = Campaign::factory()->create();
        Quest::factory()->inCampaign($campaign)->count(2)->create();
        Quest::factory()->create();

        expect($campaign->quests()->count())->toBe(2);
    });
});
