<?php

use Database\Seeders\BackgroundSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backgrounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rules_edition', 4)->default('2014');
            $table->json('skills');
            $table->unsignedTinyInteger('free_skills')->default(0);
            $table->unsignedSmallInteger('gp')->default(0);
            $table->text('equipment_summary')->nullable();
            $table->text('feature')->nullable();
            $table->json('ability_options')->nullable();
            $table->string('origin_feat')->nullable();
            $table->json('starting_items')->nullable();
            $table->unsignedSmallInteger('position')->default(99);
            $table->timestamps();

            $table->unique(['name', 'rules_edition']);
        });

        // I background prima stavano in config/dnd/backgrounds.php.
        activity()->withoutLogging(fn () => DB::transaction(function () {
            (new BackgroundSeeder)->run();
        }));
    }

    public function down(): void
    {
        Schema::dropIfExists('backgrounds');
    }
};
