<?php

declare(strict_types=1);

use App\Filament\Resources\Faqs\FaqResource;
use App\Models\Faq;
use App\Models\User;

describe('la guida', function () {
    it('la scrivono solo gli admin', function () {
        $faq = Faq::factory()->create();

        expect(User::factory()->admin()->create()->can('create', Faq::class))->toBeTrue()
            ->and(User::factory()->dm()->create()->can('create', Faq::class))->toBeFalse()
            ->and(User::factory()->player()->create()->can('create', Faq::class))->toBeFalse()
            ->and(User::factory()->dm()->create()->can('update', $faq))->toBeFalse();
    });

    it('le bozze non le vedono i giocatori', function () {
        $bozza = Faq::factory()->draft()->create();

        expect(User::factory()->player()->create()->can('view', $bozza))->toBeFalse()
            ->and(User::factory()->admin()->create()->can('view', $bozza))->toBeTrue();
    });

    it('la scope published lascia fuori le bozze', function () {
        Faq::factory()->count(2)->create();
        Faq::factory()->draft()->create();

        expect(Faq::published()->count())->toBe(2);
    });
});

describe('la sezione della guida nel pannello', function () {
    it('si apre agli admin', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(FaqResource::getUrl('index'))
            ->assertOk();
    });

    it('resta chiusa ai DM e ai giocatori', function () {
        $this->actingAs(User::factory()->dm()->create())
            ->get(FaqResource::getUrl('index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->player()->create())
            ->get(FaqResource::getUrl('index'))
            ->assertForbidden();
    });
});
