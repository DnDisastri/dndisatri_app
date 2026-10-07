<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Il tipo di una quest.
 *
 * Oggi solo «di campagna». Per tipi senza campagna (boss run, farm)
 * `quests.campaign_id` andrà reso facoltativo.
 */
enum QuestType: string
{
    case Campaign = 'campaign';
    case BossRun = 'boss-run';
    case Farm = 'farm';

    public function label(): string
    {
        return match ($this) {
            self::Campaign => 'Di campagna',
            self::BossRun => 'Boss run',
            self::Farm => 'Da farmare',
        };
    }
}
