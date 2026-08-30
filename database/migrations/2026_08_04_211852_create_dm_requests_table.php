<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le richieste per diventare dungeon master.
 *
 * È il requisito n°1 della riscrittura. Nella vecchia applicazione la regola
 * Firestore su `users` lasciava a ogni utente il permesso di aggiornare il
 * proprio documento, campo `role` compreso: bastava la console del browser per
 * promuoversi a DM (§8.1 del brief).
 *
 * Qui il ruolo lo assegna solo il server, approvando una richiesta, e solo un
 * admin può farlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('message')->nullable();

            $table->string('status', 20)->default('pending');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);

            // Una richiesta aperta alla volta per utente: l'indice parziale non
            // esiste in MySQL, quindi il vincolo lo tiene l'azione di dominio.
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_requests');
    }
};
