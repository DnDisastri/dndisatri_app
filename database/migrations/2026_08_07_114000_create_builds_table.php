<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Le build consigliate: personaggi di 1° già pronti da cui prendere spunto.
 *
 * Nella vecchia applicazione erano otto card scritte dentro il codice, e
 * cambiarle voleva dire toccare `catalog.js`. Sono già state convertite in
 * `config/dnd/builds.php` insieme a classi e incantesimi, ma lì restano dati
 * di gioco: immutabili, e uguali per sempre.
 *
 * Qui diventano invece **contenuto che i dungeon master scrivono dal
 * pannello**, e questo è il motivo per cui meritano una tabella: le otto di
 * partenza sono un punto di partenza, non l'elenco definitivo.
 *
 * Le colonne dei passi rispecchiano il **mago della creazione**, non la scheda:
 * `species` e non `race`, e i punteggi *comprati* prima dei bonus di specie,
 * perché è lì che questi dati devono tornare.
 *
 * Le otto vecchie entrano subito, con quello che avevano: classe, sottoclasse
 * e il consiglio a parole. I dettagli del 1° livello sono vuoti e li riempiono
 * i DM — la vecchia applicazione non li aveva mai avuti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builds', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();

            // «Semplice · Robusto»: due parole per la card.
            $table->string('tag')->nullable();

            // Il perché funziona, in breve e per esteso.
            $table->text('summary')->nullable();
            $table->text('body')->nullable();

            // Su quali caratteristiche puntare, a parole: «FOR e COS». Resta
            // una frase e non dei numeri perché è un consiglio che vale anche
            // ai livelli successivi, dove i numeri cambiano.
            $table->string('abilities_advice')->nullable();

            // Come cresce salendo: consiglio scritto, non dati applicabili.
            $table->text('progression')->nullable();

            $table->string('cover_path')->nullable();

            // === I passi della creazione, al 1° livello ===
            $table->string('class');
            $table->string('subclass')->nullable();
            $table->string('species')->nullable();
            $table->string('background')->nullable();

            $table->json('scores')->nullable();
            $table->json('species_choices')->nullable();
            $table->json('skills')->nullable();
            $table->json('equipment')->nullable();
            $table->json('spells')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Stessa regola di news ed eventi: nulla vuol dire bozza, una data
            // futura vuol dire pubblicazione programmata.
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['published_at', 'class']);
        });

        $this->importLegacyBuilds();
    }

    /**
     * Porta dentro le otto della vecchia applicazione.
     *
     * Nascono **pubblicate**: erano già visibili a tutti, e farle ricomparire
     * come bozze vorrebbe dire toglierle dal sito senza che nessuno l'abbia
     * chiesto.
     */
    private function importLegacyBuilds(): void
    {
        $rows = collect(config('dnd.builds', []))->map(fn (array $build) => [
            'title' => $build['name'],
            'slug' => Str::slug($build['name']),
            'tag' => $build['tag'] ?? null,
            'summary' => $build['note'] ?? null,
            'abilities_advice' => $build['abil'] ?? null,
            'class' => $build['cls'],
            'subclass' => $build['sub'] ?? null,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if ($rows !== []) {
            DB::table('builds')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('builds');
    }
};
