<?php

declare(strict_types=1);

use App\Enums\NotificationCategory;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventPublished;
use App\Notifications\InAppNotification;
use App\Notifications\TradeProposed;
use Illuminate\Cache\RateLimiter as RateLimiterFacade;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Notification;

// Le notifiche restano sempre in applicazione; l'email è la parte che il
// giocatore accende e spegne, categoria per categoria, dal profilo.
beforeEach(function () {
    $this->giocatore = User::factory()->player()->create();
    $this->evento = Event::factory()->create();
});

describe('i canali', function () {
    it('di partenza sono applicazione ed email', function () {
        Notification::fake();

        $this->giocatore->notify(new EventPublished($this->evento));

        Notification::assertSentTo(
            $this->giocatore,
            EventPublished::class,
            fn ($notifica, array $canali) => $canali === ['database', 'mail'],
        );
    });

    it('si riducono all\'applicazione quando la categoria è spenta', function () {
        $this->giocatore->forceFill([
            'muted_notifications' => [NotificationCategory::Table->value],
        ])->save();

        Notification::fake();

        $this->giocatore->notify(new EventPublished($this->evento));

        Notification::assertSentTo(
            $this->giocatore,
            EventPublished::class,
            fn ($notifica, array $canali) => $canali === ['database'],
        );
    });

    it('spegnere una categoria non tocca le altre', function () {
        $this->giocatore->forceFill([
            'muted_notifications' => [NotificationCategory::Market->value],
        ])->save();

        expect($this->giocatore->wantsEmailFor(NotificationCategory::Market))->toBeFalse()
            ->and($this->giocatore->wantsEmailFor(NotificationCategory::Table))->toBeTrue()
            ->and($this->giocatore->wantsEmailFor(NotificationCategory::Moderation))->toBeTrue();
    });

    // Si salvano gli spenti, non gli accesi: è ciò che rende indolore
    // aggiungere una categoria in futuro.
    it('una categoria mai vista parte accesa', function () {
        $this->giocatore->forceFill(['muted_notifications' => ['categoria-che-non-esiste-ancora']])->save();

        expect($this->giocatore->wantsEmailFor(NotificationCategory::Table))->toBeTrue();
    });
});

describe('l\'email', function () {
    it('riprende titolo, testo e collegamento della notifica', function () {
        $notifica = new EventPublished($this->evento);

        $contenuto = $notifica->toArray($this->giocatore);
        $messaggio = $notifica->toMail($this->giocatore);

        expect($messaggio->subject)->toBe($contenuto['title'])
            ->and($messaggio->viewData['corpo'])->toBe($contenuto['body'])
            ->and($messaggio->viewData['indirizzo'])->toBe($contenuto['url']);
    });

    // L'unico che percorre la strada vera: canale, impaginazione e consegna.
    it('parte davvero, e arriva all\'indirizzo del giocatore', function () {
        $this->giocatore->notify(new EventPublished($this->evento));

        $spediti = app('mailer')->getSymfonyTransport()->messages();

        expect($spediti)->toHaveCount(1);

        $messaggio = $spediti[0]->getOriginalMessage();

        expect($messaggio->getTo()[0]->getAddress())->toBe($this->giocatore->email)
            ->and($messaggio->getSubject())->toBe('Nuovo evento')
            ->and($messaggio->getHtmlBody())->toContain($this->evento->title);
    });

    it('non parte a chi ha spento la categoria', function () {
        $this->giocatore->forceFill([
            'muted_notifications' => [NotificationCategory::Table->value],
        ])->save();

        $this->giocatore->notify(new EventPublished($this->evento));

        expect(app('mailer')->getSymfonyTransport()->messages())->toBeEmpty()
            ->and($this->giocatore->notifications()->count())->toBe(1);
    });

    it('si impagina senza errori e porta il collegamento', function () {
        $messaggio = (new EventPublished($this->evento))->toMail($this->giocatore);

        $html = view($messaggio->view, $messaggio->viewData)->render();

        expect($html)->toContain($this->giocatore->name)
            ->toContain($this->evento->title)
            ->toContain(route('profile.edit'));
    });
});

// L'hosting accetta 250 email l'ora su tutto l'account: il freno sta sulla
// posta e non deve rallentare la campanella.
describe('il limitatore di invii', function () {
    it('frena la posta', function () {
        $middleware = (new EventPublished($this->evento))->middleware($this->giocatore, 'mail');

        expect($middleware)->toHaveCount(1)
            ->and($middleware[0])->toBeInstanceOf(RateLimited::class);
    });

    it('non frena la notifica in applicazione', function () {
        expect((new EventPublished($this->evento))->middleware($this->giocatore, 'database'))->toBe([]);
    });

    it('insiste abbastanza da attraversare l\'ora del blocco', function () {
        expect((new EventPublished($this->evento))->retryUntil())
            ->toBeGreaterThan(now()->addHour());
    });

    it('è registrato e sta sotto il tetto dell\'hosting', function () {
        $limite = app(RateLimiterFacade::class)->limiter(InAppNotification::LIMITATORE)();

        expect($limite->maxAttempts)->toBeLessThan(250);
    });
});

describe('la pagina del profilo', function () {
    it('mostra un interruttore per categoria', function () {
        $this->actingAs($this->giocatore)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Notifiche email')
            ->assertSee(NotificationCategory::Market->label())
            ->assertSee(NotificationCategory::Moderation->label());
    });

    it('salva come spente le categorie non spuntate', function () {
        $this->actingAs($this->giocatore)
            ->put(route('profile.notifications'), [
                'categorie' => [NotificationCategory::Table->value],
            ])
            ->assertRedirect();

        expect($this->giocatore->fresh()->wantsEmailFor(NotificationCategory::Table))->toBeTrue()
            ->and($this->giocatore->fresh()->wantsEmailFor(NotificationCategory::Market))->toBeFalse();
    });

    it('accetta di spegnerle tutte', function () {
        $this->actingAs($this->giocatore)
            ->put(route('profile.notifications'))
            ->assertRedirect();

        $aggiornato = $this->giocatore->fresh();

        foreach (NotificationCategory::forUser($aggiornato) as $categoria) {
            expect($aggiornato->wantsEmailFor($categoria))->toBeFalse();
        }
    });

    it('mostra «Da approvare» solo a DM e admin', function () {
        $this->actingAs($this->giocatore)
            ->get(route('profile.edit'))
            ->assertDontSee(NotificationCategory::Approvals->label());

        $this->actingAs(User::factory()->dm()->create())
            ->get(route('profile.edit'))
            ->assertSee(NotificationCategory::Approvals->label());
    });

    it('non spegne «Da approvare» a un giocatore che salva: gli servirà da DM', function () {
        $this->actingAs($this->giocatore)
            ->put(route('profile.notifications'))
            ->assertRedirect();

        expect($this->giocatore->fresh()->wantsEmailFor(NotificationCategory::Approvals))->toBeTrue();
    });

    it('lascia a un DM la scelta su «Da approvare»', function () {
        $dm = User::factory()->dm()->create();

        $this->actingAs($dm)
            ->put(route('profile.notifications'), ['categorie' => [NotificationCategory::Table->value]])
            ->assertRedirect();

        expect($dm->fresh()->wantsEmailFor(NotificationCategory::Approvals))->toBeFalse();
    });

    it('rifiuta una categoria inventata', function () {
        $this->actingAs($this->giocatore)
            ->put(route('profile.notifications'), ['categorie' => ['mercato-nero']])
            ->assertSessionHasErrors('categorie.0');
    });
});

it('ogni notifica dichiara la sua categoria', function () {
    $classi = collect(glob(app_path('Notifications/*.php')))
        ->map(fn (string $file) => 'App\\Notifications\\'.basename($file, '.php'))
        ->reject(fn (string $classe) => (new ReflectionClass($classe))->isAbstract());

    expect($classi)->not->toBeEmpty();

    foreach ($classi as $classe) {
        expect((new ReflectionClass($classe))->getMethod('category')->getDeclaringClass()->getName())
            ->toBe($classe, "{$classe} non dichiara la categoria");
    }
});

it('le notifiche del mercato stanno nella categoria del mercato', function () {
    expect((new ReflectionClass(TradeProposed::class))->newInstanceWithoutConstructor()->category())
        ->toBe(NotificationCategory::Market);
});
