<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Characters\CharacterPhoto;
use App\Models\PendingChange;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serve la foto proposta di una richiesta in attesa.
 *
 * La foto vive sul disco privato finché un DM non approva: non ha un indirizzo
 * pubblico. Qui la si mostra a chi può esaminare la richiesta, autorizzato
 * dalla policy, così il DM vede cosa sta approvando invece di un percorso.
 */
final class PendingChangePhotoController extends Controller
{
    public function show(PendingChange $change): StreamedResponse|Response
    {
        $this->authorize('view', $change);

        $path = $change->proposedPhotoPath();

        abort_unless(
            $path !== null
                && str_starts_with($path, CharacterPhoto::PENDING_DIR.'/')
                && Storage::disk('local')->exists($path),
            404,
        );

        return Storage::disk('local')->response($path);
    }
}
