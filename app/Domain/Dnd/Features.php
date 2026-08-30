<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

use App\Enums\ActionCost;
use App\Models\Character;
use Illuminate\Support\Collection;

/**
 * Che cosa sa fare un personaggio: i privilegi di classe e di sottoclasse che
 * ha **già preso**, quelli del suo livello e di quelli sotto.
 *
 * Serve alla sezione «Armi» della scheda. La domanda a cui risponde non è
 * «cosa fa un barbaro» ma «cosa posso fare io, adesso, in questo turno» — che
 * è quella che uno si fa al tavolo mentre gli altri aspettano.
 *
 * Multiclasse compreso: ogni classe porta i suoi privilegi fino al livello che
 * il personaggio ha **in quella classe**, che è l'unico conto giusto. Un
 * guerriero 5/ladro 3 ha l'attacco extra e non ha la schivata prodigiosa.
 *
 * I dati stanno in config/dnd/features.php, con le attribuzioni di licenza.
 */
final class Features
{
    /**
     * @return Collection<int, array{
     *     origine: string, livello: int, nome: string, costo: ActionCost,
     *     usi: ?string, testo: string, mio: bool, controllare: bool, daTurno: bool
     * }>
     */
    public static function for(Character $character): Collection
    {
        $privilegi = collect();

        foreach ($character->classLevels() as $classe => $livello) {
            $privilegi = $privilegi->concat(
                self::raccogli(config("dnd.features.classi.{$classe}", []), $classe, $livello)
            );

            foreach (self::sottoclassiDi($character, $classe) as $sottoclasse) {
                $privilegi = $privilegi->concat(
                    self::raccogli(config("dnd.features.sottoclassi.{$sottoclasse}", []), $sottoclasse, $livello)
                );
            }
        }

        return $privilegi->sortBy('livello')->values();
    }

    /**
     * Le sottoclassi di cui non abbiamo scritto i privilegi.
     *
     * Il SRD ne contiene una per classe: le altre stanno nei manuali e vanno
     * riassunte a mano, poche per volta. Finché non lo sono, la scheda lo
     * **dice** invece di far finta di niente — un buco dichiarato è più utile
     * di un silenzio che sembra una risposta.
     *
     * @return list<string>
     */
    public static function sottoclassiSenzaPrivilegi(Character $character): array
    {
        $mancanti = [];

        foreach ($character->classLevels() as $classe => $livello) {
            foreach (self::sottoclassiDi($character, $classe) as $sottoclasse) {
                if (config("dnd.features.sottoclassi.{$sottoclasse}") === null) {
                    $mancanti[] = $sottoclasse;
                }
            }
        }

        return $mancanti;
    }

    /**
     * Raggruppate per costo, nell'ordine in cui si guardano nel proprio turno.
     *
     * Solo i privilegi **da turno**: quelli che al tavolo, in combattimento,
     * uno vuole avere sottomano. Un privilegio sociale o d'esplorazione non è
     * roba di questo schermo — il suo posto è la Storia, e ci si arriva con
     * `da_turno: false` sulla riga del catalogo. Il difetto è «sì», perché nelle
     * classi da mischia quasi ogni passiva serve in combattimento: si marca
     * l'eccezione, non la regola.
     */
    public static function perCosto(Character $character): Collection
    {
        $privilegi = self::for($character)
            ->filter(fn (array $p) => $p['daTurno'])
            ->groupBy(fn (array $p) => $p['costo']->value);

        return collect(ActionCost::ordered())
            ->mapWithKeys(fn (ActionCost $costo) => [$costo->value => $privilegi->get($costo->value, collect())])
            ->reject(fn (Collection $gruppo) => $gruppo->isEmpty());
    }

    /** I privilegi tenuti fuori dal turno (`da_turno: false`): vanno in Storia. */
    public static function fuoriDalTurno(Character $character): Collection
    {
        return self::for($character)->reject(fn (array $p) => $p['daTurno'])->values();
    }

    /**
     * I privilegi di una lista fino a un livello, con l'origine attaccata.
     *
     * L'origine è il nome della classe o della sottoclasse: senza, in un
     * multiclasse non si capisce da dove arriva una capacità, e con due classi
     * che hanno l'attacco extra si vedrebbe due volte la stessa riga senza
     * sapere perché.
     */
    private static function raccogli(array $lista, string $origine, int $livello): Collection
    {
        return collect($lista)
            ->filter(fn (array $p) => $p['livello'] <= $livello)
            ->map(fn (array $p) => [
                'origine' => $origine,
                'livello' => $p['livello'],
                'nome' => $p['nome'],
                'costo' => ActionCost::from($p['costo']),
                'usi' => $p['usi'] ?? null,
                'testo' => $p['testo'],
                // Il nome l'ho tradotto io perché nel SRD italiano non c'è:
                // la scheda lo segnala, così chi al tavolo lo chiama in un
                // altro modo sa che può cambiarlo senza rompere niente.
                'mio' => (bool) ($p['nome_mio'] ?? false),
                // Riassunto scritto a memoria e non ancora ricontrollato sul
                // manuale. Si dice, invece di lasciarlo passare per verificato:
                // al tavolo la differenza fra «è così» e «mi pare» è tutta.
                'controllare' => (bool) ($p['da_controllare'] ?? false),
                // Se sta nel cheat sheet del turno. Il difetto è «sì»: si marca
                // `da_turno: false` solo su ciò che in combattimento non serve.
                'daTurno' => (bool) ($p['da_turno'] ?? true),
            ])
            ->values();
    }

    /**
     * La sottoclasse presa in una certa classe, se c'è.
     *
     * Passa dalle righe di `character_classes` quando ci sono, e ricade sulla
     * colonna della scheda quando no — che è lo stesso salvagente di
     * `classLevels()`, per gli stessi motivi.
     *
     * @return list<string>
     */
    private static function sottoclassiDi(Character $character, string $classe): array
    {
        $righe = $character->relationLoaded('classes') ? $character->classes : $character->classes()->get();

        $dalle = $righe
            ->where('class', $classe)
            ->pluck('subclass')
            ->filter()
            ->all();

        if ($dalle !== []) {
            return array_values($dalle);
        }

        return $character->class === $classe && $character->subclass
            ? [$character->subclass]
            : [];
    }
}
