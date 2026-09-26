<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Immagini finte per il database dimostrativo.
 *
 * Servono a vedere le pagine come saranno: senza, copertine e ritratti
 * ricadrebbero sui segnaposti e metà del disegno non si giudicherebbe.
 *
 * Sono SVG scritti a mano e non file scaricati: nessuna chiamata alla rete
 * mentre si popola il database, nessun binario nella repo, e i colori sono
 * quelli veri della palette — così anche il finto assomiglia al vero.
 */
class Placeholder
{
    /** Le tinte del progetto, in ordine, così due immagini vicine non si somigliano. */
    private const TINTE = [
        ['#2c3e6e', '#e8d5a0'],
        ['#d4423e', '#ffffff'],
        ['#4c63a6', '#f5f5f5'],
        ['#e8d5a0', '#5a4a1e'],
        ['#1a1a1a', '#e8d5a0'],
    ];

    private static int $contatore = 0;

    /**
     * Scrive un'immagine e restituisce il percorso relativo al disco public,
     * quello che va salvato in `cover_path` e simili.
     */
    public static function make(string $cartella, string $testo, int $width = 800, int $height = 450): string
    {
        [$fondo, $inchiostro] = self::TINTE[self::$contatore++ % count(self::TINTE)];

        $iniziali = Str::of($testo)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $parola) => Str::upper(Str::substr($parola, 0, 1)))
            ->join('');

        // Il testo va scappato: un apostrofo in un titolo italiano romperebbe
        // l'XML, e un SVG rotto non si vede senza dire perché.
        $etichetta = htmlspecialchars($testo, ENT_XML1);

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$width} {$height}" width="{$width}" height="{$height}">
            <rect width="{$width}" height="{$height}" fill="{$fondo}"/>
            <text x="50%" y="44%" fill="{$inchiostro}" opacity="0.35"
                  font-family="sans-serif" font-size="{$height}" font-weight="700"
                  text-anchor="middle" dominant-baseline="middle">{$iniziali}</text>
            <text x="50%" y="86%" fill="{$inchiostro}"
                  font-family="sans-serif" font-size="28" text-anchor="middle">{$etichetta}</text>
        </svg>
        SVG;

        $percorso = $cartella.'/'.Str::slug($testo).'-'.Str::random(6).'.svg';

        Storage::disk('public')->put($percorso, $svg);

        return $percorso;
    }
}
