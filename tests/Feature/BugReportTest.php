<?php

declare(strict_types=1);

use App\Actions\Support\CloseBugReport;
use App\Enums\BugReportStatus;
use App\Enums\NotificationCategory;
use App\Filament\Resources\BugReports\BugReportResource;
use App\Filament\Resources\BugReports\Pages\ListBugReports;
use App\Filament\Resources\BugReports\Pages\ViewBugReport;
use App\Models\BugReport;
use App\Models\User;
use App\Notifications\BugReportClosed;
use App\Notifications\BugReportFiled;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->giocatore = User::factory()->player()->create(['name' => 'Grimm']);
});

describe('la segnalazione', function () {
    it('si manda dal modulo e avvisa gli amministratori', function () {
        Notification::fake();

        $dm = User::factory()->dm()->create();

        $this->actingAs($this->giocatore)
            ->post(route('bug-reports.store'), [
                'title' => 'Il pulsante per vendere non fa niente',
                'description' => 'Apro il mercato, premo Vendi e non succede nulla.',
                'page' => 'https://dndisastri.test/mercato',
            ])
            ->assertRedirect('https://dndisastri.test/mercato')
            ->assertSessionHas('status');

        $segnalazione = BugReport::sole();

        expect($segnalazione->user_id)->toBe($this->giocatore->id)
            ->and($segnalazione->status)->toBe(BugReportStatus::Open);

        Notification::assertSentTo($this->admin, BugReportFiled::class);
        Notification::assertNotSentTo([$dm, $this->giocatore], BugReportFiled::class);
    });

    // È quello che l'utente non sa dare e chi corregge non può ricostruire.
    it('si porta dietro pagina e browser senza chiederli', function () {
        $this->actingAs($this->giocatore)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Android 14; Mobile)')
            ->post(route('bug-reports.store'), [
                'title' => 'Qualcosa non va',
                'description' => 'Succede quando apro la scheda del personaggio.',
                'page' => 'https://dndisastri.test/eroi/1',
            ]);

        $segnalazione = BugReport::sole();

        expect($segnalazione->page)->toBe('https://dndisastri.test/eroi/1')
            ->and($segnalazione->user_agent)->toContain('Android');
    });

    it('vuole un racconto, non due parole', function () {
        $this->actingAs($this->giocatore)
            ->post(route('bug-reports.store'), ['title' => 'Rotto', 'description' => 'boh'])
            ->assertSessionHasErrors(['description']);

        expect(BugReport::count())->toBe(0);
    });

    it('resta chiusa a chi non ha fatto l\'accesso', function () {
        $this->post(route('bug-reports.store'), [
            'title' => 'Il pulsante per vendere non fa niente',
            'description' => 'Apro il mercato, premo Vendi e non succede nulla.',
        ])->assertRedirect(route('login'));
    });

    it('la voce compare nel menù', function () {
        $this->actingAs($this->giocatore)
            ->get(route('home'))
            ->assertSee('Segnala un problema');
    });
});

describe('la chiusura', function () {
    it('avvisa chi aveva segnalato, con la risposta', function () {
        Notification::fake();

        $segnalazione = BugReport::factory()->from($this->giocatore)->create();

        app(CloseBugReport::class)->handle(
            $segnalazione,
            $this->admin,
            BugReportStatus::Fixed,
            'Sistemato, ora il pulsante funziona.',
        );

        expect($segnalazione->fresh()->status)->toBe(BugReportStatus::Fixed)
            ->and($segnalazione->fresh()->closed_by)->toBe($this->admin->id);

        Notification::assertSentTo($this->giocatore, BugReportClosed::class);
    });

    it('distingue «risolta» da «non è un errore»', function () {
        $segnalazione = BugReport::factory()->from($this->giocatore)->create();

        app(CloseBugReport::class)->handle($segnalazione, $this->admin, BugReportStatus::NotABug);

        $avviso = $this->giocatore->notifications()->first();

        expect($avviso->data['title'])->toBe('Funziona così');
    });

    it('la fa solo un amministratore', function () {
        $segnalazione = BugReport::factory()->create();
        $dm = User::factory()->dm()->create();

        expect(fn () => app(CloseBugReport::class)->handle($segnalazione, $dm, BugReportStatus::Fixed))
            ->toThrow(RuntimeException::class);
    });

    it('non si richiude una già chiusa', function () {
        $segnalazione = BugReport::factory()->closed()->create();

        expect(fn () => app(CloseBugReport::class)->handle($segnalazione, $this->admin, BugReportStatus::Fixed))
            ->toThrow(RuntimeException::class);
    });

    it('non accetta uno stato che non chiude niente', function () {
        $segnalazione = BugReport::factory()->create();

        expect(fn () => app(CloseBugReport::class)->handle($segnalazione, $this->admin, BugReportStatus::InProgress))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('il pannello', function () {
    it('conta le aperte nel badge del menù', function () {
        BugReport::factory()->count(2)->create();
        BugReport::factory()->closed()->create();

        expect(BugReportResource::getNavigationBadge())->toBe('2');
    });

    it('resta chiuso ai DM', function () {
        $this->actingAs(User::factory()->dm()->create())
            ->get(BugReportResource::getUrl('index'))
            ->assertForbidden();
    });

    it('chiude una segnalazione dalla sua scheda', function () {
        $segnalazione = BugReport::factory()->from($this->giocatore)->create();

        Livewire::actingAs($this->admin)
            ->test(ViewBugReport::class, ['record' => $segnalazione->getKey()])
            ->callAction('risolvi', ['answer' => 'Sistemato.']);

        expect($segnalazione->fresh()->status)->toBe(BugReportStatus::Fixed);
    });

    it('la scheda «Da vedere» mostra le aperte e nasconde le chiuse', function () {
        $aperta = BugReport::factory()->create();
        $chiusa = BugReport::factory()->closed()->create();

        Livewire::actingAs($this->admin)
            ->test(ListBugReports::class)
            ->assertCanSeeTableRecords([$aperta])
            ->assertCanNotSeeTableRecords([$chiusa]);
    });
});

// «Segnalazioni» racconta al giocatore le sue; quella da leggere è un lavoro per l'admin.
it('avvisa l\'admin nella categoria «Da approvare»', function () {
    $report = BugReport::factory()->create();

    expect((new BugReportFiled($report))->category())->toBe(NotificationCategory::Approvals);
});

// Le preferenze salvano gli spenti, quindi una categoria nuova parte accesa
// per chi aveva già scelto: è la prima volta che succede davvero.
it('la categoria nuova è accesa per chi aveva già salvato le preferenze', function () {
    $this->giocatore->forceFill([
        'muted_notifications' => [NotificationCategory::Market->value],
    ])->save();

    expect($this->giocatore->wantsEmailFor(NotificationCategory::Reports))->toBeTrue();
});
