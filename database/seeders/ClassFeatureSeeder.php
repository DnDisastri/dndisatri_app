<?php

namespace Database\Seeders;

use App\Models\ClassFeature;
use Illuminate\Database\Seeder;

/**
 * I privilegi di classi e sottoclassi da `seeders/data/class_features.php`.
 *
 * Gli usi lì sono scritti a parole: qui si ricavano i numeri dalle frasi
 * ricorrenti («Una volta; torna col riposo breve o lungo», «Tante volte quanto
 * il tuo bonus di competenza»), e i privilegi principali hanno i loro valori
 * scritti a mano. Quello che non si riconosce resta solo testo, e si completa
 * dal pannello. Non riscrive i privilegi che ci sono già.
 */
class ClassFeatureSeeder extends Seeder
{
    /** I privilegi con numeri che le frasi non dicono: tabelle per livello, riserve, attivazioni. */
    private const A_MANO = [
        'Barbaro|Ira' => [
            'uses_formula' => 'table', 'uses_table' => [['level' => 1, 'count' => 2], ['level' => 3, 'count' => 3], ['level' => 6, 'count' => 4], ['level' => 12, 'count' => 5], ['level' => 17, 'count' => 6], ['level' => 20, 'count' => 0]], 'recharge' => 'long',
            'can_activate' => true,
            'active_text' => 'Vantaggio alle prove e ai tiri salvezza su Forza, bonus ai danni in mischia con la Forza (+2, +3 dal 9º, +4 dal 16º), resistenza a danni contundenti, perforanti e taglienti. Niente incantesimi né concentrazione.',
        ],
        'Bardo|Ispirazione bardica' => ['uses_formula' => 'ability', 'uses_ability' => 'cha', 'recharge' => 'long', 'short_rest_from_level' => 5],
        'Chierico|Incanalare divinità' => ['uses_formula' => 'table', 'uses_table' => [['level' => 2, 'count' => 1], ['level' => 6, 'count' => 2], ['level' => 18, 'count' => 3]], 'recharge' => 'short'],
        'Chierico|Intervento divino' => ['uses_formula' => null],
        'Guerriero|Azione impetuosa' => ['uses_formula' => 'table', 'uses_table' => [['level' => 2, 'count' => 1], ['level' => 17, 'count' => 2]], 'recharge' => 'short'],
        'Guerriero|Indomabile' => ['uses_formula' => 'table', 'uses_table' => [['level' => 9, 'count' => 1], ['level' => 13, 'count' => 2], ['level' => 17, 'count' => 3]], 'recharge' => 'long'],
        'Mago|Recupero arcano' => ['uses_formula' => 'fixed', 'uses_value' => 1, 'recharge' => 'long'],
        'Monaco|Punti ki' => ['uses_formula' => 'class_level', 'recharge' => 'short'],
        'Paladino|Percezione divina' => ['uses_formula' => 'ability_plus_one', 'uses_ability' => 'cha', 'recharge' => 'long'],
        'Paladino|Imposizione delle mani' => ['uses_formula' => 'class_level_x5', 'recharge' => 'long'],
        'Stregone|Fonte di magia' => ['uses_formula' => 'class_level', 'recharge' => 'long'],
        'Stregone|Ripristino stregonesco' => ['uses_formula' => 'fixed', 'uses_value' => 1, 'recharge' => 'long'],
        'Druido|Forma selvatica' => ['can_activate' => true, 'active_text' => 'Hai la forma di una bestia: i suoi punti ferita, la sua velocità e le sue statistiche fisiche. Mantieni le tue caratteristiche mentali.'],
    ];

    /** Le riserve che altri privilegi consumano, per nome della frase. */
    private const RISERVE = [
        'incanalare divinità' => 'Incanalare divinità',
        'ki' => 'Punti ki',
        'stregoneria' => 'Fonte di magia',
    ];

    public function run(): void
    {
        $dati = require database_path('seeders/data/class_features.php');

        foreach (['class' => 'classi', 'subclass' => 'sottoclassi'] as $tipo => $chiave) {
            foreach ($dati[$chiave] as $proprietario => $privilegi) {
                foreach (array_values($privilegi) as $posizione => $p) {
                    ClassFeature::firstOrCreate(
                        ['owner_type' => $tipo, 'owner' => $proprietario, 'name' => $p['nome'], 'level' => $p['livello']],
                        [
                            'cost' => $p['costo'],
                            'text' => $p['testo'],
                            'uses_text' => $p['usi'] ?? null,
                            'on_turn' => $p['da_turno'] ?? true,
                            'our_name' => $p['nome_mio'] ?? false,
                            'to_check' => $p['da_controllare'] ?? false,
                            'position' => $posizione,
                            ...self::usi($p['usi'] ?? null),
                            ...(self::A_MANO["{$proprietario}|{$p['nome']}"] ?? []),
                        ],
                    );
                }
            }
        }
    }

    /**
     * I numeri ricavati dalla frase degli usi.
     *
     * @return array<string,mixed>
     */
    public static function usi(?string $frase): array
    {
        if ($frase === null) {
            return [];
        }

        $f = mb_strtolower($frase);

        if (str_starts_with($f, 'consuma un uso di incanalare divinità')) {
            return ['spends' => self::RISERVE['incanalare divinità'], 'spends_amount' => 1];
        }

        if (preg_match('/^costa (\d+) punt[oi] (ki|stregoneria)/u', $f, $m) === 1) {
            return ['spends' => self::RISERVE[$m[2]], 'spends_amount' => (int) $m[1]];
        }

        // «Una volta per incantesimo», «uno per incantesimo»: non è un contatore solo.
        if (str_contains($f, 'per incantesimo')) {
            return [];
        }

        $ritorno = match (true) {
            str_contains($f, 'riposo breve') || str_contains($f, 'breve o lungo') || preg_match('/per riposo(?! lungo)/u', $f) === 1 => 'short',
            str_contains($f, 'riposo lungo') || str_contains($f, 'al giorno') => 'long',
            default => null,
        };

        $caratteristiche = ['forza' => 'str', 'destrezza' => 'dex', 'costituzione' => 'con', 'intelligenza' => 'int', 'saggezza' => 'wis', 'carisma' => 'cha'];

        $quanti = match (true) {
            str_contains($f, 'metà del tuo bonus di competenza') => ['uses_formula' => 'half_proficiency'],
            str_contains($f, 'tante volte quanto il tuo bonus di competenza') => ['uses_formula' => 'proficiency'],
            preg_match('/tante volte quanto il tuo modificatore di (\w+)/u', $f, $m) === 1 && isset($caratteristiche[$m[1]]) => ['uses_formula' => 'ability', 'uses_ability' => $caratteristiche[$m[1]]],
            preg_match('/^(una|due|tre) volt[ae]/u', $f, $m) === 1 => ['uses_formula' => 'fixed', 'uses_value' => ['una' => 1, 'due' => 2, 'tre' => 3][$m[1]]],
            default => null,
        };

        return $quanti === null || $ritorno === null ? [] : [...$quanti, 'recharge' => $ritorno];
    }
}
