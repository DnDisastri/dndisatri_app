<?php

namespace App\Http\Controllers;

use App\Actions\Characters\CharacterPhoto;
use App\Actions\Characters\ProposeChange;
use App\Actions\Characters\RequestLevelUp;
use App\Domain\Dnd\Ability;
use App\Domain\Dnd\ItemEffectMode;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\Multiclass;
use App\Domain\Dnd\Progression;
use App\Models\Character;
use App\Models\PendingChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Le proposte del giocatore.
 *
 * Nessuna di queste azioni tocca la scheda: creano una richiesta che finisce
 * in bacheca. È il pattern centrale del gioco, e il motivo per cui i giocatori
 * non hanno nessuna via diretta per modificarsi il personaggio.
 */
class ProposalController extends Controller
{
    /** Le richieste del giocatore, dalla più recente. */
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
                ->get(),
            // Quante ce ne sono nell'archivio: il collegamento lo dice, e senza
            // il numero un «mostra archiviate» che porta a zero è un invito
            // sprecato. Sull'altra sponda, quante se ne possono ancora svuotare.
            'archiviate' => (clone $base)->archived()->count(),
            'daSvuotare' => (clone $base)->notArchived()->decided()->count(),
        ]);
    }

    /**
     * Mettere via una richiesta decisa. Non si cancella: prende una data e
     * sparisce dalla lista, pronta a tornare da «mostra archiviate».
     */
    public function archive(Request $request, PendingChange $change): RedirectResponse
    {
        $this->authorizeArchive($request, $change);

        abort_unless($change->isArchivable(), 403);

        $change->update(['archived_at' => now()]);

        return back()->with('status', 'Richiesta archiviata.');
    }

    /** Svuota tutte le richieste decise: le mette via in un colpo solo. */
    public function clear(Request $request): RedirectResponse
    {
        PendingChange::visibleTo($request->user())
            ->notArchived()
            ->decided()
            ->update(['archived_at' => now()]);

        return back()->with('status', 'Richieste decise archiviate.');
    }

    /** Ripescarla dall'archivio: torna in lista dov'era. */
    public function restore(Request $request, PendingChange $change): RedirectResponse
    {
        $this->authorizeArchive($request, $change);

        $change->update(['archived_at' => null]);

        return back()->with('status', 'Richiesta ripristinata.');
    }

    /**
     * Si archivia e si ripristina solo ciò che si può vedere in bacheca: il
     * giocatore le sue richieste, il DM tutte. Fuori da lì, 404 — non si dice
     * nemmeno che esiste.
     */
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
            // Quanto basta per raccontare chi è, non per scriverci un romanzo:
            // questo testo finisce su una card che gli altri sfogliano.
            'story' => ['nullable', 'string', 'max:2000'],
            'species_traits' => ['nullable', 'string'],
            'class_features' => ['nullable', 'string'],
            'subclass_features' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            // Il tipo si controlla sul contenuto, non sul nome del file:
            // `image` guarda dentro, l'estensione la scrive chi carica.
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        unset($validated['photo']);

        // Il file va da parte subito, ma sul disco privato: entra nella scheda
        // solo se un DM approva la richiesta.
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

        // In quale classe si sta salendo. Arriva dalla query perché cambiando
        // classe cambia mezzo modulo — sottoclasse, requisiti, abilità — e
        // ricaricare è più onesto che tenere in piedi due stati.
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
            // I requisiti si mostrano subito: chiedere una classe sapendo già
            // che manca qualcosa è diverso dal chiederla e scoprirlo dopo.
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

        return view('proposals.loot', ['character' => $character]);
    }

    public function submitLoot(Request $request, Character $character, ProposeChange $proposals): RedirectResponse
    {
        $this->authorize('propose', $character);

        $validated = $request->validate([
            'gp' => ['nullable', 'integer', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.category' => ['nullable', 'string', 'max:255'],
            'items.*.value' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Le righe lasciate in bianco non sono oggetti.
        $items = collect($validated['items'] ?? [])
            ->filter(fn ($item) => filled($item['name'] ?? null))
            ->values()
            ->all();

        return $this->propose(
            fn () => $proposals->loot(
                $character,
                $request->user(),
                (int) ($validated['gp'] ?? 0),
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

    /**
     * Le azioni di dominio rifiutano quello che non ha senso lanciando
     * un'eccezione. Qui diventa un errore di modulo, invece di una pagina
     * bianca.
     */
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
