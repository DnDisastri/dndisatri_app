<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Il tipo di una quest.
 *
 * Oggi ce n'è una sola — **di campagna**, legata a una storia e a un tavolo —
 * ma la label esiste già per quando ne arriveranno altre che a una campagna non
 * appartengono: una **boss run**, una **da farmare**. Definirle qui adesso vuol
 * dire che introdurle domani è aggiungere la logica, non inventare il concetto.
 *
 * Attenzione, quando arriverà il momento: `quests.campaign_id` è obbligatorio,
 * e i tipi diversi da campagna dovranno renderlo facoltativo.
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
