<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Models\Character;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * La lista degli incantesimi preparati per oggi (D16).
 *
 * Riguarda solo le classi che preparano — Chierico e Druido nei dati di gioco.
 * Le altre conoscono un numero fisso di incantesimi e li hanno sempre pronti.
 *
 * **Non passa dalla bacheca**, come gli slot e i punti ferita: preparare gli
 * incantesimi è quello che si fa al mattino nel gioco, non una modifica al
 * personaggio. Cambia ogni giorno per definizione.
 *
 * L'elenco **sostituisce** il precedente invece di aggiungersi: preparare è
 * scegliere, e scegliere significa anche rinunciare a quello di ieri.
 */
final class PrepareSpells
{
    /** @param  list<string>  $names  gli incantesimi da tenere pronti */
    public function handle(Character $character, array $names): Character
    {
        if (! $character->preparesSpells()) {
            throw new RuntimeException(
                "Un {$character->class} non prepara gli incantesimi: quelli che conosce sono sempre pronti."
            );
        }

        $names = array_values(array_unique($names));
        $limit = $character->preparationLimit();

        if (count($names) > $limit) {
            throw new RuntimeException(
                "Puoi tenerne pronti {$limit}, non ".count($names).'.'
            );
        }

        return DB::transaction(function () use ($character, $names) {
            // I trucchetti restano fuori: non si preparano, e toccarli
            // significherebbe poterli spegnere per sbaglio.
            $spells = $character->spells()->where('level', '>', 0)->get();

            $unknown = array_diff($names, $spells->pluck('name')->all());

            if ($unknown !== []) {
                throw new RuntimeException(
                    'Non conosci: '.implode(', ', $unknown).'.'
                );
            }

            foreach ($spells as $spell) {
                $spell->forceFill(['prepared' => in_array($spell->name, $names, true)])->save();
            }

            return $character->fresh();
        });
    }
}
