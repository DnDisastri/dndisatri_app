<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Icon;
use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Unica fonte delle voci per barra in basso, menù e barra laterale.
 * Voce: `['nome', 'href', 'icona', 'attiva']`; `href` null = pagina che non c'è ancora, voce spenta.
 */
final class Navigazione
{
    /**
     * Due coppie e un cerchio in mezzo; il DM ha voci sue.
     *
     * @return array{sinistra: list<array>, centro: array, destra: list<array>}
     */
    public static function barra(User $utente): array
    {
        if ($utente->isDm()) {
            return [
                'sinistra' => [
                    self::voce('Campagne', 'campaigns.index', Icon::Campaigns),
                    self::voce('Sessioni', 'sessions.index', Icon::Sessions),
                ],
                'centro' => self::voce('Area Master', 'dm.home', Icon::DmRequests, ['dm.home', 'dm.npcs', 'encounters.*']),
                'destra' => [
                    self::voce('Gilda', 'guild.index', Icon::Guild, ['guild.*', 'fallen.*']),
                    // Link esterno all'app: non si accende mai.
                    ['nome' => 'Pannello', 'href' => '/admin', 'icona' => Icon::Panel, 'attiva' => false],
                ],
            ];
        }

        $eroi = self::voce('Eroi', 'characters.index', Icon::Characters);

        // Si accende sui miei eroi, non sulla scheda di un altro (ci si arriva dalla Gilda).
        $pg = request()->route('character');
        $eroi['attiva'] = $eroi['attiva'] && (! $pg instanceof Character || $pg->user_id === $utente->id);

        return [
            'sinistra' => [
                self::voce('Campagne', 'campaigns.index', Icon::Campaigns),
                self::voce('Libro Mastro', 'ledger.index', Icon::Ledger),
            ],
            'centro' => $eroi,
            'destra' => [
                self::voce('Mercato', 'market.index', Icon::Market),
                self::voce('Eventi', 'events.index', Icon::Events),
            ],
        ];
    }

    /**
     * Le voci secondarie, senza quelle che la barra ha già.
     *
     * @return list<array>
     */
    public static function menu(User $utente): array
    {
        $voci = [
            // In cima: chiedere un posto e seguire le proprie richieste dev'essere a un tocco.
            self::voce('Calendario', 'sessions.index', Icon::Sessions, ['sessions.index', 'sessions.show']),
            self::voce('Le mie prenotazioni', 'sessions.mine', Icon::Bookings, ['sessions.mine']),
            self::voce('Gilda', 'guild.index', Icon::Guild, ['guild.*', 'fallen.*']),
            self::voce('Build consigliate', 'builds.index', Icon::Builds),
            self::voce('Le mie richieste', 'proposals.index', Icon::Proposals),
            self::voce('Il mio profilo', 'profile.edit', Icon::Profile),
            self::voce('FAQs', 'faq.index', Icon::Faq),
            self::voce('Chi siamo', 'about', Icon::General),
            self::voce('Segnala un problema', 'bug-reports.create', Icon::BugReports),
        ];

        if ($utente->isDm()) {
            array_unshift($voci, self::voce('Manuale', 'dm.manual', Icon::Manual, ['dm.manual']));
        }

        if ($utente->isDm() || $utente->isAdmin()) {
            $voci[] = ['nome' => 'Pannello', 'href' => '/admin', 'icona' => Icon::Panel, 'attiva' => false];
        }

        $barra = self::barra($utente);
        $giaInBarra = array_column([...$barra['sinistra'], $barra['centro'], ...$barra['destra']], 'href');

        return array_values(array_filter($voci, fn (array $voce) => ! in_array($voce['href'], $giaInBarra, true)));
    }

    /**
     * Attiva anche nelle pagine di dettaglio: `campaigns.show` accende Campagne.
     *
     * @param  list<string>|null  $pattern
     */
    private static function voce(string $nome, string $rotta, Icon $icona, ?array $pattern = null): array
    {
        $esiste = Route::has($rotta);
        $pattern ??= [Str::contains($rotta, '.') ? Str::before($rotta, '.').'.*' : $rotta];

        return [
            'nome' => $nome,
            'href' => $esiste ? route($rotta) : null,
            'icona' => $icona,
            'attiva' => $esiste && request()->routeIs(...$pattern),
        ];
    }
}
