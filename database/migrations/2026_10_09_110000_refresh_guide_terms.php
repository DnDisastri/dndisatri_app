<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Porta tutorial e FAQ ai nomi dell'app (quest, sessione, Emporio, dungeon master).
 * Tocca solo le righe ancora identiche a un testo seminato: quelle riscritte dal pannello restano.
 */
return new class extends Migration
{
    /** Titolo di oggi => [titolo nel seed, impronte md5 dei testi seminati prima]. */
    private const TUTORIAL = [
        'Crea il tuo eroe' => ['Crea il tuo eroe', ['63b457ef433a3cc78f4f88bc5dada9e0', 'a6267e6bb0af7f968df0f661fa201cf0']],
        'Trova un incarico' => ['Trova una quest', ['ee93815deb205476b24e61e2b4ecc0aa', 'ff14ac689cca17778beb5cc79a40e1a2']],
        'Il mercato' => ['Il mercato', ['89e32164cdcb91290f5713509fb5575d', '8647ce42ac0c08319949bbeba8e10ad2']],
    ];

    /** Domanda di oggi => [posizione nel seed, impronta md5 della risposta seminata]. */
    private const FAQ = [
        'Da dove comincio?' => [1, '45446b932efd38dfd554dcd23a9704be'],
        'Come creo un personaggio?' => [2, '4f55e88930383f77f2209f7fdadbfc42'],
        'Posso cambiare la scheda dopo?' => [4, 'aedc39bdc976b5f55956800d1278c188'],
        "Cos'è un incarico?" => [5, 'aacfe0f9410649b9f035e0cd8f41cd1b'],
        'Come mi prenoto?' => [6, '01ca19e47ad7a45c974435c997f52477'],
        'Dove vedo quando si gioca?' => [7, 'ab65e4631b5f6b504cca9e0c90c1a1a6'],
        "Cos'è il resoconto?" => [8, 'cb691b643e1829d0da95a93e6d33e16f'],
        "Cos'è una campagna?" => [9, '4927f59fb89fe0422a00961384d17852'],
        'Come funziona uno scambio?' => [11, '4d20d55885d357242a89585e5475c342'],
    ];

    public function up(): void
    {
        $passi = collect(require database_path('seeders/data/tutorial.php'))->keyBy('title');

        foreach (self::TUTORIAL as $titolo => [$nuovoTitolo, $impronte]) {
            $passo = DB::table('tutorial_steps')->where('title', $titolo)->first();

            if ($passo === null || ! in_array(md5((string) $passo->body), $impronte, true) || ! $passi->has($nuovoTitolo)) {
                continue;
            }

            DB::table('tutorial_steps')->where('id', $passo->id)->update([
                'title' => $nuovoTitolo,
                'body' => $passi[$nuovoTitolo]['body'],
                'updated_at' => now(),
            ]);
        }

        $voci = require database_path('seeders/data/faq.php');

        foreach (self::FAQ as $domanda => [$posizione, $impronta]) {
            $faq = DB::table('faqs')->where('question', $domanda)->first();

            if ($faq === null || md5((string) $faq->answer) !== $impronta || ! isset($voci[$posizione])) {
                continue;
            }

            DB::table('faqs')->where('id', $faq->id)->update([
                'category' => $voci[$posizione]['category'],
                'question' => $voci[$posizione]['question'],
                'answer' => $voci[$posizione]['answer'],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
