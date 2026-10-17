<?php

use Database\Seeders\GameClassSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('rules_edition', 4)->default('2014');
            $table->unsignedTinyInteger('hit_die');
            $table->json('saves');
            $table->unsignedTinyInteger('skill_count')->default(2);
            $table->json('skill_choices');
            $table->text('proficiencies')->nullable();
            $table->text('equipment_summary')->nullable();

            $table->string('caster_type', 10)->default('none');
            $table->string('spell_ability', 3)->nullable();
            $table->unsignedTinyInteger('cantrips_at_1')->default(0);
            $table->unsignedTinyInteger('spells_at_1')->nullable();
            $table->boolean('prepares')->default(false);
            $table->unsignedTinyInteger('prepare_from_level')->default(1);
            $table->boolean('prepare_half_level')->default(false);
            $table->boolean('swaps_spells')->default(false);
            $table->boolean('copies_scrolls')->default(false);
            $table->json('spell_list')->nullable();

            $table->unsignedTinyInteger('subclass_level')->default(3);
            $table->json('multiclass_requirements')->nullable();
            $table->unsignedTinyInteger('multiclass_skill_count')->default(0);
            $table->json('multiclass_skill_choices')->nullable();
            $table->json('starting_equipment')->nullable();

            $table->unsignedSmallInteger('position')->default(99);
            $table->timestamps();
        });

        // Le classi prima stavano in più file sotto config/dnd: arrivano con la migrazione, così in produzione ci sono subito.
        (new GameClassSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('game_classes');
    }
};
