<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\User;


describe('la season di una campagna', function () {
    it('è la prima se nessuno dice il contrario', function () {
        // La season 1 è il fallback storico per le campagne create prima dell'introduzione delle stagioni.
        expect(Campaign::factory()->create()->fresh()->season)->toBe(1);
    });

    it('si filtra', function () {
        Campaign::factory()->count(2)->create(['season' => 1]);
        Campaign::factory()->create(['season' => 2]);

        expect(Campaign::inSeason(2)->count())->toBe(1)
            ->and(Campaign::inSeason(1)->count())->toBe(2);
    });

    it('e l\'elenco delle season si ricava dalle campagne, dalla più recente', function () {
        Campaign::factory()->create(['season' => 1]);
        Campaign::factory()->create(['season' => 3]);
        Campaign::factory()->create(['season' => 3]);

        expect(Campaign::seasons())->toBe([3, 1]);
    });
});

describe('lo sfondo della campagna', function () {
    it('usa il suo quando c\'è', function () {
        $campagna = Campaign::factory()->create([
            'cover_path' => 'campagne/copertina.jpg',
            'background_path' => 'campagne/sfondi/pergamena.jpg',
        ]);

        expect($campagna->backgroundUrl())->toContain('pergamena.jpg');
    });

    it('ricade sulla copertina quando non ce n\'è uno suo', function () {
        $campagna = Campaign::factory()->create([
            'cover_path' => 'campagne/copertina.jpg',
            'background_path' => null,
        ]);

        expect($campagna->backgroundUrl())->toContain('copertina.jpg');
    });

    it('senza nessuna delle due non inventa niente', function () {
        $campagna = Campaign::factory()->create([
            'cover_path' => null,
            'background_path' => null,
        ]);

        expect($campagna->backgroundUrl())->toBeNull();
    });

    it('la pagina disegna lo sfondo e il velo', function () {
        $campagna = Campaign::factory()->create(['background_path' => 'campagne/sfondi/pergamena.jpg']);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('campaigns.show', $campagna))
            ->assertOk()
            ->assertSee('pergamena.jpg')
            ->assertSee('bg-page/85', false);
    });
});
