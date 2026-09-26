<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'inventario. Va normalizzato e non lasciato in JSON, perché ci si fa il
 * mercato: gli oggetti si vendono, si scambiano e si mettono in vendita.
 *
 * L'equipaggiamento è una colonna qui, non quattro chiavi esterne sulla scheda.
 * Nella vecchia applicazione `equipped` conteneva i NOMI degli oggetti: se
 * l'oggetto veniva venduto o rinominato, l'equipaggiamento puntava nel vuoto e
 * la Classe Armatura sbagliava in silenzio (§2.4 del piano).
 *
 * Con `equipped_slot` il problema sparisce da solo: se la riga se ne va, se ne
 * va anche l'equipaggiamento, e l'indice univoco garantisce un oggetto solo per
 * slot senza bisogno di controlli applicativi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedInteger('qty')->default(1);

            // Valore unitario in monete d'oro, per il mercato fra giocatori.
            $table->unsignedInteger('value')->default(0);
            $table->text('details')->nullable();

            // 'weapon' | 'armor' | 'shield', oppure null se riposto.
            $table->string('equipped_slot', 10)->nullable();
            // La sintonia della quinta edizione: se ne tengono fino a tre, e
            // sono la strada degli effetti degli oggetti magici.
            $table->boolean('attuned')->default(false);
            // Messo in vendita fra giocatori.
            $table->boolean('tradeable')->default(false);

            $table->timestamps();

            // Un oggetto solo per slot. In MySQL i NULL non collidono fra loro,
            // quindi gli oggetti non equipaggiati non danno fastidio.
            $table->unique(['character_id', 'equipped_slot']);
            $table->index(['character_id', 'category']);
            $table->index(['character_id', 'attuned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_items');
    }
};
