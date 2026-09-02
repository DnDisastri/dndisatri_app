<?php

use App\Http\Controllers\BuildController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\DmController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GameSessionController;
use App\Http\Controllers\GuildController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PendingChangePhotoController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\SupervisionController;
use App\Livewire\CharacterWizard;
use App\Livewire\Market\Listings;
use App\Livewire\Market\Shop;
use App\Livewire\Market\Trades;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {

    Route::get('bacheca/{change}/foto', [PendingChangePhotoController::class, 'show'])->name('pending-changes.photo');

    Route::get('notifiche', [NotificationController::class, 'index'])->name('notifications.index');
// La rotta fissa deve precedere `{notification}` per non essere interpretata come parametro dinamico.
    Route::post('notifiche/svuota', [NotificationController::class, 'clear'])->name('notifications.clear');
    Route::post('notifiche/{notification}/archivia', [NotificationController::class, 'archive'])->name('notifications.archive');
    Route::post('notifiche/{notification}/ripristina', [NotificationController::class, 'restore'])->name('notifications.restore');

    Route::get('profilo', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profilo', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profilo/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('profilo/richiami', [ProfileController::class, 'warnings'])->name('profile.warnings');

    Route::get('gilda', [GuildController::class, 'index'])->name('guild.index');

    Route::get('regia', [DmController::class, 'home'])->name('dm.home');

    Route::get('regia/serata/{session}/prepara', [DmController::class, 'prepare'])->name('dm.prepare');
// Mantiene compatibili i vecchi link a `/caduti`; il redirect resta temporaneo per evitare cache permanenti.
    Route::redirect('caduti', '/gilda#caduti')->name('guild.fallen');

    Route::get('caduti/{character}', [GuildController::class, 'fallenShow'])->name('fallen.show');

    Route::get('campagne', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('campagne/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');

    Route::get('incarichi', [QuestController::class, 'index'])->name('quests.index');
    Route::get('incarichi/{quest}', [QuestController::class, 'show'])->name('quests.show');

    Route::post('incarichi/{quest}/prenota', [QuestController::class, 'book'])->name('quests.book');
    Route::post('incarichi/{quest}/ritirati', [QuestController::class, 'withdraw'])->name('quests.withdraw');
    Route::post('incarichi/{quest}/serata', [QuestController::class, 'confirmNight'])->name('quests.confirm-night');
    Route::post('incarichi/{quest}/chiama', [QuestController::class, 'promote'])->name('quests.promote');
    Route::post('incarichi/{quest}/concludi', [QuestController::class, 'conclude'])->name('quests.conclude');

    Route::get('serate', [GameSessionController::class, 'index'])->name('sessions.index');
    Route::get('serate/{session}', [GameSessionController::class, 'show'])->name('sessions.show');
    Route::post('serate/{session}/resoconto', [GameSessionController::class, 'writeRecap'])->name('sessions.recap');
    Route::post('serate/{session}/presenze', [GameSessionController::class, 'recordAttendance'])->name('sessions.attendance');


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
            array_filter(App\Enums\SheetSection::cases(), fn ($s) => $s !== App\Enums\SheetSection::DEFAULT),
            'value',
        ))
        ->name('characters.section');

    Route::prefix('mercato')->name('market.')->group(function () {
        Route::redirect('/', '/mercato/emporio')->name('index');
        Route::get('emporio', Shop::class)->name('shop');
        Route::get('annunci', Listings::class)->name('listings');
        Route::get('scambi', Trades::class)->name('trades');

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
