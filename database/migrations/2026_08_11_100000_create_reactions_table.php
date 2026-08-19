<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le reaction.
 *
 * Una tabella sola e polimorfa: news, eventi, serate e incarichi non hanno
 * niente in comune fra loro, ma «una persona ha applaudito questa cosa» è la
 * stessa frase per tutti, e quattro tabelle uguali sarebbero quattro posti
 * dove correggere lo stesso errore.
 *
 * **L'indice unico è la regola, non un'ottimizzazione.** «Una reaction per
 * persona» scritta solo nel codice PHP regge finché arriva una richiesta per
 * volta: due schede aperte, o un doppio tocco su un telefono lento, e ne
 * entrano due. Qui il database rifiuta la seconda comunque, e il codice può
 * limitarsi a raccontarlo bene.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();

            // Se un utente sparisce, le sue reaction se ne vanno con lui: sono
            // il suo gesto, non un fatto della cosa a cui ha reagito.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->morphs('reactable');

            // La chiave del caso di `App\Enums\Reaction`, non l'icona: il
            // disegno può cambiare senza toccare una riga di dati.
            $table->string('type');

            $table->timestamps();

            $table->unique(['user_id', 'reactable_type', 'reactable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
