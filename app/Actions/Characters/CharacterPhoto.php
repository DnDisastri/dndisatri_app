<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Models\Character;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Il viaggio di una foto, dal caricamento alla scheda.
 *
 * La foto è l'unica cosa della scheda che sia un **file** e non un valore, e
 * questo cambia tutto: una richiesta in attesa porta con sé del testo, che sta
 * nel database e non dà fastidio a nessuno, mentre una foto in attesa è roba
 * su un disco.
 *
 * Da qui le due destinazioni:
 *
 * - **in attesa** su disco privato, dove nessun indirizzo pubblico la
 *   raggiunge. Una foto che un DM non ha ancora approvato non deve poter
 *   circolare solo perché qualcuno ne indovina l'indirizzo;
 * - **pubblicata** su disco pubblico, e solo quando la richiesta è approvata.
 *
 * Al rifiuto il file si cancella: tenerlo non servirebbe a niente e resterebbe
 * lì per sempre.
 */
final class CharacterPhoto
{
    /** Dove aspettano le foto non ancora approvate. */
    public const PENDING_DIR = 'proposte-foto';

    /**
     * Mette da parte il file caricato e restituisce il percorso da mandare
     * nella richiesta.
     */
    public function store(UploadedFile $file): string
    {
        $name = Str::uuid().'.'.$file->getClientOriginalExtension();

        return $file->storeAs(self::PENDING_DIR, $name, 'local');
    }

    /**
     * Sposta la foto sul disco pubblico e restituisce il nuovo percorso.
     *
     * Restituisce null se il file non c'è più: può succedere se la stessa
     * richiesta viene approvata due volte, o se qualcuno ha ripulito il disco
     * mentre la proposta aspettava in bacheca. In quel caso la scheda tiene la
     * foto che ha già, che è meglio di un riquadro rotto.
     */
    public function publish(Character $character, string $pendingPath): ?string
    {
        if (! Storage::disk('local')->exists($pendingPath)) {
            return null;
        }

        $published = 'personaggi/'.$character->getKey().'/'.basename($pendingPath);

        Storage::disk('public')->put(
            $published,
            Storage::disk('local')->get($pendingPath),
        );

        Storage::disk('local')->delete($pendingPath);

        // La foto di prima non serve più a nessuno: la scheda ne mostra una
        // sola, e i file orfani si accumulano in silenzio.
        $this->deletePublished($character->photo_path);

        return $published;
    }

    /** Butta via una foto che non è mai stata approvata. */
    public function discard(?string $pendingPath): void
    {
        if ($pendingPath !== null && str_starts_with($pendingPath, self::PENDING_DIR.'/')) {
            Storage::disk('local')->delete($pendingPath);
        }
    }

    private function deletePublished(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
