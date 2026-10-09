<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FocusesCampaign;
use App\Models\Encounter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** I combattimenti di una campagna, nell'Area Master: solo DM. */
class EncounterController extends Controller
{
    use FocusesCampaign;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Encounter::class);

        [$mie, $altre] = $this->campagneDelDm($request->user());
        $campagna = $this->campagnaAFuoco($request->query('campagna'), $mie, $altre);

        $combattimenti = $campagna?->encounters()->with('session')->latest('updated_at')->get() ?? collect();

        return view('dm.encounters.index', [
            'campagna' => $campagna,
            'aperti' => $combattimenti->reject->isEnded()->values(),
            'conclusi' => $combattimenti->filter->isEnded()->values(),
            'sessioni' => $campagna?->sessions()->orderByDesc('played_at')->get() ?? collect(),
            'sessioneScelta' => $request->integer('serata') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Encounter::class);

        $dati = $request->validate([
            'campaign_id' => ['required', Rule::exists('campaigns', 'id')->whereNull('ended_at')],
            'title' => ['required', 'string', 'max:120'],
            'game_session_id' => ['nullable', Rule::exists('game_sessions', 'id')->where('campaign_id', $request->integer('campaign_id'))],
        ]);

        $encounter = new Encounter($dati);
        $encounter->forceFill(['created_by' => $request->user()->getKey()])->save();

        return redirect()->route('encounters.show', $encounter);
    }

    public function show(Encounter $encounter): View
    {
        $this->authorize('view', $encounter);

        $encounter->load(['campaign', 'session']);

        return view('dm.encounters.show', [
            'encounter' => $encounter,
            'sessioni' => $encounter->campaign->sessions()->orderByDesc('played_at')->get(),
        ]);
    }

    /** Titolo e sessione: il collegamento si può aggiungere anche dopo, come storico. */
    public function update(Request $request, Encounter $encounter): RedirectResponse
    {
        $this->authorize('update', $encounter);

        $dati = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'game_session_id' => ['nullable', Rule::exists('game_sessions', 'id')->where('campaign_id', $encounter->campaign_id)],
        ]);

        $encounter->update($dati);

        return back()->with('status', 'Combattimento aggiornato.');
    }

    public function destroy(Encounter $encounter): RedirectResponse
    {
        $this->authorize('delete', $encounter);

        $campagna = $encounter->campaign;
        $encounter->delete();

        return redirect()->route('encounters.index', ['campagna' => $campagna->slug])->with('status', 'Combattimento eliminato.');
    }
}
