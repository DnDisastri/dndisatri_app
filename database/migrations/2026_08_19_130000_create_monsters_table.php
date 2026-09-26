<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Il bestiario (tappa C del tracker): il repertorio dei mostri.
 *
 * Vive nella scrivania (Filament), come le build: è materiale che il DM prepara
 * con calma e riusa. Dal tracker di combattimento lo si pesca, e il mostro
 * scelto viene copiato nella serata (i suoi PF calano lì, non nel bestiario).
 *
 * `attacks` è una lista corta di colpi (nome, bonus, danni); `traits` è il
 * testo libero per il resto dello statblock — tratti, resistenze, azioni
 * speciali — quello che si legge nel modale esteso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monsters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('hp');
            $table->unsignedTinyInteger('ac');
            $table->string('speed')->nullable();
            $table->json('attacks')->nullable();
            $table->text('traits')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monsters');
    }
};
