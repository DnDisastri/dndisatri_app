<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La bacheca delle richieste: il cuore del gioco.
 *
 * I giocatori non modificano mai il personaggio direttamente. Propongono, e la
 * richiesta arriva a tutti i DM e a tutti gli admin (decisione D1).
 *
 * Due differenze rispetto alla vecchia applicazione:
 *
 * - si salva il **diff**, non lo snapshot completo. Nella vecchia app
 *   `newData` sovrascriveva tutto: una richiesta proposta lunedì e approvata
 *   giovedì cancellava gli acquisti fatti nel mezzo (§2.3 del piano).
 * - `base_updated_at` registra com'era il personaggio al momento della
 *   proposta, così in approvazione si può avvisare che nel frattempo è
 *   cambiato invece di sovrascrivere in silenzio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_changes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

            $table->string('type', 20);

            // Solo i campi che cambiano: { "gp": 150, "level": 4 }.
            // Si chiama `diff` e non `changes`: quest'ultimo collide con una
            // proprietà interna di Eloquent (`HasAttributes::$changes`).
            $table->json('diff')->nullable();

            // Riassunto leggibile, per passaggi di livello, bottini e oggetti.
            $table->text('summary')->nullable();

            // Solo per il bottino. In approvazione i delta si applicano
            // RILEGGENDO il valore corrente (gp + grant_gp), non sovrascrivendo:
            // serve a non annullare quanto successo nel frattempo.
            $table->integer('grant_gp')->default(0);
            $table->json('grant_items')->nullable();

            $table->timestamp('base_updated_at')->nullable();

            $table->string('status', 20)->default('pending');

            // Chi ha deciso resta tracciato: la bacheca è condivisa e la chiude
            // il primo che arriva, ma dopo si sa chi è stato.
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            // Tolta dalla bacheca senza cancellarla.
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            // La bacheca: le richieste aperte, dalla più vecchia.
            $table->index(['status', 'created_at']);
            $table->index(['character_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_changes');
    }
};
