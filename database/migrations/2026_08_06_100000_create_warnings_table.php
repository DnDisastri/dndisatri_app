<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I richiami (decisione D13).
 *
 * Stanno sull'**utente**, non sul personaggio: chi ha più personaggi non si
 * libera di un richiamo cambiando scheda.
 *
 * Non si cancellano mai, si tolgono: `lifted_at` chiude il periodo e lascia la
 * riga dov'è. È da queste righe che si ricavano le due cose che il gruppo vuole
 * poter vedere — quanti richiami ha avuto una persona, e quanto tempo ha
 * passato sotto controllo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warnings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->constrained('users');

            // Perché è stato dato. Obbligatorio: un richiamo senza motivo non
            // si può né contestare né togliere con cognizione di causa.
            $table->text('reason');

            // Null finché il richiamo è attivo. La durata è la differenza fra
            // creazione e questo momento, quindi non serve salvarla.
            $table->timestamp('lifted_at')->nullable();
            $table->foreignId('lifted_by')->nullable()->constrained('users');
            $table->text('lift_note')->nullable();

            $table->timestamps();

            // «Questa persona è sotto richiamo adesso?», la domanda che viene
            // fatta a ogni azione di mercato.
            $table->index(['user_id', 'lifted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warnings');
    }
};
