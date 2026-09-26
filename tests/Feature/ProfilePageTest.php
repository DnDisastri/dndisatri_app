<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Support\Facades\Hash;


it('mostra nome, email e i propri personaggi', function () {
    $giocatore = User::factory()->player()->create(['name' => 'Margherita', 'email' => 'mar@example.test']);
    Character::factory()->ownedBy($giocatore)->create(['name' => 'Grimm']);

    $this->actingAs($giocatore)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Margherita')
        ->assertSee('mar@example.test')
        ->assertSee('Grimm');
});

it('non mostra i personaggi degli altri', function () {
    Character::factory()->ownedBy(User::factory()->player()->create())->create(['name' => 'Estranea']);

    $this->actingAs(User::factory()->player()->create())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('Estranea');
});

it('chiede l\'accesso a chi non è entrato', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

describe('dati personali', function () {
    it('salva nome ed email', function () {
        $giocatore = User::factory()->player()->create(['name' => 'Vecchio', 'email' => 'vecchio@example.test']);

        $this->actingAs($giocatore)
            ->patch(route('profile.update'), ['name' => 'Nuovo', 'email' => 'nuovo@example.test'])
            ->assertRedirect();

        expect($giocatore->fresh())
            ->name->toBe('Nuovo')
            ->email->toBe('nuovo@example.test');
    });

    it('rifiuta un nome già preso', function () {
        User::factory()->player()->create(['name' => 'Preso']);
        $giocatore = User::factory()->player()->create(['name' => 'Mio']);

        $this->actingAs($giocatore)
            ->patch(route('profile.update'), ['name' => 'Preso', 'email' => $giocatore->email])
            ->assertSessionHasErrors('name');

        expect($giocatore->fresh()->name)->toBe('Mio');
    });

    it('lascia salvare senza aver cambiato niente', function () {
        $giocatore = User::factory()->player()->create();

        $this->actingAs($giocatore)
            ->patch(route('profile.update'), ['name' => $giocatore->name, 'email' => $giocatore->email])
            ->assertSessionHasNoErrors();
    });
});

describe('la password', function () {
    it('la cambia a chi conosce quella attuale', function () {
        $giocatore = User::factory()->player()->create(['password' => Hash::make('quella-vecchia')]);

        $this->actingAs($giocatore)
            ->put(route('profile.password'), [
                'current_password' => 'quella-vecchia',
                'password' => 'quella-nuova',
                'password_confirmation' => 'quella-nuova',
            ])
            ->assertSessionHasNoErrors();

        expect(Hash::check('quella-nuova', $giocatore->fresh()->password))->toBeTrue();
    });
// Cambiare password richiede quella attuale anche con una sessione autenticata, per proteggere le sessioni lasciate aperte.
    it('non la cambia a chi non sa quella attuale', function () {
        $giocatore = User::factory()->player()->create(['password' => Hash::make('quella-vecchia')]);

        $this->actingAs($giocatore)
            ->put(route('profile.password'), [
                'current_password' => 'tirata-a-indovinare',
                'password' => 'quella-nuova',
                'password_confirmation' => 'quella-nuova',
            ])
            ->assertSessionHasErrors('current_password');

        expect(Hash::check('quella-vecchia', $giocatore->fresh()->password))->toBeTrue();
    });

    it('vuole la conferma uguale', function () {
        $giocatore = User::factory()->player()->create(['password' => Hash::make('quella-vecchia')]);

        $this->actingAs($giocatore)
            ->put(route('profile.password'), [
                'current_password' => 'quella-vecchia',
                'password' => 'quella-nuova',
                'password_confirmation' => 'un-altra-cosa',
            ])
            ->assertSessionHasErrors('password');
    });
});

describe('i richiami', function () {
    it('avvisa chi è sotto richiamo adesso', function () {
        $giocatore = User::factory()->player()->create();
        Warning::create([
            'user_id' => $giocatore->getKey(),
            'issued_by' => User::factory()->dm()->create()->getKey(),
            'reason' => 'Scambio in malafede',
        ]);

        $this->actingAs($giocatore)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Sei sotto richiamo');
    });
// La revoca chiude il richiamo ma non ne cancella lo storico.
    it('tiene il conto anche dei richiami tolti', function () {
        $giocatore = User::factory()->player()->create();
        $richiamo = Warning::create([
            'user_id' => $giocatore->getKey(),
            'issued_by' => User::factory()->dm()->create()->getKey(),
            'reason' => 'Chiusa',
        ]);
        $richiamo->forceFill(['lifted_at' => now()])->save();

        $this->actingAs($giocatore)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('I miei richiami')
            ->assertDontSee('Sei sotto richiamo');
    });

    it('non parla di richiami a chi non ne ha avuti', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('I miei richiami');
    });
});
