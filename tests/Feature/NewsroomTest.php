<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Event;
use App\Models\Map;
use App\Models\Post;
use App\Models\User;

describe('le news', function () {
    it('le pubblicano solo gli admin', function () {
        $post = Post::factory()->create();

        expect(User::factory()->admin()->create()->can('create', Post::class))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('create', Post::class))->toBeFalse()
            ->and(User::factory()->player()->create()->can('create', Post::class))->toBeFalse()
            ->and(User::factory()->dm()->create()->can('update', $post))->toBeFalse();
    });

    it('le bozze non le vedono i giocatori', function () {
        $draft = Post::factory()->draft()->create();

        expect($draft->isDraft())->toBeTrue()
            ->and($draft->isPublished())->toBeFalse()
            ->and(User::factory()->player()->create()->can('view', $draft))->toBeFalse()
            ->and(User::factory()->admin()->create()->can('view', $draft))->toBeTrue();
    });

    it('una pubblicazione programmata resta invisibile finché non scatta', function () {
        $scheduled = Post::factory()->scheduled()->create();

        expect($scheduled->isPublished())->toBeFalse()
            ->and(User::factory()->player()->create()->can('view', $scheduled))->toBeFalse()
            ->and(Post::published()->count())->toBe(0);
    });

    it('l\'elenco mostra prima quelle in evidenza, poi le più recenti', function () {
        $old = Post::factory()->create(['published_at' => now()->subMonth()]);
        $recent = Post::factory()->create(['published_at' => now()->subHour()]);
        $pinned = Post::factory()->pinned()->create(['published_at' => now()->subYear()]);
        Post::factory()->draft()->create();

        expect(Post::published()->pluck('id')->all())->toBe([$pinned->id, $recent->id, $old->id]);
    });
});

describe('gli eventi', function () {
    it('sono una cosa diversa dalle sessioni di gioco', function () {
        $event = Event::factory()->create();

        expect($event->isUpcoming())->toBeTrue()
            ->and(Event::upcoming()->count())->toBe(1);
    });

    it('separano i prossimi dai passati', function () {
        $next = Event::factory()->create(['starts_at' => now()->addDays(3)]);
        $done = Event::factory()->past()->create();

        expect(Event::upcoming()->pluck('id')->all())->toBe([$next->id])
            ->and(Event::past()->pluck('id')->all())->toBe([$done->id]);
    });

    it('li gestiscono solo gli admin', function () {
        expect(User::factory()->admin()->create()->can('create', Event::class))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('create', Event::class))->toBeFalse();
    });
});

describe('le mappe', function () {
    it('le vedono tutti', function () {
        $map = Map::factory()->create();

        expect(User::factory()->player()->create()->can('view', $map))->toBeTrue();
    });

    it('le carica chi conduce, non i giocatori', function () {
        expect(User::factory()->dm()->create()->can('create', Map::class))->toBeTrue()
            ->and(User::factory()->admin()->create()->can('create', Map::class))->toBeTrue()
            ->and(User::factory()->player()->create()->can('create', Map::class))->toBeFalse();
    });

    it('una mappa di un tavolo la gestisce il suo DM', function () {
        $owner = User::factory()->dm()->create();
        $map = Map::factory()->forCampaign(Campaign::factory()->runBy($owner)->create())->create();

        expect($map->load('campaign'))->isGeneral()->toBeFalse()
            ->and($owner->can('update', $map))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('update', $map->load('campaign')))->toBeFalse()
            ->and(User::factory()->admin()->create()->can('update', $map->load('campaign')))->toBeTrue();
    });

    it('una mappa generale la gestisce qualsiasi DM', function () {
        $map = Map::factory()->create();

        expect($map->isGeneral())->toBeTrue()
            ->and(User::factory()->dm()->create()->can('update', $map))->toBeTrue();
    });
// Le mappe legate a una campagna sopravvivono alla sua cancellazione e diventano risorse generali.
    it('sopravvivono alla cancellazione della campagna, diventando generali', function () {
        $campaign = Campaign::factory()->create();
        $map = Map::factory()->forCampaign($campaign)->create();

        $campaign->delete();

        expect($map->fresh())->not->toBeNull()
            ->and($map->fresh()->campaign_id)->toBeNull();
    });

    it('espongono un percorso su disco, non l\'immagine nel database', function () {
        // Il database conserva solo il percorso del file; l'immagine resta nello storage.
        $map = Map::factory()->create(['image_path' => 'maps/torre-nera.jpg']);

        expect($map->image_path)->toBe('maps/torre-nera.jpg')
            ->and($map->url())->toContain('maps/torre-nera.jpg');
    });
});
