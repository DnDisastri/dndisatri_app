<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Domain\Dnd\ClassRules;
use App\Enums\LedgerAction;
use App\Enums\PendingChangeStatus;
use App\Enums\PendingChangeType;
use App\Models\Character;
use App\Models\PendingChange;
use App\Models\User;
use App\Notifications\RequestDecided;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Applica una richiesta approvata al personaggio.
 *
 * È il pattern centrale del gioco: i giocatori non modificano mai la scheda
 * direttamente, propongono, e qui la proposta diventa realtà.
 *
 * Due cose da tenere a mente leggendo questo codice.
 *
 * **Il diff arriva da un giocatore.** Non si applica quello che c'è scritto:
 * si applica solo quello che quel tipo di richiesta ha il diritto di cambiare.
 * Senza l'elenco dei campi ammessi, una richiesta costruita a mano potrebbe
 * riscrivere `user_id`, `gp` o `died_at`.
 *
 * **Il bottino si somma, non sostituisce.** Rileggendo il saldo corrente sotto
 * blocco, come chiede il brief (§4.3): serve a non annullare gli acquisti fatti
 * fra la proposta e l'approvazione.
 */
final class ApprovePendingChange
{
    /**
     * I campi che una modifica di scheda può toccare.
     *
     * Fuori di proposito: `gp` (si muove solo dal mercato e dal DM), `level`
     * (solo dal passaggio di livello), `user_id` e `died_at` (mai da qui).
     */
    private const EDITABLE = [
        'name', 'class', 'subclass', 'race', 'background',
        'str', 'dex', 'con', 'int', 'wis', 'cha',
        'speed', 'hp_max', 'hp_current', 'hp_temp',
        'saving_throws', 'skills', 'spell_ability',
        'species_traits', 'class_features', 'subclass_features', 'background_feature', 'notes',
        // La storia è pubblica e la foto pure: passano di qui come tutto il
        // resto della scheda, perché è quello che vedono gli altri.
        'story', 'photo_path',
    ];

    /** Quello che un passaggio di livello può cambiare, e niente di più. */
    private const LEVEL_UP = [
        'level', 'hit_die', 'subclass',
        'str', 'dex', 'con', 'int', 'wis', 'cha',
        'hp_max', 'hp_current',
    ];

    public function handle(PendingChange $change, User $reviewer, ?string $note = null): PendingChange
    {
        return DB::transaction(function () use ($change, $reviewer, $note) {
            $locked = PendingChange::whereKey($change->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new RuntimeException('Questa richiesta è già stata decisa.');
            }

            $character = Character::whereKey($locked->character_id)->lockForUpdate()->firstOrFail();

            $gpDelta = match ($locked->type) {
                PendingChangeType::CharacterEdit => $this->applyEdit($character, $locked),
                PendingChangeType::LevelUp => $this->applyLevelUp($character, $locked),
                PendingChangeType::Loot => $this->applyLoot($character, $locked),
                PendingChangeType::ItemEffect => $this->applyItemEffect($character, $locked),
            };

            $locked->forceFill([
                'status' => PendingChangeStatus::Approved,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            $character->refresh()->recordInLedger(
                LedgerAction::Approve,
                $locked->summary ?: $locked->type->label().' approvata',
                $gpDelta,
                $reviewer,
            );

            // Il proponente va avvisato, o resterebbe a controllare la bacheca
            // a mano. La notifica non dice chi ha deciso: quello lo vedono
            // solo DM e admin, dal pannello.
            $locked->requestedBy()->first()?->notify(new RequestDecided($locked));

            return $locked;
        });
    }

    private function applyEdit(Character $character, PendingChange $change): int
    {
        $fields = $this->allowed($change->diff, self::EDITABLE);

        // La foto non è un valore da copiare: è un file che aspetta su un
        // disco privato e va spostato dove il mondo può vederlo. Solo adesso,
        // perché è adesso che qualcuno l'ha guardata e ha detto di sì.
        if ($pending = ($fields['photo_path'] ?? null)) {
            $published = app(CharacterPhoto::class)->publish($character, $pending);

            if ($published === null) {
                unset($fields['photo_path']);
            } else {
                $fields['photo_path'] = $published;
            }
        }

        $character->forceFill($fields)->save();

        return 0;
    }

    private function applyLevelUp(Character $character, PendingChange $change): int
    {
        $character->forceFill($this->allowed($change->diff, self::LEVEL_UP))->save();

        // La classe: è l'unico punto del sistema in cui una classe nuova nasce.
        // Sta qui e non nella richiesta perché finché non è approvata il
        // personaggio non è multiclasse.
        if ($classUp = ($change->diff['class_up'] ?? null)) {
            $this->applyClassUp($character, $classUp);
        }

        // Un talento non è una colonna della scheda: è una riga a parte, e
        // arriva sotto una chiave che il filtro dei campi scarta da sé.
        if ($feat = ($change->diff['feat'] ?? null)) {
            $character->feats()->create([
                'name' => $feat['name'],
                'description' => $feat['description'] ?? null,
                'level' => $change->diff['level'] ?? $character->level,
                'source' => 'asi',
            ]);
        }

        // Gli incantesimi imparati salendo, per la stessa strada dei talenti.
        // `firstOrCreate` perché la lista non deve poter sdoppiarsi: un
        // incantesimo si conosce o non si conosce.
        foreach ($change->diff['spells'] ?? [] as $spell) {
            $character->spells()->firstOrCreate(
                ['name' => $spell],
                ['level' => ClassRules::spellLevel($spell)],
            );
        }

        return 0;
    }

    /**
     * Scrive la riga della classe e tiene allineata la copia sulla scheda.
     *
     * La copia (`characters.level`, `class`, `subclass`) esiste per poter
     * ordinare ed elencare in SQL, e il patto per non farla scollare è che la
     * scriva **solo questo metodo**, nella stessa transazione delle righe.
     *
     * @param  array<string,mixed>  $classUp
     */
    private function applyClassUp(Character $character, array $classUp): void
    {
        $row = $character->classes()->updateOrCreate(
            ['class' => $classUp['class']],
            [
                'level' => (int) $classUp['level'],
                // La prima classe che il personaggio abbia mai avuto: da lei, e
                // solo da lei, vengono i tiri salvezza competenti.
                'is_primary' => $character->classes()->count() === 0,
            ],
        );

        if (($classUp['subclass'] ?? null) !== null) {
            $row->forceFill(['subclass' => $classUp['subclass']])->save();
        }

        // Entrando in una classe nuova arrivano poche abilità, e solo per
        // Bardo, Ladro e Ranger. Si aggiungono a quelle che ci sono: una
        // competenza acquisita non si perde salendo di livello.
        if ($skills = ($classUp['skills'] ?? [])) {
            $current = $character->skills ?? [];

            foreach ($skills as $skill) {
                $current[$skill] ??= 'proficient';
            }

            $character->forceFill(['skills' => $current])->save();
        }
    }

    /**
     * L'oro si somma al valore corrente e gli oggetti si accodano
     * all'inventario: una richiesta di bottino non sovrascrive mai niente.
     */
    private function applyLoot(Character $character, PendingChange $change): int
    {
        $gp = (int) $change->grant_gp;

        if ($gp !== 0) {
            $character->increment('gp', $gp);
        }

        foreach ($change->grant_items ?? [] as $item) {
            $character->addToInventory(
                name: $item['name'],
                qty: (int) ($item['qty'] ?? 1),
                category: $item['category'] ?? null,
                value: (int) ($item['value'] ?? 0),
                details: $item['details'] ?? null,
            );
        }

        return $gp;
    }

    /**
     * L'oggetto magico trovato in sessione.
     *
     * Crea **l'oggetto prima dell'effetto**, e li lega: da qui in poi il bonus
     * vale finché l'oggetto è in sintonia, e vendendolo sparisce da sé. Se il
     * personaggio l'oggetto ce l'ha già — comprato o raccolto prima di chiedere
     * l'effetto — ci si aggancia invece di crearne un doppione.
     *
     * La sintonia si dà subito, se c'è posto: chi ha appena trovato un oggetto
     * magico se lo mette. Con tre già in uso l'oggetto arriva spento, e il
     * giocatore sceglie a cosa rinunciare.
     */
    private function applyItemEffect(Character $character, PendingChange $change): int
    {
        $effect = $change->diff ?? [];
        $name = $effect['name'] ?? 'Oggetto magico';

        $item = $character->items()->where('name', $name)->first()
            ?? $character->addToInventory(name: $name, category: 'Oggetti magici');

        $character->itemEffects()->create([
            'character_item_id' => $item->getKey(),
            'name' => $name,
            'ability' => $effect['ability'],
            'mode' => $effect['mode'],
            'value' => (int) $effect['value'],
        ]);

        if ($character->items()->where('attuned', true)->count() < Character::ATTUNEMENT_LIMIT) {
            $item->forceFill(['attuned' => true])->save();
        }

        return 0;
    }

    /**
     * @param  array<string,mixed>|null  $changes
     * @param  list<string>  $fields
     * @return array<string,mixed>
     */
    private function allowed(?array $changes, array $fields): array
    {
        return array_intersect_key($changes ?? [], array_flip($fields));
    }
}
