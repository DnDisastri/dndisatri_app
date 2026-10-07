<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FocusesCampaign;
use App\Models\Campaign;
use App\Models\GameSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * L'Area Master: qui si conduce la sessione, nel Pannello si approva (D20).
 * Prima le proprie campagne, ma ogni DM raggiunge anche quelle degli altri,
 * per coprire un collega.
 */
class DmController extends Controller
{
    use FocusesCampaign;

    public function home(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isDm(), 403);

        [$mie, $altre] = $this->campagneDelDm($user);
        $corrente = $this->campagnaAFuoco($request->query('campagna'), $mie, $altre);
        $corrente?->load('handoverUpdatedBy');

        $sessione = $corrente ? $this->sessioneInMano($corrente) : null;
        $prenotati = $sessione?->isUpcoming() ? $sessione->bookedCharacters() : collect();

        return view('dm.home', [
            'mie' => $mie,
            'altre' => $altre,
            'corrente' => $corrente,
            'sessione' => $sessione,
            // Per una sessione in programma con dei prenotati, gli eroi sono loro.
            'eroi' => $prenotati->isNotEmpty() ? $prenotati : ($corrente ? $corrente->roster() : collect()),
            'eroiPrenotati' => $prenotati->isNotEmpty(),
            'combattimenti' => $corrente ? $corrente->encounters()->open()->with('session')->latest('updated_at')->get() : collect(),
            // La campagna di un altro: la home lo dice, senza impedirlo.
            'sostituto' => $corrente !== null && $corrente->dm_id !== $user->getKey(),
        ]);
    }

    /** La vecchia pagina «Prepara»: appunti e combattimenti ora stanno altrove. */
    public function prepare(Request $request, GameSession $session): RedirectResponse
    {
        abort_unless($request->user()->isDm(), 403);

        return redirect()->route('sessions.show', ['session' => $session, 'da' => 'regia']);
    }

    /** La nota di passaggio: «dove siamo rimasti». La scrive qualsiasi DM. */
    public function handover(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($request->user()->isDm(), 403);

        $dati = $request->validate(['handover_notes' => ['nullable', 'string', 'max:5000']]);

        $campaign->forceFill([
            'handover_notes' => $dati['handover_notes'] ?: null,
            'handover_updated_by' => $request->user()->getKey(),
            'handover_updated_at' => now(),
        ])->save();

        return redirect()->route('dm.home', ['campagna' => $campaign->slug])->with('status', 'Nota di passaggio salvata.');
    }

    /** La prossima da giocare, altrimenti l'ultima giocata. */
    private function sessioneInMano(Campaign $campagna): ?GameSession
    {
        return $campagna->sessions()->upcoming()->first()
            ?? $campagna->sessions()->past()->first();
    }
}
