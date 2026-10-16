<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait NormalizesNewlines
{
    /**
     * I browser inviano gli a capo delle textarea come «\r\n», due caratteri, mentre
     * il contatore della pagina ne conta uno: senza questo un testo al limite
     * verrebbe rifiutato. Da chiamare prima di `validate`.
     */
    private function normalizeNewlines(Request $request, string ...$campi): void
    {
        foreach ($campi as $campo) {
            if (is_string($request->input($campo))) {
                $request->merge([$campo => str_replace(["\r\n", "\r"], "\n", $request->input($campo))]);
            }
        }
    }
}
