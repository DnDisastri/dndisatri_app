<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le azioni di mercato di un giocatore sotto richiamo, in attesa di un via
 * libera (D13).
 *
 * **Tabella separata da `pending_changes`, deliberatamente.** Le due cose si
 * somigliano — qualcuno chiede, qualcuno decide — ma raccontano storie diverse:
 * una richiesta di gioco dice «vorrei salire di livello», questa dice «vorrei
 * fare questo scambio, controllami». Mescolarle renderebbe la bacheca un posto
 * ambiguo, dove non si capisce più se si sta arbitrando o vigilando.
 *
 * Il `payload` conserva l'intenzione, non il risultato: all'approvazione viene
 * rigiocata attraverso l'azione vera, che rivalida tutto da capo. Fra la
 * richiesta e il via libera il mondo può essere cambiato — l'oggetto venduto,
 * l'oro speso — e in quel caso l'azione fallisce come farebbe normalmente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervised_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Sotto quale richiamo è stata chiesta: serve a raccontare, a
            // richiamo chiuso, quanto è servito il controllo.
            $table->foreignId('warning_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type', 30);

            // L'intenzione: chi, cosa, quanto. Si rigioca all'approvazione.
            $table->json('payload');

            // La stessa cosa in italiano, per chi deve decidere in fretta.
            $table->string('summary');

            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();

            // Obbligatoria quando si rifiuta: il giocatore deve sapere perché.
            $table->text('review_note')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervised_actions');
    }
};
