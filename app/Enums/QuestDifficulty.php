<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Icona;
use App\Domain\Dnd\AdventurerRank;
use Filament\Support\Contracts\ScalableIcon;
use Filament\Support\Enums\IconSize;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use ToneGabes\Filament\Icons\Enums\Weight;

/**
 * I valori restano in italiano perché sono quelli scritti nei dati della
 * vecchia applicazione: la migrazione li importa così come sono.
 */
enum QuestDifficulty: string implements Icona, ScalableIcon
{
    case Facile = 'Facile';
    case Media = 'Media';
    case Difficile = 'Difficile';
    case Epica = 'Epica';

    public function label(): string
    {
        return $this->value;
    }

    /** Classe CSS del pallino colorato, come nella vecchia interfaccia. */
    public function color(): string
    {
        return match ($this) {
            self::Facile => 'success',
            self::Media => 'warning',
            self::Difficile => 'danger',
            self::Epica => 'epic',
        };
    }

    /**
     * I due gradi d'avventuriero a cui la difficoltà è tarata: da chi la può
     * affrontare a chi la domina. Le fasce si accavallano di un gradino, perché
     * una quest non è per un livello solo ma per un tratto della scalata.
     *
     * @return array{AdventurerRank, AdventurerRank}
     */
    public function ranks(): array
    {
        return match ($this) {
            self::Facile => [AdventurerRank::Novizio, AdventurerRank::Apprendista],
            self::Media => [AdventurerRank::Apprendista, AdventurerRank::Professionista],
            self::Difficile => [AdventurerRank::Professionista, AdventurerRank::Maestro],
            self::Epica => [AdventurerRank::Maestro, AdventurerRank::Leggendario],
        };
    }

    /** La fascia di livelli consigliata, per la legenda. */
    public function suggestedLevels(): string
    {
        return match ($this) {
            self::Facile => '1–4',
            self::Media => '3–8',
            self::Difficile => '5–12',
            self::Epica => '9+',
        };
    }

    /** Cosa aspettarsi, in una riga, per chi non conosce ancora la scala. */
    public function description(): string
    {
        return match ($this) {
            self::Facile => 'Per chi comincia: rischi contenuti, buona per i primi personaggi.',
            self::Media => 'Il passo normale di una campagna: serve un gruppo affiatato.',
            self::Difficile => 'Scontri seri e scelte pesanti: meglio arrivarci preparati.',
            self::Epica => 'Roba da leggenda: i più forti, e non è detto che tornino tutti.',
        };
    }

    /** Le tacche del segnale: una in più a ogni gradino. */
    public function phosphor(): Phosphor
    {
        return match ($this) {
            self::Facile => Phosphor::CellSignalLow,
            self::Media => Phosphor::CellSignalMedium,
            self::Difficile => Phosphor::CellSignalHigh,
            self::Epica => Phosphor::CellSignalFull,
        };
    }

    /** Duotone come le altre icone: le tacche mancanti restano accennate. */
    public function blade(): string
    {
        return $this->phosphor()->getLabel().'-'.Weight::Duotone->value;
    }

    public function getIconForSize(IconSize $size): string
    {
        return $this->blade();
    }
}
