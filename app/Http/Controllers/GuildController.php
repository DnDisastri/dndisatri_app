<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Warning;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuildController extends Controller
{
    /** La Gilda (P13): i vivi, e in fondo i caduti. Le classi servono a scrivere i multiclasse. */
    public function index(Request $request): View
    {
        // Solo per i DM (M16): la ricerca per personaggio o giocatore, e chi è sotto richiamo.
        $conduce = $request->user()->isDm();
        $cerca = $conduce ? trim((string) $request->query('cerca', '')) : '';

        $vivi = Character::alive()->with(['user', 'classes'])->orderBy('name');
        $caduti = Character::fallen()->with(['user', 'classes'])->orderByDesc('died_at');

        if ($cerca !== '') {
            // La chiusura raggruppa l'OR, o si mangerebbe la condizione vivo/caduto.
            $filtro = fn ($q) => $q
                ->where('name', 'like', "%{$cerca}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$cerca}%"));

            $vivi->where($filtro);
            $caduti->where($filtro);
        }

        return view('guild.index', [
            'characters' => $vivi->get(),
            'fallen' => $caduti->get(),
            'cerca' => $cerca,

            'sottoRichiamo' => $conduce
                ? Warning::active()->pluck('user_id')->unique()->all()
                : [],
        ]);
    }

    /** Il memoriale di un caduto (P15b): come è morto e in quale sessione. Su un vivo, 404. */
    public function fallenShow(Character $character): View
    {
        abort_if($character->isAlive(), 404);

        // La campagna serve a dire a quale storia apparteneva la sessione.
        $character->load(['user', 'classes', 'diedInSession.campaign']);

        return view('guild.caduto', ['character' => $character]);
    }
}
