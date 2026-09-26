<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Le condizioni del manuale (D&D 5e): lo stato in cui un combattente si trova
 * durante uno scontro — prono, avvelenato, stordito.
 *
 * Sono una **lista fissa** e non testo libero: sono quindici, sono quelle, e un
 * elenco chiuso tiene i nomi coerenti fra un DM e l'altro. Un giorno accanto a
 * ognuna potrà starci cosa comporta; per adesso è il nome che serve al tavolo.
 *
 * Il valore è la chiave inglese (stabile, non cambia se cambia l'etichetta); la
 * parola che si legge la dà `label()`.
 */
enum Condition: string
{
    case Blinded = 'blinded';
    case Charmed = 'charmed';
    case Deafened = 'deafened';
    case Exhaustion = 'exhaustion';
    case Frightened = 'frightened';
    case Grappled = 'grappled';
    case Incapacitated = 'incapacitated';
    case Invisible = 'invisible';
    case Paralyzed = 'paralyzed';
    case Petrified = 'petrified';
    case Poisoned = 'poisoned';
    case Prone = 'prone';
    case Restrained = 'restrained';
    case Stunned = 'stunned';
    case Unconscious = 'unconscious';

    public function label(): string
    {
        return match ($this) {
            self::Blinded => 'Accecato',
            self::Charmed => 'Affascinato',
            self::Deafened => 'Assordato',
            self::Exhaustion => 'Sfinito',
            self::Frightened => 'Spaventato',
            self::Grappled => 'Afferrato',
            self::Incapacitated => 'Incapacitato',
            self::Invisible => 'Invisibile',
            self::Paralyzed => 'Paralizzato',
            self::Petrified => 'Pietrificato',
            self::Poisoned => 'Avvelenato',
            self::Prone => 'Prono',
            self::Restrained => 'Trattenuto',
            self::Stunned => 'Stordito',
            self::Unconscious => 'Privo di sensi',
        };
    }

    /**
     * L'elenco per il selettore: valore => etichetta, in ordine alfabetico
     * italiano. Si ricava dai casi, così non può mai dimenticarne uno.
     *
     * @return array<string, string>
     */
    public static function elenco(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->sort(SORT_LOCALE_STRING)
            ->all();
    }
}
