<?php

declare(strict_types=1);

use App\Actions\Sessions\RecordAttendance;
use App\Actions\Sessions\WriteRecap;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\Quest;
use App\Models\User;

describe('calendario e storico', function () {
    it('distingue le sessioni in programma da quelle giocate', function () {
        $campaign = Campaign::factory()->create();
        $next = GameSession::factory()->inCampaign($campaign)->upcoming()->create();
        $last = GameSession::factory()->inCampaign($campaign)->create();

        expect($next->isUpcoming())->toBeTrue()
            ->and($last->isUpcoming())->toBeFalse()
            ->and(GameSession::upcoming()->pluck('id')->all())->toBe([$next->id])
            ->and(GameSession::past()->pluck('id')->all())->toBe([$last->id]);
    });

    it('ordina il calendario dalla prossima e lo storico dalla più recente', function () {
        $campaign = Campaign::factory()->create();
        $farther = GameSession::factory()->inCampaign($campaign)->playedOn(now()->addWeeks(3))->create();
        $sooner = GameSession::factory()->inCampaign($campaign)->playedOn(now()->addDay())->create();
        $older = GameSession::factory()->inCampaign($campaign)->playedOn(now()->subMonth())->create();
        $recent = GameSession::factory()->inCampaign($campaign)->playedOn(now()->subDay())->create();

        expect(GameSession::upcoming()->pluck('id')->all())->toBe([$sooner->id, $farther->id])
            ->and(GameSession::past()->pluck('id')->all())->toBe([$recent->id, $older->id]);
    });

    it('compone il titolo con il numero progressivo', function () {
        $numbered = GameSession::factory()->create(['number' => 12, 'title' => 'La Torre Nera']);
        $untitled = GameSession::factory()->create(['number' => 3, 'title' => null]);
        $unnumbered = GameSession::factory()->create(['number' => null, 'title' => null]);

        expect($numbered->displayTitle())->toBe('Sessione 12: La Torre Nera')
            ->and($untitled->displayTitle())->toBe('Sessione 3')
            ->and($unnumbered->displayTitle())->toBe('Sessione');
    });
});

describe('il recap', function () {
    it('porta con sé chi l\'ha scritto e quando', function () {
        $dm = User::factory()->dm()->create();

        $session = GameSession::factory()->create();

        expect($session->hasRecap())->toBeFalse();

        app(WriteRecap::class)->handle($session, $dm, 'Il gruppo ha bruciato la locanda. Di nuovo.');

        $session = $session->fresh();

        expect($session->hasRecap())->toBeTrue()
            ->and($session->recap)->toContain('locanda')
            ->and($session->recap_written_by)->toBe($dm->id)
            ->and($session->recap_written_at)->not->toBeNull();
    });

    it('non si può scrivere lasciando indietro la firma', function () {
        // Recap e firma passano dall'azione di dominio e non possono essere impostati tramite mass assignment.
        $session = GameSession::factory()->create();

        $session->update(['recap' => 'testo di contrabbando']);

        expect($session->fresh()->hasRecap())->toBeFalse();
    });

    it('lo leggono tutti, anche chi gioca a un altro tavolo', function () {
        $session = GameSession::factory()->create();

        expect(User::factory()->player()->create()->can('view', $session))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('view', $session))->toBeTrue();
    });

    it('lo scrivono il DM di quel tavolo e gli admin, non gli altri DM', function () {
        $owner = User::factory()->dm()->create();
        $session = GameSession::factory()->inCampaign(Campaign::factory()->runBy($owner)->create())->create();

        expect($owner->can('writeRecap', $session))->toBeTrue()
            ->and(User::factory()->admin()->create()->can('writeRecap', $session))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('writeRecap', $session))->toBeFalse()
            ->and(User::factory()->player()->create()->can('writeRecap', $session))->toBeFalse();
    });

    it('si può scrivere anche a campagna conclusa', function () {

        $owner = User::factory()->dm()->create();
        $ended = Campaign::factory()->runBy($owner)->ended()->create();
        $session = GameSession::factory()->inCampaign($ended)->create();

        expect($owner->can('writeRecap', $session))->toBeTrue();
    });
});

describe('le presenze', function () {
    it('registrano chi c\'era davvero', function () {
        $session = GameSession::factory()->create();
        [$present, $absent] = User::factory()->player()->count(2)->create();

        app(RecordAttendance::class)->handle($session, [$present->id]);

        expect($session->attended($present))->toBeTrue()
            ->and($session->attended($absent))->toBeFalse();
    });

    it('sostituiscono l\'elenco invece di aggiungere, così le correzioni valgono', function () {
        $session = GameSession::factory()->create();
        [$first, $second] = User::factory()->player()->count(2)->create();

        app(RecordAttendance::class)->handle($session, [$first->id, $second->id]);
        app(RecordAttendance::class)->handle($session, [$second->id]);

        expect($session->fresh()->attendees()->pluck('users.id')->all())->toBe([$second->id]);
    });

    it('non richiedono che ci si fosse iscritti a una quest', function () {

        $session = GameSession::factory()->create();
        $walkIn = User::factory()->player()->create();

        app(RecordAttendance::class)->handle($session, [$walkIn->id]);

        expect($session->fresh()->attendees()->count())->toBe(1);
    });

    it('dicono anche con quale personaggio si giocava', function () {
        $session = GameSession::factory()->create();
        $player = User::factory()->player()->create();
        $hero = Character::factory()->for($player)->create();

        app(RecordAttendance::class)->handle($session, [$player->id => $hero->id]);

        expect($session->fresh()->playedCharacters->pluck('id')->all())->toBe([$hero->id]);
    });

    it('ma il personaggio si può omettere: chi conduce non ne gioca uno', function () {
        $session = GameSession::factory()->create();
        $dm = User::factory()->dm()->create();

        app(RecordAttendance::class)->handle($session, [$dm->id => null]);

        expect($session->attended($dm))->toBeTrue()
            ->and($session->fresh()->playedCharacters)->toBeEmpty();
    });

    it('e non si attribuisce a un giocatore il personaggio di un altro', function () {
        $session = GameSession::factory()->create();
        $player = User::factory()->player()->create();
        $altrui = Character::factory()->create();

        expect(fn () => app(RecordAttendance::class)->handle($session, [$player->id => $altrui->id]))
            ->toThrow(InvalidArgumentException::class, 'non è di quel giocatore');
    });

    it('le segna il DM del tavolo, non i giocatori', function () {
        $owner = User::factory()->dm()->create();
        $session = GameSession::factory()->inCampaign(Campaign::factory()->runBy($owner)->create())->create();

        expect($owner->can('recordAttendance', $session))->toBeTrue()
            ->and(User::factory()->player()->create()->can('recordAttendance', $session))->toBeFalse();
    });
});

describe('il capogilda', function () {
    it('sta sulla campagna, non sulla quest', function () {
        $campaign = Campaign::factory()->create(['quest_giver' => 'Ser Baldrico']);
        $quest = Quest::factory()->inCampaign($campaign)->create();

        expect($quest->load('campaign')->questGiver())->toBe('Ser Baldrico');
    });

    it('vale per tutte le quest del tavolo, senza copie da allineare', function () {
        $campaign = Campaign::factory()->create(['quest_giver' => 'Ser Baldrico']);
        Quest::factory()->inCampaign($campaign)->count(3)->create();

        $campaign->update(['quest_giver' => 'Dama Ortensia']);

        foreach ($campaign->quests()->with('campaign')->get() as $quest) {
            expect($quest->questGiver())->toBe('Dama Ortensia');
        }
    });
});

describe('le sessioni e la campagna', function () {
    it('spariscono con lei', function () {
        $campaign = Campaign::factory()->create();
        GameSession::factory()->inCampaign($campaign)->count(3)->create();

        $campaign->delete();

        expect(GameSession::count())->toBe(0);
    });

    it('le crea il DM del tavolo, e solo se la campagna è aperta', function () {
        $owner = User::factory()->dm()->create();
        $active = Campaign::factory()->runBy($owner)->create();
        $ended = Campaign::factory()->runBy($owner)->ended()->create();

        expect($owner->can('create', [GameSession::class, $active]))->toBeTrue()
            ->and($owner->can('create', [GameSession::class, $ended]))->toBeFalse()
            ->and(User::factory()->dm()->create()->can('create', [GameSession::class, $active]))->toBeFalse();
    });
});
