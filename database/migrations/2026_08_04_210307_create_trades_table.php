<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gli scambi diretti fra due personaggi: oggetti e oro da entrambe le parti.
 *
 * A differenza degli annunci, qui **niente esce dall'inventario al momento
 * della proposta**: la disponibilità delle due parti si verifica al momento
 * dell'accettazione, come nella vecchia applicazione (§4.8). Una proposta è
 * un'intenzione, non un impegno.
 *
 * Conseguenza voluta: una proposta può fallire in accettazione perché nel
 * frattempo il proponente ha venduto l'oggetto. È corretto che sia così, e il
 * messaggio d'errore deve dirlo chiaramente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('from_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('to_character_id')->constrained('characters')->cascadeOnDelete();

            // Oro offerto da chi propone, e oro chiesto in cambio.
            $table->unsignedInteger('give_gp')->default(0);
            $table->unsignedInteger('want_gp')->default(0);

            $table->string('status', 20)->default('pending');
            $table->timestamp('resolved_at')->nullable();
            // L'annullamento (D12): si fa una volta sola.
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users');
            $table->text('message')->nullable();

            $table->timestamps();

            // "Le proposte che mi aspettano" e "quelle che ho mandato".
            $table->index(['to_character_id', 'status']);
            $table->index(['from_character_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
