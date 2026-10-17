<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Domain\Dnd\Features;
use App\Enums\RestType;
use App\Models\Character;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Gli usi dei privilegi dalla scheda. Come slot e punti ferita non passano dalla
 * bacheca: sono lo stato della sessione, non modifiche al personaggio.
 *
 * Il conto sta in `characters.feature_uses` (id del privilegio => usi spesi) e
 * gli effetti accesi in `active_features`.
 */
final class UseFeature
{
    /** Spende un uso, o i punti della riserva che il privilegio consuma (ki, Incanalare divinità). */
    public function use(Character $character, int $featureId): Character
    {
        return $this->locked($character, function (Character $character, array $privilegi) use ($featureId) {
            $privilegio = $this->trova($privilegi, $featureId);

            if ($privilegio['consuma'] !== null) {
                $riserva = collect($privilegi)->firstWhere('nome', $privilegio['consuma'])
                    ?? throw new RuntimeException("Non hai ancora {$privilegio['consuma']}.");

                $this->spendi($character, $riserva, $privilegio['quanti']);

                return;
            }

            $this->spendi($character, $privilegio, 1);
        });
    }

    /** Rimette un uso: per correggere un clic di troppo, o quando il DM lo concede. */
    public function restore(Character $character, int $featureId): Character
    {
        return $this->locked($character, function (Character $character, array $privilegi) use ($featureId) {
            $privilegio = $this->trova($privilegi, $featureId);
            $usati = $character->feature_uses ?? [];
            $usati[$featureId] = max(0, $privilegio['usati'] - 1);

            $character->forceFill(['feature_uses' => array_filter($usati)])->save();
        });
    }

    /** Accende o spegne un effetto, come l'Ira. Accenderlo consuma un uso, se il privilegio li conta. */
    public function toggle(Character $character, int $featureId): Character
    {
        return $this->locked($character, function (Character $character, array $privilegi) use ($featureId) {
            $privilegio = $this->trova($privilegi, $featureId);

            if (! $privilegio['attivabile']) {
                throw new RuntimeException("{$privilegio['nome']} non si accende.");
            }

            $attivi = $character->active_features ?? [];

            if ($privilegio['attivo']) {
                $character->forceFill(['active_features' => array_values(array_diff($attivi, [$featureId]))])->save();

                return;
            }

            if ($privilegio['massimo'] !== null) {
                $this->spendi($character, $privilegio, 1);
            }

            $character->forceFill(['active_features' => [...$attivi, $featureId]])->save();
        });
    }

    /** Dopo un riposo tornano gli usi che si ripristinano con quel riposo, e gli effetti si spengono. */
    public function afterRest(Character $character, RestType $rest): void
    {
        $usati = $character->feature_uses ?? [];

        foreach (Features::for($character) as $privilegio) {
            if ($privilegio['privilegio']->rechargesOn($rest, $privilegio['livelloClasse'])) {
                unset($usati[$privilegio['id']]);
            }
        }

        $character->forceFill(['feature_uses' => $usati, 'active_features' => []])->save();
    }

    /** @param array<string,mixed> $privilegio */
    private function spendi(Character $character, array $privilegio, int $quanti): void
    {
        if ($privilegio['massimo'] === null) {
            throw new RuntimeException("{$privilegio['nome']} non ha usi da contare.");
        }

        if ($privilegio['massimo'] === Features::UNLIMITED) {
            return;
        }

        $restano = $privilegio['massimo'] - $privilegio['usati'];

        if ($restano < $quanti) {
            throw new RuntimeException($restano === 0
                ? "{$privilegio['nome']}: usi finiti. Tornano col riposo."
                : "{$privilegio['nome']}: ne restano {$restano}, ne servono {$quanti}.");
        }

        $usati = $character->feature_uses ?? [];
        $usati[$privilegio['id']] = $privilegio['usati'] + $quanti;
        $character->forceFill(['feature_uses' => $usati])->save();
    }

    /**
     * @param  list<array<string,mixed>>  $privilegi
     * @return array<string,mixed>
     */
    private function trova(array $privilegi, int $featureId): array
    {
        return collect($privilegi)->firstWhere('id', $featureId)
            ?? throw new RuntimeException('Questo privilegio non è del personaggio.');
    }

    private function locked(Character $character, callable $fai): Character
    {
        return DB::transaction(function () use ($character, $fai) {
            $fresco = Character::whereKey($character->getKey())->lockForUpdate()->firstOrFail();
            $fai($fresco, Features::for($fresco)->all());

            return $fresco;
        });
    }
}
