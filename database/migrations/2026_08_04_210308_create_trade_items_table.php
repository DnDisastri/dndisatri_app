<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gli oggetti coinvolti in uno scambio, da una parte e dall'altra.
 *
 * `direction` dice da che lato sta l'oggetto: `give` è quello che offre chi
 * propone, `want` quello che chiede in cambio.
 *
 * Si salvano nome e categoria e non un riferimento alla riga di inventario:
 * quella riga può cambiare o sparire fra la proposta e l'accettazione, e la
 * verifica di possesso si fa comunque per nome e quantità al momento buono.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trade_id')->constrained()->cascadeOnDelete();

            $table->string('direction', 10);

            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('value')->default(0);
            $table->text('details')->nullable();

            $table->timestamps();

            $table->index(['trade_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_items');
    }
};
