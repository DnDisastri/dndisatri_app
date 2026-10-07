<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Character;

/**
 * Le sezioni della scheda, per «cosa stai facendo» e non come la scheda di carta.
 * I PF stanno nell'intestazione. Il valore del caso è il pezzo di indirizzo.
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
     * Se la sezione è solo del giocatore. A chi passa restano Storia e Zaino, e
     * dello Zaino la sola vetrina (la filtra il controller).
     */
    public function isPrivate(): bool
    {
        return ! in_array($this, self::PUBBLICHE, true);
    }

    /** Le sezioni pubbliche, nell'ordine in cui le vede chi passa (Storia prima: si apre per sapere chi è). */
    public const PUBBLICHE = [self::Story, self::Pack];

    /**
     * Le relazioni della sezione; con `preventLazyLoading` una mancanza rompe la pagina.
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
     * Le sezioni per questo lettore (tutte o le pubbliche), senza quelle che il
     * personaggio non ha. La prima si apre entrando.
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
