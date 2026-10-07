<?php

namespace App\Http\Controllers;

use App\Actions\Characters\CharacterPhoto;
use App\Actions\Characters\LootSuggestions;
use App\Actions\Characters\ProposeChange;
use App\Actions\Characters\RequestLevelUp;
use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\Coins;
use App\Domain\Dnd\ItemEffectMode;
use App\Domain\Dnd\Multiclass;
use App\Domain\Dnd\Progression;
use App\Enums\EquipmentSlot;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\PendingChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

/** Nessuna azione qui tocca la scheda: creano richieste che un DM approva in bacheca. */
class ProposalController extends Controller
{
    public function index(Request $request): View
    {
        $mostraArchiviate = $request->boolean('archiviate');

        $base = PendingChange::visibleTo($request->user());

        return view('proposals.index', [
            'mostraArchiviate' => $mostraArchiviate,
            'changes' => (clone $base)
                ->when($mostraArchiviate, fn ($q) => $q->archived(), fn ($q) => $q->notArchived())
                ->with(['character', 'reviewedBy'])
                ->latest('id')
                ->simplePaginate(15)
                ->withQueryString(),
            'archiviate' => (clone $base)->archived()->count(),
            'daSvuotare' => (clone $base)->notArchived()->decided()->count(),
        ]);
    }

    /** Non cancella: segna la data e la richiesta si può ripristinare. */
    public function archive(Request $request, PendingChange $change): RedirectResponse
    {
        $this->authorizeArchive($request, $change);

        abort_unless($change->isArchivable(), 403);

        $change->update(['archived_at' => now()]);

        return back()->with('status', 'Richiesta archiviata.');
    }

    public function clear(Request $request): RedirectResponse
    {
        PendingChange::visibleTo($request->user())
            ->notArchived()
            ->decided()
            ->update(['archived_at' => now()]);

        return back()->with('status', 'Richieste decise archiviate.');
    }

    public function restore(Request $request, PendingChange $change): RedirectResponse
    {
        $this->authorizeArchive($request, $change);

        $change->update(['archived_at' => null]);

        return back()->with('status', 'Richiesta ripristinata.');
    }

    /** Solo ciò che si vede in bacheca (il giocatore le sue, il DM tutte); fuori 404, per non rivelarne l'esistenza. */
    private function authorizeArchive(Request $request, PendingChange $change): void
    {
        abort_unless(
            PendingChange::visibleTo($request->user())->whereKey($change->getKey())->exists(),
            404,
        );
    }

    public function editForm(Character $character): View
    {
        $this->authorize('propose', $character);

        return view('proposals.edit', ['character' => $character]);
    }

    public function submitEdit(Request $request, Character $character, ProposeChange $proposals): RedirectResponse
    {
        $this->authorize('propose', $character);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'background' => ['nullable', 'string', 'max:255'],
            'story' => ['nullable', 'string', 'max:2000'],
            'private_story' => ['nullable', 'string', 'max:5000'],
            'species_traits' => ['nullable', 'string'],
            'class_features' => ['nullable', 'string'],
            'subclass_features' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            // `image` controlla il contenuto: l'estensione la sceglie chi carica.
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        unset($validated['photo']);

        // Disco privato: la foto entra nella scheda solo se un DM approva.
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = app(CharacterPhoto::class)->store($request->file('photo'));
        }

        return $this->propose(
            fn () => $proposals->edit($character, $request->user(), $validated),
            $character,
        );
    }

    public function levelUpForm(Request $request, Character $character): View
    {
        $this->authorize('propose', $character);

        $newLevel = $character->level + 1;
        $levels = $character->classLevels();

        // Dalla query: cambiare classe cambia mezzo modulo, si ricarica la pagina.
        $class = (string) $request->query('classe');
        $class = ClassRules::exists($class) ? $class : $character->class;

        $classLevel = ($levels[$class] ?? 0) + 1;
        $row = $character->classes()->where('class', $class)->first();

        return view('proposals.level-up', [
            'character' => $character,
            'newLevel' => $newLevel,
            'isAsiLevel' => Progression::isAsiLevel($newLevel),
            'levels' => $levels,
            'pickedClass' => $class,
            'classLevel' => $classLevel,
            // Una classe nuova si può prendere solo se non si è al tetto.
            'canAddClass' => count($levels) < Character::MAX_CLASSES,
            'availableClasses' => ClassRules::names()
                ->reject(fn (string $name) => array_key_exists($name, $levels))
                ->values()
                ->all(),
            'unmet' => array_key_exists($class, $levels) ? [] : Multiclass::unmetRequirements(
                $character->baseScores(), array_keys($levels), $class
            ),
            'entrySkills' => array_key_exists($class, $levels)
                ? ['count' => 0, 'from' => []]
                : Multiclass::skillsOnEntry($class),
            'skillNames' => config('dnd.character.skill_names', []),
            // La sottoclasse si sceglie al livello DI QUELLA CLASSE.
            'canPickSubclass' => $row?->subclass === null
                && $classLevel >= Progression::subclassLevel($class),
            'subclasses' => ClassRules::subclasses($class),
        ]);
    }

    public function submitLevelUp(Request $request, Character $character, RequestLevelUp $levelUp): RedirectResponse
    {
        $this->authorize('propose', $character);

        $abilities = array_column(Ability::cases(), 'value');

        $validated = $request->validate([
            'class' => ['nullable', 'string', Rule::in(ClassRules::names()->all())],
            'asi_mode' => ['nullable', Rule::in(['plus2', 'plus1', 'feat'])],
            'asi_first' => ['nullable', Rule::in($abilities)],
            'asi_second' => ['nullable', Rule::in($abilities)],
            'feat_name' => ['nullable', 'string', 'max:255'],
            'feat_description' => ['nullable', 'string'],
            'subclass' => ['nullable', 'string', 'max:255'],
            'skills' => ['array'],
            'skills.*' => ['string'],
        ]);

        return $this->propose(
            fn () => $levelUp->handle(
                $character,
                $request->user(),
                class: $validated['class'] ?? null,
                asiMode: $validated['asi_mode'] ?? null,
                asiAbilities: array_filter([$validated['asi_first'] ?? null, $validated['asi_second'] ?? null]),
                featName: $validated['feat_name'] ?? null,
                featDescription: $validated['feat_description'] ?? null,
                subclass: $validated['subclass'] ?? null,
                skills: array_values($validated['skills'] ?? []),
            ),
            $character,
        );
    }

    public function lootForm(Character $character): View
    {
        $this->authorize('propose', $character);

        return view('proposals.loot', [
            'character' => $character,
            'suggerimenti' => app(LootSuggestions::class)->handle(),
        ]);
    }

    public function submitLoot(Request $request, Character $character, ProposeChange $proposals): RedirectResponse
    {
        $this->authorize('propose', $character);

        $validated = $request->validate([
            'coins' => ['nullable', 'array'],
            'coins.*' => ['nullable', 'integer', 'min:0', 'max:'.Coins::MAX],
            'items' => ['nullable', 'array', 'max:'.ProposeChange::LOOT_MAX_ITEMS],
            'items.*.name' => ['nullable', 'string', 'max:100'],
            'items.*.qty' => ['nullable', 'integer', 'min:1', 'max:999'],
            'items.*.category' => ['nullable', Rule::in(CharacterItem::CATEGORIES)],
            'items.*.base' => ['nullable', Rule::in(collect(EquipmentSlot::bases())->flatten()->all())],
            'items.*.value' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'items.*.details' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'items.max' => 'Al massimo :max oggetti per richiesta: registra il resto con una seconda richiesta.',
            'items.*.name.max' => 'Il nome di un oggetto può avere al massimo :max caratteri: la descrizione va nei dettagli.',
        ]);

        $coins = Coins::fromArray($validated['coins'] ?? []);

        if ($coins->value() > ProposeChange::LOOT_MAX_CP) {
            throw ValidationException::withMessages([
                'coins' => 'Al massimo '.Coins::formatValue(ProposeChange::LOOT_MAX_CP).' di monete per richiesta: per somme più alte chiedi a un dungeon master.',
            ]);
        }

        // Le righe lasciate in bianco non sono oggetti; il valore arriva in mo.
        $items = collect($validated['items'] ?? [])
            ->filter(fn ($item) => filled($item['name'] ?? null))
            ->map(function (array $item) {
                $item['value_cp'] = (int) round((float) ($item['value'] ?? 0) * 100);
                unset($item['value']);

                return $item;
            })
            ->values()
            ->all();

        return $this->propose(
            fn () => $proposals->loot(
                $character,
                $request->user(),
                $coins,
                $items,
                $validated['note'] ?? null,
            ),
            $character,
        );
    }

    public function itemEffectForm(Character $character): View
    {
        $this->authorize('propose', $character);

        return view('proposals.item-effect', ['character' => $character]);
    }

    public function submitItemEffect(Request $request, Character $character, ProposeChange $proposals): RedirectResponse
    {
        $this->authorize('propose', $character);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ability' => ['required', Rule::enum(Ability::class)],
            'mode' => ['required', Rule::enum(ItemEffectMode::class)],
            'value' => ['required', 'integer', 'between:-10,30'],
        ]);

        return $this->propose(
            fn () => $proposals->itemEffect(
                $character,
                $request->user(),
                $validated['name'],
                Ability::from($validated['ability']),
                ItemEffectMode::from($validated['mode']),
                (int) $validated['value'],
            ),
            $character,
        );
    }

    /** Le eccezioni delle azioni di dominio diventano errori di modulo. */
    private function propose(callable $action, Character $character): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['proposta' => $e->getMessage()]);
        }

        return redirect()
            ->route('characters.show', $character)
            ->with('status', 'Richiesta inviata: la vedrà un dungeon master.');
    }
}
