<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Event;
use App\Models\GameSession;
use App\Models\Post;
use App\Models\Quest;
use Illuminate\Database\Eloquent\Model;

/**
 * Le cose a cui si può reagire.
 *
 * Serve a due mestieri insieme: è l'**elenco chiuso** di cosa accetta le
 * reaction, ed è il pezzo che sta nell'indirizzo — `/reazioni/serata/12`.
 * Tenerli uniti vuol dire che non esiste un tipo raggiungibile da fuori che
 * non sia in questo elenco: un `tryFrom` che fallisce è già un 404.
 *
 * **Non è una `morphMap` di Laravel**, di proposito. Una morph map cambia
 * quello che si scrive in *tutte* le colonne polimorfe dell'applicazione, e
 * qui ce n'è già una che non è nostra: il registro delle attività di Spatie,
 * pieno di righe che dicono `App\Models\Quest` per esteso. Le reaction si
 * portano dietro il nome della classe come fa lui, e questa tabellina resta un
 * fatto degli indirizzi.
 */
enum Reactable: string
{
    case Session = 'serata';
    case Post = 'news';
    case Event = 'evento';
    case Quest = 'incarico';

    /** @return class-string<Model> */
    public function model(): string
    {
        return match ($this) {
            self::Session => GameSession::class,
            self::Post => Post::class,
            self::Event => Event::class,
            self::Quest => Quest::class,
        };
    }

    /**
     * Il verso opposto: da un oggetto al pezzo di indirizzo che lo nomina.
     *
     * Se qualcuno mette `<x-reactions>` su una cosa che non è in elenco, salta
     * fuori qui e subito, invece di produrre un pulsante che risponderebbe 404
     * solo quando qualcuno ci clicca sopra.
     */
    public static function of(Model $model): self
    {
        foreach (self::cases() as $caso) {
            if ($model::class === $caso->model()) {
                return $caso;
            }
        }

        throw new \InvalidArgumentException($model::class.' non accetta reaction.');
    }
}
