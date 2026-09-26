<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le news della gilda: la sezione redazionale, uno dei tre requisiti che
 * hanno motivato la riscrittura.
 *
 * Sostituisce gli «Annunci» della vecchia applicazione, che erano notifiche
 * con `type: 'announcement'` scritte con un `prompt()` del browser — e che
 * chiunque poteva pubblicare, perché il controllo «solo i DM» stava nel client
 * (§1-bis). Qui le pubblicano solo gli admin, e sono contenuti veri con
 * titolo, testo e immagine.
 *
 * Tabella separata dagli eventi di proposito: una news si ordina per data di
 * pubblicazione, un evento per data di svolgimento, e i campi non coincidono.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('cover_path')->nullable();

            // Null = bozza. Una data futura = pubblicazione programmata.
            $table->timestamp('published_at')->nullable();

            // In evidenza in cima alla bacheca.
            $table->boolean('is_pinned')->default(false);

            $table->timestamps();

            $table->index(['published_at', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
