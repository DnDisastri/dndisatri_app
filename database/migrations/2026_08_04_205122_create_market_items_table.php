<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Il catalogo del negozio della gilda. Lo gestiscono solo gli admin (D1).
 *
 * Scorte: nella vecchia applicazione `stock: null` significava "infinite", una
 * convenzione implicita che il brief chiedeva di sciogliere (§4.6). Qui c'è una
 * colonna esplicita `is_unlimited`: così un articolo esaurito (`stock = 0`) non
 * si confonde con uno sempre disponibile, e nessuno deve ricordarsi la regola.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_items', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedInteger('price');

            $table->boolean('is_unlimited')->default(false);
            $table->unsignedInteger('stock')->default(0);

            $table->text('details')->nullable();

            $table->timestamps();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_items');
    }
};
