<?php

declare(strict_types=1);

use App\Actions\Users\ApproveRegistration;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Campaign;
use App\Models\User;
use App\Notifications\RegistrationApproved;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

// Un account appena registrato resta in attesa finché un admin non lo approva.
it('un admin approva un iscritto in attesa dal pannello', function () {
    $admin = User::factory()->admin()->create();
    $inAttesa = User::factory()->player()->unapproved()->create();

    expect($inAttesa->isApproved())->toBeFalse();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callTableAction('approva', $inAttesa);

    expect($inAttesa->refresh()->isApproved())->toBeTrue();
});

it('all\'approvazione l\'iscritto riceve la conferma anche per email', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $inAttesa = User::factory()->player()->unapproved()->create(['played_before' => false]);

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callTableAction('approva', $inAttesa);

    Notification::assertSentTo(
        $inAttesa,
        RegistrationApproved::class,
        fn (RegistrationApproved $n, array $canali) => in_array('mail', $canali, true),
    );
});

it('un account già approvato non riceve una seconda conferma', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $giocatore = User::factory()->player()->create(['played_before' => true]);

    app(ApproveRegistration::class)->handle($giocatore, $admin);

    Notification::assertNothingSent();
});

it('chi si era iscritto prima della conferma viene approvato senza email', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $vecchioIscritto = User::factory()->player()->unapproved()->create(['played_before' => null]);

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callTableAction('approva', $vecchioIscritto);

    expect($vecchioIscritto->refresh()->isApproved())->toBeTrue();
    Notification::assertNothingSent();
});

it('chi si iscrive dal modulo riceve la conferma quando viene approvato', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();

    $this->post(route('register'), [
        'name' => 'Nuova Iscritta',
        'email' => 'nuova@example.com',
        'password' => 'password-lunga-123',
        'password_confirmation' => 'password-lunga-123',
        'played_before' => '0',
    ]);
    auth()->logout();

    $iscritto = User::where('email', 'nuova@example.com')->firstOrFail();
    app(ApproveRegistration::class)->handle($iscritto, $admin);

    Notification::assertSentTo($iscritto, RegistrationApproved::class);
});

it('solo un admin può approvare', function () {
    $dm = User::factory()->dm()->create();
    $inAttesa = User::factory()->player()->unapproved()->create();

    expect(fn () => app(ApproveRegistration::class)->handle($inAttesa, $dm))
        ->toThrow(RuntimeException::class);

    expect($inAttesa->refresh()->isApproved())->toBeFalse();
});

it('l\'email di conferma porta alla pagina di accesso', function () {
    $html = (new RegistrationApproved)
        ->toMail(User::factory()->player()->create())
        ->render();

    expect((string) $html)->toContain('La tua iscrizione è approvata')
        ->toContain(route('login'));
});

// La nomina dal pannello: l'admin decide, il giocatore non ha chiesto niente.
it('un admin nomina un giocatore dungeon master dal pannello', function () {
    $admin = User::factory()->admin()->create();
    $giocatore = User::factory()->player()->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callTableAction('nominaDm', $giocatore);

    expect($giocatore->refresh()->isDm())->toBeTrue();
});

// I DM stanno nella loro scheda: la revoca si fa da lì.
it('e gli toglie il ruolo riportandolo giocatore', function () {
    $admin = User::factory()->admin()->create();
    $dm = User::factory()->dm()->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->set('activeTab', 'dm')
        ->callTableAction('revocaDm', $dm);

    expect($dm->refresh()->isDm())->toBeFalse();
});

it('a un DM le due azioni non compaiono nemmeno', function () {
    $dm = User::factory()->dm()->create();
    $giocatore = User::factory()->player()->create();

    Livewire::actingAs($dm)
        ->test(ListUsers::class)
        ->assertTableActionHidden('nominaDm', $giocatore);
});

it('la revoca avvisa invece di fallire quando la campagna è ancora aperta', function () {
    $admin = User::factory()->admin()->create();
    $dm = User::factory()->dm()->create();
    Campaign::factory()->runBy($dm)->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->set('activeTab', 'dm')
        ->callTableAction('revocaDm', $dm)
        ->assertNotified();

    expect($dm->refresh()->isDm())->toBeTrue();
});

it('la sezione Utenti resta chiusa ai giocatori', function () {
    $this->actingAs(User::factory()->player()->create())
        ->get(UserResource::getUrl('index'))
        ->assertForbidden();
});

it('il badge del menu Utenti conta gli iscritti in attesa', function () {
    User::factory()->player()->unapproved()->count(2)->create();
    User::factory()->player()->create();

    expect(UserResource::getNavigationBadge())->toBe('2');
});

it('senza iscritti in attesa il badge non compare', function () {
    User::factory()->player()->count(3)->create();

    expect(UserResource::getNavigationBadge())->toBeNull();
});
