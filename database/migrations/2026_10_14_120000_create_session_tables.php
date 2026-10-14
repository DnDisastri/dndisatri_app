<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I tavoli di una sessione speciale: ognuno con i suoi posti, i suoi DM e un
 * resoconto facoltativo. Ci si prenota alla sessione, poi i DM assegnano il
 * tavolo; la presenza ricorda a quale tavolo si è giocato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedSmallInteger('seats')->default(5);
            $table->unsignedSmallInteger('position')->default(0);
            $table->text('recap')->nullable();
            $table->foreignId('recap_written_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recap_written_at')->nullable();
            $table->timestamps();

            $table->index(['game_session_id', 'position']);
        });

        Schema::create('session_table_dm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['session_table_id', 'user_id']);
        });

        Schema::table('game_session_bookings', function (Blueprint $table) {
            $table->foreignId('session_table_id')->nullable()->after('character_id')->constrained()->nullOnDelete();
        });

        Schema::table('game_session_user', function (Blueprint $table) {
            $table->foreignId('session_table_id')->nullable()->after('character_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_session_user', fn (Blueprint $table) => $table->dropConstrainedForeignId('session_table_id'));
        Schema::table('game_session_bookings', fn (Blueprint $table) => $table->dropConstrainedForeignId('session_table_id'));
        Schema::dropIfExists('session_table_dm');
        Schema::dropIfExists('session_tables');
    }
};
