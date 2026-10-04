<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\RegistrationAwaitingApproval;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

describe('accesso', function () {
    it('mostra il modulo agli ospiti', function () {
        $this->get(route('login'))->assertOk()->assertSee('Entra');
    });

    it('fa entrare con le credenziali giuste', function () {
        $user = User::factory()->player()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    });
    // Gli errori di accesso restano generici per non rivelare quali indirizzi appartengono a utenti registrati.
    it('rifiuta la password sbagliata senza dire cosa non va', function () {

        $user = User::factory()->player()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'sbagliata',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('frena dopo cinque tentativi falliti', function () {
        $user = User::factory()->player()->create();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'sbagliata']);
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('porta fuori chi esce', function () {
        $this->actingAs(User::factory()->player()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('non fa entrare chi non è ancora approvato, anche con le credenziali giuste', function () {
        $user = User::factory()->player()->unapproved()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('lo fa entrare appena è approvato', function () {
        $user = User::factory()->player()->unapproved()->create();
        $user->forceFill(['approved_at' => now()])->save();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user->fresh());
    });
    // La radice è pubblica e mostra la landing; le altre sezioni dell'app restano protette dall'autenticazione.
    it('a chi non e entrato mostra la presentazione, non il modulo', function () {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tiriamo i Dadi')
            ->assertSee(route('login'));

        $this->get(route('campaigns.index'))->assertRedirect(route('login'));
    });
});

describe('registrazione', function () {
    // La registrazione crea un account non approvato e non apre automaticamente una sessione.
    it('crea l\'account in attesa, e non lo fa entrare', function () {
        $this->post(route('register'), [
            'name' => 'Delia',
            'email' => 'delia@dndisastri.test',
            'password' => 'password-lunga',
            'password_confirmation' => 'password-lunga',
            'played_before' => '0',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertGuest();

        $nuovo = User::where('email', 'delia@dndisastri.test')->first();
        expect($nuovo)->not->toBeNull()
            ->and($nuovo->isApproved())->toBeFalse();
    });

    it('assegna il ruolo di giocatore, e nessun altro', function () {
        $this->post(route('register'), [
            'name' => 'Bruno',
            'email' => 'bruno@dndisastri.test',
            'password' => 'password-lunga',
            'password_confirmation' => 'password-lunga',
            'played_before' => '0',
        ]);

        $user = User::where('email', 'bruno@dndisastri.test')->first();

        expect($user->hasRole(User::ROLE_PLAYER))->toBeTrue()
            ->and($user->isDm())->toBeFalse()
            ->and($user->isAdmin())->toBeFalse();
    });
    // Il badge nel pannello lo vede solo chi ci entra: senza avviso un
    // iscritto resterebbe fuori finché a qualcuno non viene in mente.
    it('avvisa gli amministratori che c\'è qualcuno da approvare', function () {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $dm = User::factory()->dm()->create();
        $giocatore = User::factory()->player()->create();

        $this->post(route('register'), [
            'name' => 'Delia',
            'email' => 'delia@dndisastri.test',
            'password' => 'password-lunga',
            'password_confirmation' => 'password-lunga',
            'played_before' => '0',
        ]);

        Notification::assertSentTo($admin, RegistrationAwaitingApproval::class);
        Notification::assertNotSentTo([$dm, $giocatore], RegistrationAwaitingApproval::class);
    });

    it('nell\'avviso mette nome e indirizzo di chi si è iscritto', function () {
        $admin = User::factory()->admin()->create();

        $this->post(route('register'), [
            'name' => 'Delia',
            'email' => 'delia@dndisastri.test',
            'password' => 'password-lunga',
            'password_confirmation' => 'password-lunga',
            'played_before' => '0',
        ]);

        $avviso = $admin->notifications()->first();

        expect($avviso->data['body'])->toContain('Delia')
            ->toContain('delia@dndisastri.test');
    });

    // I ruoli inviati dal client vengono ignorati: le autorizzazioni superiori possono essere assegnate solo lato server.
    it('non lascia scegliere il ruolo da chi si registra', function () {
        $this->post(route('register'), [
            'name' => 'Furbo',
            'email' => 'furbo@dndisastri.test',
            'password' => 'password-lunga',
            'password_confirmation' => 'password-lunga',
            'played_before' => '0',
            'role' => 'admin',
            'roles' => ['admin'],
        ]);

        expect(User::where('email', 'furbo@dndisastri.test')->first()->isAdmin())->toBeFalse();
    });

    it('rifiuta un nome utente già preso', function () {
        User::factory()->player()->create(['name' => 'Delia']);

        $this->post(route('register'), [
            'name' => 'Delia',
            'email' => 'altra@dndisastri.test',
            'password' => 'password-lunga',
            'password_confirmation' => 'password-lunga',
            'played_before' => '0',
        ])->assertSessionHasErrors('name');
    });

    it('salva le risposte su come ci ha conosciuti e se ha già giocato', function () {
        $admin = User::factory()->admin()->create();

        $this->post(route('register'), [
            'name' => 'Delia',
            'email' => 'delia@dndisastri.test',
            'password' => 'password-lunga',
            'password_confirmation' => 'password-lunga',
            'played_before' => '1',
            'discovery_source' => 'Me ne ha parlato Bruno',
        ])->assertRedirect(route('login'));

        $nuovo = User::where('email', 'delia@dndisastri.test')->first();

        expect($nuovo->played_before)->toBeTrue()
            ->and($nuovo->discovery_source)->toBe('Me ne ha parlato Bruno')
            ->and($admin->notifications()->first()->data['body'])
            ->toContain('Ha già fatto sessioni con noi: sì.')
            ->toContain('«Me ne ha parlato Bruno»');
    });

    it('chiede se ha già giocato con noi, e la provenienza resta facoltativa', function () {
        $this->post(route('register'), [
            'name' => 'Delia',
            'email' => 'delia@dndisastri.test',
            'password' => 'password-lunga',
            'password_confirmation' => 'password-lunga',
        ])->assertSessionHasErrors('played_before')->assertSessionDoesntHaveErrors('discovery_source');

        expect(User::where('email', 'delia@dndisastri.test')->exists())->toBeFalse();
    });

    it('chi si era iscritto prima delle domande resta senza risposta', function () {
        expect(User::playedBeforeLabel(User::factory()->player()->create()->played_before))->toBe('Non indicato');
    });

    it('rifiuta una password troppo corta o non confermata', function () {
        $this->post(route('register'), [
            'name' => 'Corto',
            'email' => 'corto@dndisastri.test',
            'password' => 'breve',
            'password_confirmation' => 'breve',
        ])->assertSessionHasErrors('password');

        $this->post(route('register'), [
            'name' => 'Diverso',
            'email' => 'diverso@dndisastri.test',
            'password' => 'password-lunga',
            'password_confirmation' => 'password-diversa',
        ])->assertSessionHasErrors('password');
    });
});

describe('password dimenticata', function () {
    it('manda il link a chi è registrato', function () {
        Notification::fake();
        $user = User::factory()->player()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    });
    // Anche il recupero password usa una risposta indistinguibile per evitare l'enumerazione degli account.
    it('risponde allo stesso modo per un indirizzo sconosciuto', function () {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nessuno@dndisastri.test'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    });

    it('reimposta la password con un link valido', function () {
        Event::fake([PasswordReset::class]);
        $user = User::factory()->player()->create();
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-nuova',
            'password_confirmation' => 'password-nuova',
        ])->assertRedirect(route('login'));

        Event::assertDispatched(PasswordReset::class);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password-nuova']);
        $this->assertAuthenticatedAs($user->fresh());
    });

    it('rifiuta un link scaduto o falso', function () {
        $user = User::factory()->player()->create();

        $this->post(route('password.store'), [
            'token' => 'inventato',
            'email' => $user->email,
            'password' => 'password-nuova',
            'password_confirmation' => 'password-nuova',
        ])->assertSessionHasErrors('email');
    });
});

describe('il pannello di gestione', function () {
    it('non lo vedono i giocatori', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get('/admin')
            ->assertForbidden();
    });

    it('lo vedono DM e admin', function () {
        $this->actingAs(User::factory()->dm()->create())->get('/admin')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
    });
});
