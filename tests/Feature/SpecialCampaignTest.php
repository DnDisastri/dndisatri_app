<?php

declare(strict_types=1);

use App\Enums\SpecialSessionType;
use App\Models\Campaign;
use App\Models\GameSession;
use App\Models\Quest;
use App\Models\User;

describe('le stagioni', function () {
    it('una campagna su più stagioni compare in ognuna', function () {
        $lunga = Campaign::factory()->create(['title' => 'La lunga marcia', 'season' => 2, 'last_season' => 3]);
        Campaign::factory()->create(['title' => 'Solo la prima', 'season' => 1]);

        expect(Campaign::inSeason(2)->pluck('id')->all())->toBe([$lunga->id])
            ->and(Campaign::inSeason(3)->pluck('id')->all())->toBe([$lunga->id])
            ->and(Campaign::inSeason(1)->pluck('title')->all())->toBe(['Solo la prima'])
            ->and($lunga->seasonLabel())->toBe('Season 2-3');
    });

    it('le stagioni del filtro coprono gli intervalli e le sessioni speciali', function () {
        Campaign::factory()->create(['season' => 1, 'last_season' => 3]);
        $speciale = Campaign::factory()->special()->create();
        GameSession::factory()->inCampaign($speciale)->create(['season' => 5, 'special_type' => SpecialSessionType::Opening]);

        expect(Campaign::seasons())->toBe([5, 3, 2, 1]);
    });

    it('la speciale compare in ogni stagione', function () {
        $speciale = Campaign::factory()->special()->create();

        expect(Campaign::inSeason(4)->pluck('id')->all())->toContain($speciale->id);
    });
});

describe('la campagna speciale', function () {
    it('la gestisce qualsiasi DM, ma non si chiude', function () {
        $speciale = Campaign::factory()->special()->create();
        $dm = User::factory()->dm()->create();

        expect($dm->can('update', $speciale))->toBeTrue()
            ->and($dm->can('end', $speciale))->toBeFalse()
            ->and(User::factory()->admin()->create()->can('end', $speciale))->toBeFalse()
            ->and(User::factory()->player()->create()->can('update', $speciale))->toBeFalse();
    });

    it('qualsiasi DM ne crea e modifica le sessioni e le quest', function () {
        $speciale = Campaign::factory()->special()->create();
        $dm = User::factory()->dm()->create();
        $sessione = GameSession::factory()->inCampaign($speciale)->create();
        $quest = Quest::factory()->inCampaign($speciale)->create();

        expect($dm->can('create', [GameSession::class, $speciale]))->toBeTrue()
            ->and($dm->can('update', $sessione))->toBeTrue()
            ->and($dm->can('update', $quest))->toBeTrue();
    });

    it('il titolo di una sessione speciale dice tipo e stagione', function () {
        $sessione = GameSession::factory()->inCampaign(Campaign::factory()->special()->create())->create([
            'title' => 'Il risveglio', 'season' => 3, 'special_type' => SpecialSessionType::Opening, 'number' => null,
        ]);

        expect($sessione->displayTitle())->toBe('Apertura di stagione: Il risveglio')
            ->and($sessione->numberLabel())->toBe('Apertura di stagione · Season 3');
    });

    it('nella pagina dice che la conducono tutti i DM', function () {
        $speciale = Campaign::factory()->special()->create();

        $this->actingAs(User::factory()->player()->create())
            ->get(route('campaigns.show', $speciale))
            ->assertOk()
            ->assertSee('Le conducono tutti i dungeon master')
            ->assertSee('Sessioni speciali');
    });

    it('nell\'Area Master sta fra le campagne di ogni DM', function () {
        Campaign::factory()->special()->create();
        $dm = User::factory()->dm()->create();
        Campaign::factory()->runBy($dm)->create(['title' => 'La mia']);

        $this->actingAs($dm)
            ->get(route('dm.home'))
            ->assertOk()
            ->assertSee('Sessioni speciali')
            ->assertSee('La mia');
    });
});
