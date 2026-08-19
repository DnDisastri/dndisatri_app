<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le campagne sono il perno dei permessi: da qui deriva chi può fare cosa.
 *
 * `dm_id` è la novità rispetto alla vecchia applicazione, dove "sei DM" era un
 * flag globale e qualunque DM poteva agire su qualunque personaggio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('background_path')->nullable();

            // La season di appartenenza: si elenca «le campagne della season X».
            $table->unsignedTinyInteger('season')->default(1);

            // Il capogilda che assegna gli incarichi del tavolo.
            $table->string('quest_giver')->nullable();
            $table->text('quest_giver_description')->nullable();
            $table->string('quest_giver_photo')->nullable();

            // Il DM proprietario del tavolo. Se il suo account sparisce la
            // campagna resta, ma senza responsabile: la riassegna un admin.
            $table->foreignId('dm_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Una campagna conclusa non accetta più quest né richieste.
            // Null = attiva: evita un enum che andrebbe tenuto allineato.
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();

            $table->index(['dm_id', 'ended_at']);
            // «Le campagne della season X, prima le aperte».
            $table->index(['season', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
