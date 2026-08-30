<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Qualcosa che sa dire con quale disegno si mostra.
 *
 * Esiste per una ragione sola: `<x-icona>` è **l'unico modo di disegnare
 * un'icona nelle pagine**, e vuole un caso di enum e non una stringa, così un
 * nome sbagliato non arriva a schermo. Quando sono nate le reaction quella
 * regola avrebbe avuto due strade davanti: infilare dieci faccine dentro
 * `Icon`, che è l'elenco delle icone *dell'applicazione* e non dei contenuti,
 * oppure lasciare che le reaction si disegnassero da sole scavalcando il
 * componente.
 *
 * Nessuna delle due. `<x-icona>` chiama `blade()` e non gliene importa altro:
 * basta dichiararlo, e due enum diversi passano dalla stessa porta.
 */
interface Icona
{
    /** Il nome per blade-icons: `phosphor-fire-duotone`. */
    public function blade(): string;
}
