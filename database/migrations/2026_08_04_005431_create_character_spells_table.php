<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gli incantesimi conosciuti o preparati.
 *
 * Livello 0 = trucchetto: evita una tabella separata per i cantrip e li rende
 * ordinabili insieme agli altri.
 *
 * La descrizione è nullable: se manca si prende dalla libreria interna
 * (config/dnd/spells.php, chiave normalizzata), e il DM può sovrascriverla.
 * Sono sintesi originali, non testo ufficiale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_spells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->unsignedTinyInteger('level')->default(0);
            // Chierico e Druido preparano ogni giorno; gli altri conoscono.
            $table->boolean('prepared')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();

            // "Gli incantesimi di terzo livello di questo personaggio."
            $table->index(['character_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_spells');
    }
};
