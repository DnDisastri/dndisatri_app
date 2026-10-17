<?php

use Database\Seeders\SpeciesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('species', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('rules_edition', 4)->default('2014');
            $table->json('asi')->nullable();
            $table->decimal('speed', 4, 1)->default(9);
            $table->text('traits')->nullable();
            $table->string('description', 500)->nullable();
            $table->unsignedTinyInteger('free_bonuses')->default(0);
            $table->unsignedTinyInteger('skill_choices')->default(0);
            $table->boolean('grants_feat')->default(false);
            $table->unsignedSmallInteger('position')->default(99);
            $table->timestamps();
        });

        // Le specie prima stavano in config/dnd/species.php: arrivano con la migrazione, così in produzione ci sono subito.
        (new SpeciesSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('species');
    }
};
