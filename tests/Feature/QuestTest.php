<?php

declare(strict_types=1);

use App\Actions\Quests\BookQuestSeat;
use App\Actions\Quests\ConcludeQuest;
use App\Actions\Quests\ConfirmQuestNight;
use App\Actions\Quests\PromoteFromWaitingList;
use App\Actions\Quests\WithdrawFromQuest;
use App\Enums\QuestOutcome;
use App\Enums\QuestSeatStatus;
use App\Exceptions\QuestUnavailableException;
use App\Models\Campaign;
use App\Models\Quest;
use App\Models\User;
use App\Notifications\QuestSeatConfirmed;
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

describe('le prenotazioni', function () {
    it('occupano un posto appena si prenotano', function () {
        $quest = Quest::factory()->slots(3)->create();

        $stato = app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());

        expect($stato)->toBe(QuestSeatStatus::Booked)
            ->and($quest->fresh()->freeSlots())->toBe(2)
            ->and($quest->fresh()->isFull())->toBeFalse();
    });
// A posti esauriti una nuova prenotazione entra in lista d'attesa e non occupa un posto.
    it('a posti esauriti finiscono in lista d\'attesa, non respinte', function () {
        $quest = Quest::factory()->slots(1)->create();
        app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());

        $tardivo = User::factory()->player()->create();
        $stato = app(BookQuestSeat::class)->handle($quest->fresh(), $tardivo);

        expect($stato)->toBe(QuestSeatStatus::Waiting)
            ->and($quest->fresh()->waiting()->count())->toBe(1)
            ->and($quest->fresh()->participantCount())->toBe(1);
    });

    it('prenotarsi due volte non occupa due posti', function () {
        $quest = Quest::factory()->slots(3)->create();
        $player = User::factory()->player()->create();

        app(BookQuestSeat::class)->handle($quest, $player);
        app(BookQuestSeat::class)->handle($quest->fresh(), $player);

        expect($quest->fresh()->participantCount())->toBe(1);
    });

    it('non retrocede un posto già confermato', function () {
        $quest = Quest::factory()->slots(3)->create();
        $player = User::factory()->player()->create();

        app(BookQuestSeat::class)->handle($quest, $player);
        app(ConfirmQuestNight::class)->handle($quest->fresh());

        app(BookQuestSeat::class)->handle($quest->fresh(), $player);

        expect($quest->fresh()->seatOf($player))->toBe(QuestSeatStatus::Confirmed);
    });
// Il ritiro libera il posto ma conserva la riga del partecipante come storico.
    it('ritirarsi libera il posto ma lascia la traccia', function () {
        $quest = Quest::factory()->slots(2)->create();
        $player = User::factory()->player()->create();

        app(BookQuestSeat::class)->handle($quest, $player);
        app(WithdrawFromQuest::class)->handle($quest->fresh(), $player);

        $quest = $quest->fresh();

        expect($quest->freeSlots())->toBe(2)
            ->and($quest->seatOf($player))->toBe(QuestSeatStatus::Withdrawn)
            ->and($quest->participants()->count())->toBe(1);
    });

    it('chi si è ritirato può ripensarci', function () {
        $quest = Quest::factory()->slots(2)->create();
        $player = User::factory()->player()->create();

        app(BookQuestSeat::class)->handle($quest, $player);
        app(WithdrawFromQuest::class)->handle($quest->fresh(), $player);
        $stato = app(BookQuestSeat::class)->handle($quest->fresh(), $player);

        expect($stato)->toBe(QuestSeatStatus::Booked)
            ->and($quest->fresh()->participantCount())->toBe(1)
            ->and($quest->fresh()->participants()->count())->toBe(1);
    });

    it('da una quest conclusa non ci si prenota né ci si ritira', function () {
        $quest = Quest::factory()->slots(4)->create();
        $player = User::factory()->player()->create();
        app(BookQuestSeat::class)->handle($quest, $player);

        app(ConcludeQuest::class)->handle($quest->fresh(), QuestOutcome::Completed);
        $concluded = $quest->fresh();

        expect(fn () => app(BookQuestSeat::class)->handle($concluded, User::factory()->player()->create()))
            ->toThrow(QuestUnavailableException::class);

        expect(fn () => app(WithdrawFromQuest::class)->handle($concluded, $player))
            ->toThrow(QuestUnavailableException::class);
    });
});

describe('il minimo per far partire il tavolo', function () {

    it('dice quanti ne mancano', function () {
        $quest = Quest::factory()->slots(6)->create(['min_participants' => 4]);

        app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());
        app(BookQuestSeat::class)->handle($quest->fresh(), User::factory()->player()->create());

        $quest = $quest->fresh();

        expect($quest->hasMinimum())->toBeFalse()
            ->and($quest->missingToMinimum())->toBe(2);
    });
// Il minimo dei partecipanti è informativo: la decisione finale di confermare la serata resta al DM.
    it('non impedisce niente: la serata la decide chi conduce', function () {
        $quest = Quest::factory()->slots(6)->create(['min_participants' => 4]);
        $player = User::factory()->player()->create();
        app(BookQuestSeat::class)->handle($quest, $player);

        app(ConfirmQuestNight::class)->handle($quest->fresh());

        expect($quest->fresh()->seatOf($player))->toBe(QuestSeatStatus::Confirmed);
    });
});

describe('«la serata si fa»', function () {
    it('conferma tutti i prenotati insieme e li avvisa', function () {
        Notification::fake();

        $quest = Quest::factory()->slots(4)->create();
        $primo = User::factory()->player()->create();
        $secondo = User::factory()->player()->create();

        app(BookQuestSeat::class)->handle($quest, $primo);
        app(BookQuestSeat::class)->handle($quest->fresh(), $secondo);

        app(ConfirmQuestNight::class)->handle($quest->fresh());
        $quest = $quest->fresh();

        expect($quest->confirmed()->count())->toBe(2)
            ->and($quest->booked()->count())->toBe(0)
            ->and($quest->isNightConfirmed())->toBeTrue();

        Notification::assertSentTo([$primo, $secondo], QuestSeatConfirmed::class);
    });

    it('non tocca la lista d\'attesa', function () {
        $quest = Quest::factory()->slots(1)->create();
        app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());

        $inAttesa = User::factory()->player()->create();
        app(BookQuestSeat::class)->handle($quest->fresh(), $inAttesa);

        app(ConfirmQuestNight::class)->handle($quest->fresh());

        expect($quest->fresh()->seatOf($inAttesa))->toBe(QuestSeatStatus::Waiting);
    });
});

describe('la lista d\'attesa', function () {
    it('tiene l\'ordine di arrivo', function () {
        $quest = Quest::factory()->slots(1)->create();
        app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());

        $primo = User::factory()->player()->create(['name' => 'Arrivato prima']);
        $secondo = User::factory()->player()->create(['name' => 'Arrivato dopo']);

        app(BookQuestSeat::class)->handle($quest->fresh(), $primo);
        $this->travel(1)->minutes();
        app(BookQuestSeat::class)->handle($quest->fresh(), $secondo);

        expect($quest->fresh()->waiting()->pluck('name')->all())
            ->toBe(['Arrivato prima', 'Arrivato dopo']);
    });

    it('si pesca solo con un posto libero', function () {
        $quest = Quest::factory()->slots(1)->create();
        app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());

        $inAttesa = User::factory()->player()->create();
        app(BookQuestSeat::class)->handle($quest->fresh(), $inAttesa);

        expect(fn () => app(PromoteFromWaitingList::class)->handle($quest->fresh(), $inAttesa))
            ->toThrow(QuestUnavailableException::class);
    });

    it('entra come prenotato se la serata non è ancora confermata', function () {
        $quest = Quest::factory()->slots(1)->create();
        $primo = User::factory()->player()->create();
        app(BookQuestSeat::class)->handle($quest, $primo);

        $inAttesa = User::factory()->player()->create();
        app(BookQuestSeat::class)->handle($quest->fresh(), $inAttesa);

        app(WithdrawFromQuest::class)->handle($quest->fresh(), $primo);
        $stato = app(PromoteFromWaitingList::class)->handle($quest->fresh(), $inAttesa);

        expect($stato)->toBe(QuestSeatStatus::Booked);
    });

// Se la serata è già confermata, chi viene promosso dalla lista d'attesa entra direttamente come confermato.
    it('entra come confermato se la serata si fa già', function () {
        Notification::fake();

        $quest = Quest::factory()->slots(1)->create();
        $primo = User::factory()->player()->create();
        app(BookQuestSeat::class)->handle($quest, $primo);

        $inAttesa = User::factory()->player()->create();
        app(BookQuestSeat::class)->handle($quest->fresh(), $inAttesa);

        app(ConfirmQuestNight::class)->handle($quest->fresh());
        app(WithdrawFromQuest::class)->handle($quest->fresh(), $primo);

        $stato = app(PromoteFromWaitingList::class)->handle($quest->fresh(), $inAttesa);

        expect($stato)->toBe(QuestSeatStatus::Confirmed);
        Notification::assertSentTo($inAttesa, QuestSeatConfirmed::class);
    });

    it('non pesca chi non è in attesa', function () {
        $quest = Quest::factory()->slots(3)->create();
        $prenotato = User::factory()->player()->create();
        app(BookQuestSeat::class)->handle($quest, $prenotato);

        expect(fn () => app(PromoteFromWaitingList::class)->handle($quest->fresh(), $prenotato))
            ->toThrow(QuestUnavailableException::class);
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
    it('le quest le crea il DM del tavolo, non un DM qualsiasi', function () {

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

    it('concludere una quest spetta al DM del tavolo', function () {
        $owner = User::factory()->dm()->create();
        $quest = Quest::factory()->inCampaign(Campaign::factory()->runBy($owner)->create())->create();

        expect($owner->can('conclude', $quest))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('conclude', $quest))->toBeFalse();
    });

    it('i giocatori si prenotano da soli', function () {
        $quest = Quest::factory()->slots(2)->create();
        $player = User::factory()->player()->create();

        expect($player->can('book', $quest))->toBeTrue()
            ->and($player->can('withdraw', $quest))->toBeFalse();

        app(BookQuestSeat::class)->handle($quest, $player);
        $quest = $quest->fresh();

        expect($player->can('book', $quest))->toBeFalse()
            ->and($player->can('withdraw', $quest))->toBeTrue();
    });


    it('a posti esauriti la prenotazione resta possibile', function () {
        $quest = Quest::factory()->slots(1)->create();
        app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());

        expect(User::factory()->player()->create()->can('book', $quest->fresh()))->toBeTrue();
    });
// La supervisione dei richiami riguarda le azioni di mercato e non impedisce la prenotazione alle quest.
    it('un richiamo non impedisce di prenotarsi', function () {

        $quest = Quest::factory()->slots(3)->create();
        $richiamato = User::factory()->player()->create();

        app(App\Actions\Users\IssueWarning::class)->handle(
            $richiamato,
            User::factory()->admin()->create(),
            'Prova',
        );

        expect($richiamato->fresh()->isUnderWarning())->toBeTrue()
            ->and($richiamato->fresh()->can('book', $quest))->toBeTrue();
    });

    it('confermare la serata spetta al DM del tavolo, e solo se c\'è qualcuno', function () {
        $owner = User::factory()->dm()->create();
        $quest = Quest::factory()->inCampaign(Campaign::factory()->runBy($owner)->create())->create();

        expect($owner->can('confirmNight', $quest))->toBeFalse();

        app(BookQuestSeat::class)->handle($quest, User::factory()->player()->create());
        $quest = $quest->fresh();

        expect($owner->can('confirmNight', $quest))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('confirmNight', $quest))->toBeFalse()
            ->and(User::factory()->player()->create()->can('confirmNight', $quest))->toBeFalse();
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
