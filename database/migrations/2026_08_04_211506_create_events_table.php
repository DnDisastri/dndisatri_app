<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gli eventi del gruppo: raduni, one-shot, serate speciali.
 *
 * Non sono le sessioni di gioco (`game_sessions`), che appartengono a una
 * campagna e hanno un recap: un evento è roba di tutto il gruppo e sta nella
 * sezione redazionale insieme alle news.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location')->nullable();

            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // "I prossimi eventi", la query della pagina.
            $table->index(['starts_at', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
