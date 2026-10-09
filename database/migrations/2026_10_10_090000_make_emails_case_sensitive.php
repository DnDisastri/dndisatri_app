<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mario@x e mario@x sono due indirizzi diversi: confronto e indice univoco
 * diventano sensibili alle maiuscole. SQLite lo è già.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->collation('utf8mb4_bin');
    }

    public function down(): void
    {
        $this->collation('utf8mb4_unicode_ci');
    }

    private function collation(string $collation): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE {$collation} NOT NULL");
        DB::statement("ALTER TABLE password_reset_tokens MODIFY email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE {$collation} NOT NULL");
    }
};
