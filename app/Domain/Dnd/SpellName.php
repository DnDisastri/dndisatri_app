<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use Illuminate\Support\Str;

final class SpellName
{
    /**
     * Normalizza il nome di un incantesimo per il confronto: minuscole,
     * accenti rimossi, solo caratteri alfanumerici.
     *
     * Serve a far combaciare "Palla di Fuoco" con le varianti di scrittura che
     * i giocatori inseriscono a mano ("palla di fuoco", "Palla Di Fuoco").
     */
    public static function normalize(?string $name): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii((string) $name)));
    }

    /**
     * Descrizione dalla libreria interna, o null se l'incantesimo non c'è.
     * Le descrizioni sono sintesi originali, non testo ufficiale.
     */
    public static function description(?string $name): ?string
    {
        static $index = null;

        if ($index === null) {
            $index = [];

            foreach (config('dnd.spells.library', []) as $spell => $description) {
                $index[self::normalize($spell)] = $description;
            }
        }

        return $index[self::normalize($name)] ?? null;
    }
}
