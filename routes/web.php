<?php

use App\Enums\SheetSection;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\BugReportController;
use App\Http\Controllers\BuildController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\DmController;
use App\Http\Controllers\EncounterController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\GameSessionController;
use App\Http\Controllers\GuestBookingController;
use App\Http\Controllers\GuildController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PendingChangePhotoController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\SessionBookingController;
use App\Http\Controllers\SupervisionController;
use App\Livewire\CharacterWizard;
use App\Livewire\NpcManager;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Pubblica: la si raggiunge dalla presentazione, prima dell'accesso.
Route::get('chi-siamo', [AboutController::class, 'show'])->name('about');

// Ospiti senza account: il modulo per chiedere un posto e la pagina personale col token.
Route::get('calendario', [GuestBookingController::class, 'calendar'])->name('guest-bookings.calendar');
Route::post('calendario', [GuestBookingController::class, 'storeCalendar'])
    ->middleware('throttle:'.GuestBookingController::LIMITATORE)
    ->name('guest-bookings.calendar.store');
Route::get('serate/{session}/chiedi-un-posto', [GuestBookingController::class, 'create'])->name('guest-bookings.create');
Route::post('serate/{session}/chiedi-un-posto', [GuestBookingController::class, 'store'])
    ->middleware('throttle:'.GuestBookingController::LIMITATORE)
    ->name('guest-bookings.store');
Route::get('prenotazione/{token}', [GuestBookingController::class, 'show'])->whereUuid('token')->name('guest-bookings.show');
Route::get('prenotazione/{token}/verifica', [GuestBookingController::class, 'verify'])->whereUuid('token')->name('guest-bookings.verify');
Route::post('prenotazione/{token}/offerta', [GuestBookingController::class, 'answerOffer'])->whereUuid('token')->name('guest-bookings.answer-offer');
Route::post('prenotazione/{token}/riserva', [GuestBookingController::class, 'answerReserve'])->whereUuid('token')->name('guest-bookings.answer-reserve');
Route::post('prenotazione/{token}/ritira', [GuestBookingController::class, 'withdraw'])->whereUuid('token')->name('guest-bookings.withdraw');

Route::middleware('auth')->group(function () {

    Route::get('bacheca/{change}/foto', [PendingChangePhotoController::class, 'show'])->name('pending-changes.photo');

    Route::get('notifiche', [NotificationController::class, 'index'])->name('notifications.index');
    // La rotta fissa deve precedere `{notification}` per non essere interpretata come parametro dinamico.
    Route::post('notifiche/svuota', [NotificationController::class, 'clear'])->name('notifications.clear');
    Route::delete('notifiche/archivio', [NotificationController::class, 'emptyArchive'])->name('notifications.empty-archive');
    Route::post('notifiche/{notification}/archivia', [NotificationController::class, 'archive'])->name('notifications.archive');
    Route::post('notifiche/{notification}/ripristina', [NotificationController::class, 'restore'])->name('notifications.restore');
    Route::delete('notifiche/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('profilo', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profilo', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profilo/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('profilo/notifiche', [ProfileController::class, 'updateNotifications'])->name('profile.notifications');

    Route::get('profilo/richiami', [ProfileController::class, 'warnings'])->name('profile.warnings');

    Route::get('segnala', [BugReportController::class, 'create'])->name('bug-reports.create');
    Route::post('segnala', [BugReportController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('bug-reports.store');

    Route::get('gilda', [GuildController::class, 'index'])->name('guild.index');

    Route::get('guida', [FaqController::class, 'index'])->name('faq.index');

    Route::get('area-master', [DmController::class, 'home'])->name('dm.home');
    // I vecchi indirizzi della Regia, rimasti nei preferiti.
    Route::redirect('regia', '/area-master');
    Route::redirect('regia/serata/{session}/prepara', '/area-master/serata/{session}/prepara');

    Route::get('area-master/serata/{session}/prepara', [DmController::class, 'prepare'])->name('dm.prepare');
    Route::put('area-master/campagne/{campaign}/nota', [DmController::class, 'handover'])->name('dm.handover');
    Route::get('area-master/png', NpcManager::class)->name('dm.npcs');
    Route::get('area-master/manuale', [ManualController::class, 'show'])->name('dm.manual');

    Route::get('area-master/combattimenti', [EncounterController::class, 'index'])->name('encounters.index');
    Route::post('area-master/combattimenti', [EncounterController::class, 'store'])->name('encounters.store');
    Route::get('area-master/combattimenti/{encounter}', [EncounterController::class, 'show'])->name('encounters.show');
    Route::patch('area-master/combattimenti/{encounter}', [EncounterController::class, 'update'])->name('encounters.update');
    Route::delete('area-master/combattimenti/{encounter}', [EncounterController::class, 'destroy'])->name('encounters.destroy');
    // Mantiene compatibili i vecchi link a `/caduti`; il redirect resta temporaneo per evitare cache permanenti.
    Route::redirect('caduti', '/gilda#caduti')->name('guild.fallen');

    Route::get('caduti/{character}', [GuildController::class, 'fallenShow'])->name('fallen.show');

    Route::get('campagne', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('campagne/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');

    Route::get('incarichi', [QuestController::class, 'index'])->name('quests.index');
    Route::get('incarichi/{quest}', [QuestController::class, 'show'])->name('quests.show');

    Route::post('incarichi/{quest}/interessa', [QuestController::class, 'interest'])->name('quests.interest');
    Route::post('incarichi/{quest}/sessione', [QuestController::class, 'schedule'])->name('quests.schedule');
    Route::post('incarichi/{quest}/concludi', [QuestController::class, 'conclude'])->name('quests.conclude');

    Route::get('serate', [GameSessionController::class, 'index'])->name('sessions.index');
    Route::post('serate/richiedi', [SessionBookingController::class, 'bookMany'])->name('sessions.book-many');
    Route::get('le-mie-prenotazioni', [SessionBookingController::class, 'mine'])->name('sessions.mine');
    Route::get('serate/{session}', [GameSessionController::class, 'show'])->name('sessions.show');
    Route::post('serate/{session}/prenota', [SessionBookingController::class, 'book'])->name('sessions.book');
    Route::post('serate/{session}/ritirati', [SessionBookingController::class, 'withdraw'])->name('sessions.withdraw');
    Route::post('serate/{session}/offerta', [SessionBookingController::class, 'answerOffer'])->name('sessions.answer-offer');
    Route::post('serate/{session}/riserva', [SessionBookingController::class, 'answerReserve'])->name('sessions.answer-reserve');
    Route::post('serate/{session}/offri', [SessionBookingController::class, 'offer'])->name('sessions.offer');
    Route::post('serate/{session}/ospiti', [SessionBookingController::class, 'addGuest'])->name('sessions.guests.store');
    Route::post('serate/{session}/ospiti/{booking}/togli', [SessionBookingController::class, 'removeGuest'])->whereNumber('booking')->name('sessions.guests.remove');
    Route::post('serate/{session}/ospiti/{booking}/collega', [SessionBookingController::class, 'linkGuest'])->whereNumber('booking')->name('sessions.guests.link');
    Route::post('serate/{session}/resoconto', [GameSessionController::class, 'writeRecap'])->name('sessions.recap');
    Route::post('serate/{session}/presenze', [GameSessionController::class, 'recordAttendance'])->name('sessions.attendance');
    Route::post('serate/{session}/ricompense', [GameSessionController::class, 'awardRewards'])->name('sessions.rewards');

    Route::post('reazioni/{tipo}/{id}', [ReactionController::class, 'store'])->name('reactions.store');

    Route::get('libro-mastro', [LedgerController::class, 'index'])->name('ledger.index');

    Route::get('personaggi', [CharacterController::class, 'index'])->name('characters.index');

    Route::get('eventi', [EventController::class, 'index'])->name('events.index');
    Route::get('eventi/{event}', [EventController::class, 'show'])->name('events.show');

    Route::get('news', [PostController::class, 'index'])->name('news.index');
    Route::get('news/{post}', [PostController::class, 'show'])->name('news.show');

    // Evita `/build`, usato dagli asset Vite in `public/build`.
    Route::get('consigliati', [BuildController::class, 'index'])->name('builds.index');
    Route::get('consigliati/{build}', [BuildController::class, 'show'])->name('builds.show');

    Route::get('personaggi/nuovo', CharacterWizard::class)->name('characters.create');

    Route::get('personaggi/{character}', [CharacterController::class, 'show'])
        ->name('characters.show');

    Route::get('personaggi/{character}/registro', [CharacterController::class, 'ledger'])
        ->name('characters.ledger');

    Route::get('personaggi/{character}/{sezione}', [CharacterController::class, 'section'])
        ->whereIn('sezione', array_column(
            array_filter(SheetSection::cases(), fn ($s) => $s !== SheetSection::DEFAULT),
            'value',
        ))
        ->name('characters.section');

    Route::prefix('mercato')->name('market.')->group(function () {
        Route::redirect('/', '/mercato/emporio')->name('index');
        Route::get('emporio', [MarketController::class, 'show'])->defaults('sezione', 'market.shop')->name('shop');
        Route::get('annunci', [MarketController::class, 'show'])->defaults('sezione', 'market.listings')->name('listings');
        Route::get('scambi', [MarketController::class, 'show'])->defaults('sezione', 'market.trades')->name('trades');

        Route::get('vigilanza', [SupervisionController::class, 'mine'])->name('supervision');
    });

    Route::get('richieste', [ProposalController::class, 'index'])->name('proposals.index');
    Route::post('richieste/svuota', [ProposalController::class, 'clear'])->name('proposals.clear');
    Route::post('richieste/{change}/archivia', [ProposalController::class, 'archive'])->name('proposals.archive');
    Route::post('richieste/{change}/ripristina', [ProposalController::class, 'restore'])->name('proposals.restore');
    // Deve restare dopo le route specifiche e limita `{sezione}` ai valori dell'enum per evitare collisioni.
    Route::prefix('personaggi/{character}')->name('proposals.')->group(function () {
        Route::get('modifica', [ProposalController::class, 'editForm'])->name('edit');
        Route::post('modifica', [ProposalController::class, 'submitEdit']);

        Route::get('livello', [ProposalController::class, 'levelUpForm'])->name('level-up');
        Route::post('livello', [ProposalController::class, 'submitLevelUp']);

        Route::get('bottino', [ProposalController::class, 'lootForm'])->name('loot');
        Route::post('bottino', [ProposalController::class, 'submitLoot']);

        Route::get('oggetto-magico', [ProposalController::class, 'itemEffectForm'])->name('item-effect');
        Route::post('oggetto-magico', [ProposalController::class, 'submitItemEffect']);
    });
});

// La vetrina dei componenti non viene registrata in produzione.
if (! app()->isProduction()) {
    Route::view('vetrina', 'dev.components')->name('dev.components');
}

require __DIR__.'/auth.php';
