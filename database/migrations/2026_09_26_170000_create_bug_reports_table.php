<?php

use App\Enums\BugReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le segnalazioni di problemi mandate dai giocatori e dai DM.
 *
 * `page` e `user_agent` si raccolgono da soli: sono quello che chi segnala non
 * sa dare e chi corregge non riesce a ricostruire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();

            // Chi segnala resta anche se cancella l'account: la segnalazione
            // vale lo stesso, e senza autore non si possono chiedere dettagli.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description');

            $table->string('status')->default(BugReportStatus::Open->value);

            $table->string('page')->nullable();
            $table->string('user_agent')->nullable();

            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('answer')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
