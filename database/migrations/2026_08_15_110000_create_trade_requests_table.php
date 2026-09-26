<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le richieste: «mi hanno detto che hai un Amuleto di Salute, te lo scambio?»
 *
 * **Non è uno scambio, ed è per questo che ha una tabella sua.** Uno `trade` è
 * un'offerta che può essere eseguita così com'è: le due parti sono verificate
 * al momento dell'accettazione e la roba si muove. Una richiesta invece chiede
 * una cosa che chi chiede **non può vedere** — perché non è in vetrina — e che
 * quindi è solo un nome scritto a mano, che può essere sbagliato o non esistere
 * affatto. Mettere una cosa del genere fra gli scambi vorrebbe dire tenere una
 * riga ineseguibile in mezzo a righe eseguibili, e prima o poi qualcuno la
 * esegue.
 *
 * Qui **non si muove niente e non c'è niente da vigilare**: una richiesta è una
 * domanda. Quando chi la riceve dice di sì, sceglie dal proprio zaino cosa dare
 * e nasce uno `trade` vero, che passa dal Supervisor come tutti gli altri e che
 * chi aveva chiesto deve confermare. Due conferme, perché fra la domanda e la
 * risposta il proprio zaino può essere cambiato.
 *
 * `offered` è una colonna JSON e non una tabella di righe: è quello che chi
 * chiede *dice* di poter dare, cioè un'intenzione, e diventa un elenco vero
 * soltanto nello scambio che ne nasce.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('from_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('to_character_id')->constrained('characters')->cascadeOnDelete();

            // Quello che si chiede, a parole: è una diceria, non un riferimento.
            $table->string('wanted');

            // Quello che si offre: nomi presi dal proprio zaino, e monete.
            $table->json('offered')->nullable();
            $table->unsignedInteger('offered_gp')->default(0);

            $table->string('status', 20)->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->text('message')->nullable();

            // Lo scambio nato da questa richiesta, quando c'è.
            $table->foreignId('trade_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index(['to_character_id', 'status']);
            $table->index(['from_character_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_requests');
    }
};
