<?php

declare(strict_types=1);

use App\Enums\QuestType;
use App\Models\Quest;
use App\Models\User;


it('nasce «di campagna» per default', function () {
    $quest = Quest::factory()->create();

    expect($quest->type)->toBe(QuestType::Campaign)
        ->and($quest->isCampaign())->toBeTrue();
});
// Il tipo predefinito resta quello di campagna, ma l'enum supporta già categorie future.
it('sa distinguere i tipi che verranno', function () {
    $boss = Quest::factory()->make(['type' => QuestType::BossRun]);

    expect($boss->isCampaign())->toBeFalse()
        ->and($boss->type->label())->toBe('Boss run');

    expect(QuestType::Farm->label())->toBe('Da farmare')
        ->and(QuestType::Campaign->label())->toBe('Di campagna');
});

it('mostra la label sulla pagina della quest', function () {
    $quest = Quest::factory()->create();

    $this->actingAs(User::factory()->player()->create())
        ->get(route('quests.show', $quest))
        ->assertOk()
        ->assertSee('Di campagna');
});

it('mostra la label sulla card nella lista delle quest', function () {
    Quest::factory()->create(['title' => 'La carovana perduta']);

    $this->actingAs(User::factory()->player()->create())
        ->get(route('quests.index'))
        ->assertOk()
        ->assertSee('La carovana perduta')
        ->assertSee('Di campagna');
});
