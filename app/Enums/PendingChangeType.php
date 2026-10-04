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

    /** Un oggetto del giocatore in cambio di un articolo del negozio. */
    case Barter = 'barter';

    public function label(): string
    {
        return match ($this) {
            self::CharacterEdit => 'Modifica scheda',
            self::LevelUp => 'Passaggio di livello',
            self::Loot => 'Bottino',
            self::ItemEffect => 'Oggetto magico',
            self::Barter => 'Baratto',
        };
    }

    /**
     * Bottini e baratti si applicano sul valore corrente, non lo sovrascrivono:
     * non annullano quello che è successo fra la proposta e l'approvazione.
     */
    public function appliesAsDelta(): bool
    {
        return $this === self::Loot || $this === self::Barter;
    }
}
