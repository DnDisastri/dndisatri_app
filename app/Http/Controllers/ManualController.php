<?php

namespace App\Http\Controllers;

use App\Enums\Condition;
use App\Http\Controllers\Concerns\FocusesCampaign;
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

        $listino = collect(config('dnd.prices'))->map(fn (array $voci) => collect($voci)
            ->map(fn (int $base, string $nome) => [
                'nome' => $nome,
                'base' => $base,
                'campagna' => $campagna?->adjustedPrice($base) ?? $base,
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
