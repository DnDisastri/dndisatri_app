<?php

use Database\Seeders\ClassFeatureSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_features', function (Blueprint $table) {
            $table->id();
            // Di una classe o di una sottoclasse, indicata per nome come fanno i personaggi.
            $table->string('owner_type', 10);
            $table->string('owner');
            $table->unsignedTinyInteger('level');
            $table->string('name');
            $table->string('cost', 10);
            $table->text('text');
            $table->string('uses_text')->nullable();
            $table->boolean('on_turn')->default(true);
            $table->boolean('our_name')->default(false);
            $table->boolean('to_check')->default(false);

            // Gli usi in numeri: quanti, come tornano, cosa consumano.
            $table->string('uses_formula', 20)->nullable();
            $table->unsignedTinyInteger('uses_value')->nullable();
            $table->string('uses_ability', 3)->nullable();
            $table->json('uses_table')->nullable();
            $table->string('recharge', 5)->nullable();
            $table->unsignedTinyInteger('short_rest_from_level')->nullable();
            $table->string('spends')->nullable();
            $table->unsignedTinyInteger('spends_amount')->default(1);
            $table->boolean('can_activate')->default(false);
            $table->text('active_text')->nullable();

            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['owner_type', 'owner']);
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->json('feature_uses')->nullable();
            $table->json('active_features')->nullable();
        });

        // I privilegi prima stavano in config/dnd/features.php: arrivano con la migrazione, così in produzione ci sono subito.
        // Senza registro delle attività: sono 400 righe di partenza, non modifiche di qualcuno.
        // In una transazione sola: riga per riga, su SQLite, ci vogliono minuti.
        activity()->withoutLogging(fn () => DB::transaction(fn () => (new ClassFeatureSeeder)->run()));
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['feature_uses', 'active_features']);
        });

        Schema::dropIfExists('class_features');
    }
};
