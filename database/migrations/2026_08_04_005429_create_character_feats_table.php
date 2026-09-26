<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I talenti, con il livello a cui sono stati presi e da dove arrivano
 * (un ASI, la specie, una concessione del DM).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_feats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('level')->nullable();

            // 'asi' | 'specie' | 'dm' — da dove arriva il talento.
            $table->string('source', 20)->nullable();

            $table->timestamps();

            $table->index('character_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_feats');
    }
};
