<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gli annunci di vendita fra giocatori.
 *
 * Regola importante presa dalla vecchia applicazione (§4.7): mettendo in
 * vendita, **l'oggetto lascia subito l'inventario** del venditore ed è come se
 * stesse in deposito presso l'annuncio. Se l'annuncio si annulla, torna
 * indietro. Serve a impedire che lo stesso oggetto venga venduto due volte, o
 * venduto e nel frattempo scambiato.
 *
 * Per questo l'annuncio conserva nome, categoria e dettagli dell'oggetto: la
 * riga di inventario da cui proviene non esiste più.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_listings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seller_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('buyer_character_id')->nullable()->constrained('characters')->nullOnDelete();

            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedInteger('qty')->default(1);

            // Prezzo richiesto per l'intero lotto: si compra tutto insieme.
            $table->unsignedInteger('price');

            // Valore unitario di riferimento, per far capire se è un affare.
            $table->unsignedInteger('unit_value')->default(0);

            $table->text('details')->nullable();

            $table->string('status', 20)->default('active');
            $table->timestamp('resolved_at')->nullable();
            // L'annullamento (D12): si fa una volta sola.
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users');

            $table->timestamps();

            // La vetrina: gli annunci aperti, dal più recente.
            $table->index(['status', 'id']);
            $table->index('seller_character_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_listings');
    }
};
