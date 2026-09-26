<?php

declare(strict_types=1);

namespace App\Enums;

enum PendingChangeType: string
{
    /** Modifica alla scheda proposta dal giocatore. */
    case CharacterEdit = 'character_edit';

    /** Passaggio di livello, con l'eventuale ASI o talento già scelto. */
    case LevelUp = 'level_up';

    /** Bottino di fine sessione: oro e oggetti da aggiungere. */
    case Loot = 'loot';

    /** Oggetto magico che altera una caratteristica. */
    case ItemEffect = 'item_effect';

    public function label(): string
    {
        return match ($this) {
            self::CharacterEdit => 'Modifica scheda',
            self::LevelUp => 'Passaggio di livello',
            self::Loot => 'Bottino',
            self::ItemEffect => 'Oggetto magico',
        };
    }

    /**
     * I bottini si applicano sommando al valore corrente, non sovrascrivendolo:
     * è l'unico tipo che non deve mai annullare quello che è successo fra la
     * proposta e l'approvazione.
     */
    public function appliesAsDelta(): bool
    {
        return $this === self::Loot;
    }
}
