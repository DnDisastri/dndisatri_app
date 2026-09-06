<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Character;

/**
 * Le sezioni della scheda: divise per «cosa hai in mano» (si tira per colpire,
 * una prova, si lancia, si apre lo zaino), non per la scheda di carta.
 *
 * I punti ferita non stanno qui: sono nell'intestazione, su tutte le sezioni
 * (prendere danni è la cosa più frequente della serata). Il valore del caso è
 * il pezzo di indirizzo.
 */
enum SheetSection: string
{
    case Turn = 'turno';
    case Checks = 'prove';
    case Magic = 'magia';
    case Pack = 'zaino';
    case Story = 'storia';

    /** La prima, che si apre entrando: `/personaggi/{pg}`. Ha una rotta sua, così i vecchi link reggono. */
    public const DEFAULT = self::Turn;

    public function label(): string
    {
        return match ($this) {
            self::Turn => 'Turno',
            self::Checks => 'Prove',
            self::Magic => 'Magia',
            self::Pack => 'Zaino',
            self::Story => 'Storia',
        };
    }

    /** L'indirizzo di questa sezione per un personaggio. */
    public function url(Character $character): string
    {
        return $this === self::DEFAULT
            ? route('characters.show', $character)
            : route('characters.section', [$character, $this->value]);
    }

    /** Se la sezione ha senso per questo personaggio (Magia solo per chi lancia). */
    public function fitsFor(Character $character): bool
    {
        return $this !== self::Magic || $character->casterType()->castsSpells();
    }

    /**
     * Se la sezione è affare del solo giocatore.
     *
     * La scheda di un altro si riduce togliendo sezioni: quello che non si deve
     * vedere non si disegna e non si carica. A chi passa restano Storia e Zaino,
     * e dello Zaino la sola vetrina (gli oggetti segnati «Scambierei»); il resto
     * e l'oro li filtra il controllore caricando la relazione.
     */
    public function isPrivate(): bool
    {
        return ! in_array($this, self::PUBBLICHE, true);
    }

    /** Le sezioni pubbliche, nell'ordine in cui le vede chi passa (Storia prima: si apre per sapere chi è). */
    public const PUBBLICHE = [self::Story, self::Pack];

    /**
     * Le relazioni che servono a questa sezione. `preventLazyLoading` fa da
     * guardia: una dimenticanza qui rompe la pagina invece di generare una
     * query per riga. `classes` e `itemEffects` ci sono sempre (intestazione e
     * punteggi efficaci).
     *
     * @return list<string>
     */
    public function relations(): array
    {
        $sempre = ['user', 'classes', 'itemEffects', 'items'];

        return match ($this) {
            // Il Turno elenca «cosa puoi fare»: gli servono armi, trucchetti e talenti insieme.
            self::Turn => [...$sempre, 'weapons', 'spells', 'feats'],
            self::Checks => $sempre,
            self::Magic => [...$sempre, 'spells'],
            self::Pack => $sempre,
            self::Story => [...$sempre, 'feats'],
        };
    }

    /**
     * Le sezioni che questo personaggio mostra a questo lettore: `$tutte`
     * sceglie tutte o le sole pubbliche, `fitsFor` toglie quelle che il
     * personaggio non ha. La prima è quella che si apre entrando.
     *
     * @param  bool  $tutte  se il lettore ha diritto alle sezioni private (`viewFullSheet`)
     * @return list<self>
     */
    public static function forCharacter(Character $character, bool $tutte = true): array
    {
        return array_values(array_filter(
            $tutte ? self::cases() : self::PUBBLICHE,
            fn (self $sezione) => $sezione->fitsFor($character),
        ));
    }
}
