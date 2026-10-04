<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le monete: la borsa diventa quattro pile, prezzi e valori passano in rame (x100).
 *
 * Le borse esistenti non si convertono: chi aveva 120 mo ha 120 monete d'oro.
 * Il Registro vecchio diventa una serie di movimenti d'oro.
 */
return new class extends Migration
{
    /** Tabella => [colonna vecchia => colonna nuova], tutte moltiplicate per 100. */
    private const VALUES = [
        'market_items' => ['price' => 'price_cp'],
        'market_listings' => ['price' => 'price_cp', 'unit_value' => 'unit_value_cp'],
        'trades' => ['give_gp' => 'give_cp', 'want_gp' => 'want_cp'],
        'trade_requests' => ['offered_gp' => 'offered_cp'],
        'character_items' => ['value' => 'value_cp'],
        'trade_items' => ['value' => 'value_cp'],
    ];

    /** Chiavi del payload delle azioni sotto richiamo, anche quelle in attesa di via libera. */
    private const PAYLOAD = ['give_gp' => 'give_cp', 'want_gp' => 'want_cp', 'price' => 'price_cp'];

    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->unsignedInteger('pp')->default(0)->after('gp');
            $table->unsignedInteger('sp')->default(0)->after('pp');
            $table->unsignedInteger('cp')->default(0)->after('sp');
        });

        foreach (self::VALUES as $table => $columns) {
            foreach ($columns as $old => $new) {
                Schema::table($table, fn (Blueprint $t) => $t->renameColumn($old, $new));
                DB::table($table)->update([$new => DB::raw("{$new} * 100")]);
            }
        }

        $this->ledger();
        $this->pendingChanges();
        $this->quests();
        $this->supervisedActions();
    }

    private function ledger(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->bigInteger('cp_delta')->default(0)->after('action');
            $table->json('coins_delta')->nullable()->after('cp_delta');
            $table->json('coins_after')->nullable()->after('coins_delta');
        });

        DB::table('ledger_entries')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('ledger_entries')->where('id', $row->id)->update([
                    'cp_delta' => $row->gp_delta * 100,
                    'coins_delta' => $row->gp_delta ? json_encode(['gp' => $row->gp_delta]) : null,
                    'coins_after' => $row->gp_after === null ? null : json_encode(['gp' => $row->gp_after]),
                ]);
            }
        });

        Schema::table('ledger_entries', fn (Blueprint $t) => $t->dropColumn('gp_delta'));
        Schema::table('ledger_entries', fn (Blueprint $t) => $t->dropColumn('gp_after'));
    }

    private function pendingChanges(): void
    {
        Schema::table('pending_changes', fn (Blueprint $t) => $t->json('grant_coins')->nullable()->after('summary'));

        DB::table('pending_changes')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $items = json_decode($row->grant_items ?? 'null', true);

                if (is_array($items)) {
                    $items = array_map(function (array $item) {
                        $item['value_cp'] = (int) ($item['value'] ?? 0) * 100;
                        unset($item['value']);

                        return $item;
                    }, $items);
                }

                DB::table('pending_changes')->where('id', $row->id)->update([
                    'grant_coins' => $row->grant_gp ? json_encode(['gp' => $row->grant_gp]) : null,
                    'grant_items' => is_array($items) ? json_encode($items) : $row->grant_items,
                ]);
            }
        });

        Schema::table('pending_changes', fn (Blueprint $t) => $t->dropColumn('grant_gp'));
    }

    private function quests(): void
    {
        Schema::table('quests', fn (Blueprint $t) => $t->json('reward_coins')->nullable()->after('rewards'));

        DB::table('quests')->whereNotNull('reward_gold')->where('reward_gold', '>', 0)->orderBy('id')
            ->each(fn ($row) => DB::table('quests')->where('id', $row->id)
                ->update(['reward_coins' => json_encode(['gp' => $row->reward_gold])]));

        Schema::table('quests', fn (Blueprint $t) => $t->dropColumn('reward_gold'));
    }

    private function supervisedActions(): void
    {
        DB::table('supervised_actions')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $payload = json_decode($row->payload ?? 'null', true);

                if (! is_array($payload)) {
                    continue;
                }

                foreach (self::PAYLOAD as $old => $new) {
                    if (array_key_exists($old, $payload)) {
                        $payload[$new] = (int) $payload[$old] * 100;
                        unset($payload[$old]);
                    }
                }

                DB::table('supervised_actions')->where('id', $row->id)->update(['payload' => json_encode($payload)]);
            }
        });
    }

    public function down(): void
    {
        foreach (self::VALUES as $table => $columns) {
            foreach ($columns as $old => $new) {
                DB::table($table)->update([$new => DB::raw("{$new} / 100")]);
                Schema::table($table, fn (Blueprint $t) => $t->renameColumn($new, $old));
            }
        }

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->integer('gp_delta')->default(0);
            $table->unsignedInteger('gp_after')->nullable();
        });
        DB::table('ledger_entries')->update(['gp_delta' => DB::raw('cp_delta / 100')]);
        Schema::table('ledger_entries', fn (Blueprint $t) => $t->dropColumn(['cp_delta', 'coins_delta', 'coins_after']));

        Schema::table('pending_changes', fn (Blueprint $t) => $t->integer('grant_gp')->default(0));
        Schema::table('pending_changes', fn (Blueprint $t) => $t->dropColumn('grant_coins'));

        Schema::table('quests', fn (Blueprint $t) => $t->unsignedInteger('reward_gold')->nullable());
        Schema::table('quests', fn (Blueprint $t) => $t->dropColumn('reward_coins'));

        Schema::table('characters', fn (Blueprint $t) => $t->dropColumn(['pp', 'sp', 'cp']));
    }
};
