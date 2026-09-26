<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I preferiti dell'emporio.
 *
 * **Sono del personaggio, non del giocatore**, ed è una scelta e non una
 * comodità dello schema: il chierico segna le pozioni di cura, il ladro le
 * corde e i grimaldelli, e sono due liste che non hanno niente da dirsi. Un
 * elenco unico a nome del giocatore metterebbe davanti al barbaro le pergamene
 * del mago, cioè esattamente il rumore che i preferiti servono a togliere.
 *
 * Ne segue che un personaggio che cade si porta dietro la sua lista, che è
 * giusto così: era la sua.
 *
 * Vale solo per l'emporio. Un annuncio fra giocatori è un oggetto solo, che
 * dopo la vendita non esiste più: metterlo fra i preferiti vorrebbe dire
 * tenersi il segnalibro di una pagina strappata.
 *
 * L'indice unico è la garanzia vera: `toggle()` legge e poi scrive, e due dita
 * impazienti sulla stessa stella arriverebbero altrimenti a due righe uguali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_market_item', function (Blueprint $table) {
            $table->id();

            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_item_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['character_id', 'market_item_id']);
            $table->index('market_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_market_item');
    }
};
