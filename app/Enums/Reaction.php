<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Icona;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use ToneGabes\Filament\Icons\Enums\Weight;

/**
 * Le reaction: l'applauso a una cosa che qualcuno ha scritto o fatto.
 *
 * **Questo è l'unico posto dove sta l'elenco.** Aggiungerne una vuol dire
 * scrivere un caso qui, e compare da sola su tutte le pagine che le accettano.
 *
 * L'**ordine dei casi è l'ordine sullo schermo**: si riordina spostando le
 * righe, non toccando le viste.
 *
 * Il valore salvato nel database è una chiave nostra — `fire`, `heart` — e non
 * il nome del disegno né l'icona: cambiare disegno a una reaction non tocca i
 * dati, che è la stessa ragione per cui `Icon` è fatto così. **Togliere** un
 * caso invece è un'altra faccenda: le righe che lo usano restano, e vanno
 * decise prima di cancellare la riga.
 */
enum Reaction: string implements Icona
{
    case Dice = 'dice';
    case Smile = 'smile';
    case Party = 'party';
    case Melting = 'melting';
    case Sad = 'sad';
    case Up = 'up';
    case Down = 'down';
    case Heart = 'heart';
    case Clap = 'clap';
    case Fire = 'fire';

    /**
     * Cosa vuol dire, a parole.
     *
     * Non è decorazione: dieci icone monocrome in fila si somigliano, e questa
     * è l'etichetta che il browser legge ad alta voce e che compare tenendo
     * premuto. Senza, una reaction è un disegno e basta.
     */
    public function label(): string
    {
        return match ($this) {
            self::Dice => 'Tiro epico',
            self::Smile => 'Mi fa sorridere',
            self::Party => 'Da festeggiare',
            self::Melting => 'Non ce la faccio',
            self::Sad => 'Che tristezza',
            self::Up => 'Mi piace',
            self::Down => 'Non mi piace',
            self::Heart => 'Bellissimo',
            self::Clap => 'Applausi',
            self::Fire => 'Grande',
        };
    }

    public function phosphor(): Phosphor
    {
        return match ($this) {
            self::Dice => Phosphor::DiceSix,
            self::Smile => Phosphor::Smiley,
            self::Party => Phosphor::Confetti,
            self::Melting => Phosphor::SmileyMelting,
            self::Sad => Phosphor::SmileySad,
            self::Up => Phosphor::ThumbsUp,
            self::Down => Phosphor::ThumbsDown,
            self::Heart => Phosphor::Heart,
            self::Clap => Phosphor::HandsClapping,
            self::Fire => Phosphor::Fire,
        };
    }

    /**
     * Lo stesso peso delle icone dell'applicazione, e per la stessa ragione:
     * duotone è un colore solo a due intensità, quindi una reaction prende il
     * colore del tema invece di portarsi dietro il suo.
     */
    public function blade(): string
    {
        return $this->phosphor()->getLabel().'-'.Weight::Duotone->value;
    }
}
