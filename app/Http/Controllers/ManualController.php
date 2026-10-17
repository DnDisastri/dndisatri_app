<?php

namespace App\Http\Controllers;

use App\Enums\Condition;
use App\Http\Controllers\Concerns\FocusesCampaign;
use App\Models\ManualPrice;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Il Manuale del DM: listino dell'SRD e consultazione rapida. La percentuale
 * della campagna a fuoco cambia solo questo listino, mai l'Emporio.
 */
class ManualController extends Controller
{
    use FocusesCampaign;

    public function show(Request $request): View
    {
        abort_unless($request->user()->isDm(), 403);

        [$mie, $altre] = $this->campagneDelDm($request->user());
        $campagna = $this->campagnaAFuoco($request->query('campagna'), $mie, $altre);

        // Le categorie nell'ordine in cui sono nate, le voci in ordine alfabetico.
        $listino = ManualPrice::orderBy('id')->get()
            ->groupBy('category')
            ->map(fn ($voci) => $voci
                ->map(fn (ManualPrice $voce) => [
                    'nome' => $voce->name,
                    'base' => $voce->price_cp,
                    'campagna' => $campagna?->adjustedPrice($voce->price_cp) ?? $voce->price_cp,
                ])
                ->sortBy('nome', SORT_NATURAL | SORT_FLAG_CASE)
                ->values());

        $riassunti = config('dnd.reference.conditions');

        return view('dm.manual', [
            'campagna' => $campagna,
            'listino' => $listino,
            'condizioni' => collect(Condition::elenco())->map(fn (string $nome, string $chiave) => [
                'nome' => $nome,
                'testo' => $riassunti[$chiave] ?? '',
            ]),
            'cd' => config('dnd.reference.difficulty'),
            'viaggio' => config('dnd.reference.travel'),
            'stili' => config('dnd.reference.lifestyle'),
        ]);
    }
}
