<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Porta ai testi nuovi i passi del tutorial ancora identici a quelli seminati.
 * Quelli già modificati dal pannello restano come sono.
 */
return new class extends Migration
{
    /** Impronta md5 dei testi seminati in origine, per titolo. */
    private const ORIGINALI = [
        'La barra in basso' => '8404babfff8998df683161bd21e1e7d5',
        'In alto a destra' => '0670100a31cdfce30a90017924afa153',
        'Crea il tuo eroe' => '63b457ef433a3cc78f4f88bc5dada9e0',
        'La scheda del personaggio' => '0e5d61d488c098ee0747697e27d714ab',
        'Trova un incarico' => 'ee93815deb205476b24e61e2b4ecc0aa',
        'Il mercato' => '89e32164cdcb91290f5713509fb5575d',
        'Si gioca!' => '803ab92b46e5814c253150545ed29f47',
    ];

    public function up(): void
    {
        $nuovi = collect(require database_path('seeders/data/tutorial.php'))->keyBy('title');

        foreach (self::ORIGINALI as $titolo => $impronta) {
            $passo = DB::table('tutorial_steps')->where('title', $titolo)->first();

            if ($passo === null || md5((string) $passo->body) !== $impronta || ! $nuovi->has($titolo)) {
                continue;
            }

            DB::table('tutorial_steps')->where('id', $passo->id)->update([
                'body' => $nuovi[$titolo]['body'],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
