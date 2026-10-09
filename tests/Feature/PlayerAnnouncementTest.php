<?php

declare(strict_types=1);

use App\Actions\AnnounceToPlayers;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\GameSessions\Pages\CreateGameSession;
use App\Models\Campaign;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventPublished;
use App\Notifications\GameSessionScheduled;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

describe('destinatari dell\'avviso', function () {
    it('avvisa i giocatori approvati, e nessun altro', function () {
        Notification::fake();

        $giocatore = User::factory()->player()->create();
        $nonApprovato = User::factory()->player()->unapproved()->create();
        $dm = User::factory()->dm()->create();
        $admin = User::factory()->admin()->create();

        $event = Event::factory()->create();

        app(AnnounceToPlayers::class)->handle($event, new EventPublished($event));

        Notification::assertSentTo($giocatore, EventPublished::class);
        Notification::assertNotSentTo([$nonApprovato, $dm, $admin], EventPublished::class);
    });

    it('non avvisa chi crea, e non avvisa due volte', function () {
        Notification::fake();

        $attore = User::factory()->player()->create();
        $altro = User::factory()->player()->create();
        $event = Event::factory()->create();

        app(AnnounceToPlayers::class)->handle($event, new EventPublished($event), $attore);
        app(AnnounceToPlayers::class)->handle($event->fresh(), new EventPublished($event), $attore);

        Notification::assertNotSentTo($attore, EventPublished::class);
        Notification::assertSentToTimes($altro, EventPublished::class, 1);
        expect($event->fresh()->players_notified_at)->not->toBeNull();
    });
});

describe('gli eventi', function () {
    it('un evento pubblicato dal pannello avvisa i giocatori', function () {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $giocatore = User::factory()->player()->create();

        Livewire::actingAs($admin)
            ->test(CreateEvent::class)
            ->fillForm([
                'title' => 'Raduno di primavera',
                'slug' => 'raduno-di-primavera',
                'starts_at' => now()->addWeek(),
                'published_at' => now()->subMinute(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($giocatore, EventPublished::class);
    });

    it('una bozza non avvisa nessuno', function () {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        User::factory()->player()->create();

        Livewire::actingAs($admin)
            ->test(CreateEvent::class)
            ->fillForm([
                'title' => 'Ancora Da Definire',
                'slug' => 'ancora-da-definire',
                'starts_at' => now()->addWeek(),
                'published_at' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Notification::assertNothingSent();
    });

    it('pubblicando una bozza più tardi, avvisa una volta sola', function () {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $giocatore = User::factory()->player()->create();
        $bozza = Event::factory()->create(['published_at' => null]);

        Livewire::actingAs($admin)
            ->test(EditEvent::class, ['record' => $bozza->getRouteKey()])
            ->fillForm(['published_at' => now()->subMinute()])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::actingAs($admin)
            ->test(EditEvent::class, ['record' => $bozza->getRouteKey()])
            ->fillForm(['title' => 'Titolo aggiornato'])
            ->call('save')
            ->assertHasNoFormErrors();

        Notification::assertSentToTimes($giocatore, EventPublished::class, 1);
    });
});

describe('le sessioni', function () {
    it('una nuova sessione futura avvisa i giocatori', function () {
        Notification::fake();

        $dm = User::factory()->dm()->create();
        $giocatore = User::factory()->player()->create();
        $campaign = Campaign::factory()->create(['dm_id' => $dm->id]);

        Livewire::actingAs($dm)
            ->test(CreateGameSession::class)
            ->fillForm([
                'campaign_id' => $campaign->id,
                'played_at' => now()->addWeek(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($giocatore, GameSessionScheduled::class);
    });

    it('una sessione inserita già conclusa non avvisa', function () {
        Notification::fake();

        $dm = User::factory()->dm()->create();
        $giocatore = User::factory()->player()->create();
        $campaign = Campaign::factory()->create(['dm_id' => $dm->id]);

        Livewire::actingAs($dm)
            ->test(CreateGameSession::class)
            ->fillForm([
                'campaign_id' => $campaign->id,
                'played_at' => now()->subWeek(),
                'recap' => "Com'è andata.",
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Notification::assertNotSentTo($giocatore, GameSessionScheduled::class);
    });
});
