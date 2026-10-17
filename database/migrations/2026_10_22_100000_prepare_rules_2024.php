<?php

use Database\Seeders\BackgroundSeeder;
use Database\Seeders\Rules2024Seeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nel 2024 tornano gli stessi nomi (Elfo, Nano, Accolito): unici per edizione.
        Schema::table('species', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['name', 'rules_edition']);
        });

        Schema::table('subraces', function (Blueprint $table) {
            $table->string('rules_edition', 4)->default('2014')->after('race');
            $table->dropUnique(['race', 'name']);
            $table->unique(['race', 'name', 'rules_edition']);
        });

        Schema::table('feats', function (Blueprint $table) {
            $table->string('category', 20)->nullable()->after('rules_edition');
        });

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

        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // I background prima stavano in config/dnd/backgrounds.php; il resto è il materiale 2024 dell'SRD 5.2.1.
        activity()->withoutLogging(fn () => DB::transaction(function () {
            (new BackgroundSeeder)->run();
            (new Rules2024Seeder)->run();
        }));
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('backgrounds');

        Schema::table('feats', fn (Blueprint $table) => $table->dropColumn('category'));
        Schema::table('subraces', function (Blueprint $table) {
            $table->dropUnique(['race', 'name', 'rules_edition']);
            $table->unique(['race', 'name']);
            $table->dropColumn('rules_edition');
        });
        Schema::table('species', function (Blueprint $table) {
            $table->dropUnique(['name', 'rules_edition']);
            $table->unique(['name']);
        });
    }
};
