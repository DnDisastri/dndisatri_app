<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le mappe.
 *
 * Nella vecchia applicazione l'immagine era un JPEG in base64 dentro il
 * documento, compresso sotto i 900 KB per stare nel limite di 1 MiB di
 * Firestore (§4.10). Qui è un file su disco e la colonna ne tiene il percorso:
 * niente limiti di dimensione, niente compressione forzata, e il database
 * resta leggero.
 *
 * `campaign_id` è opzionale: una mappa può appartenere a un tavolo — e allora
 * la gestisce il suo DM — oppure essere generale, e allora vale per tutti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('image_path');

            $table->timestamps();

            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maps');
    }
};
