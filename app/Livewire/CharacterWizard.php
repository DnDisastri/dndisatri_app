<?php

namespace App\Livewire;

use App\Actions\Characters\CreateCharacter;
use App\Domain\Dnd\Ability;
use App\Domain\Dnd\AbilityScores;
use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\PointBuy;
use App\Models\Build;
use App\Models\Character;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Component;

/**
 * La creazione guidata del personaggio.
 *
 * L'ordine dei passi è quello del brief (§5.8) e **non va riprogettato**: è
 * collaudato in due anni d'uso. Ogni passo dipende dai precedenti — la specie
 * cambia i punteggi, la classe decide quante abilità si scelgono, i punteggi
 * decidono quanti incantesimi si preparano.
 *
 * Qui c'è la logica dell'interfaccia; le regole stanno in `App\Domain\Dnd` e
 * la creazione vera in `CreateCharacter`, che rivalida tutto: quello che si
 * vede a schermo è una comodità, non una difesa.
 */
class CharacterWizard extends Component
{
    public int $step = 1;

    public const LAST_STEP = 8;

    /** Si sta modificando un blocco dal riepilogo? Cambia i pulsanti in fondo. */
    public bool $editing = false;

    /** Se si è partiti da una build: il titolo (per l'avviso) e lo stato di
     *  partenza, per accorgersi se poi lo si cambia. */
    public ?string $buildTitle = null;

    /** @var array<string,mixed> */
    public array $buildSnapshot = [];

    // Passo 1
    public string $name = '';

    public string $class = '';

    /** Chi è, in due righe: è la parte che vedranno gli altri giocatori. */
    public string $story = '';

    // Passo 2
    public string $species = '';

    /** @var array<string,int> i +1 a scelta di Umano Variante e Mezzelfo */
    public array $speciesChoices = [];

    // Passo 3
    /** @var array<string,int> */
    public array $scores = [];

    // Passo 4
    public string $background = '';

    // Passo 5
    /** @var list<string> */
    public array $skills = [];

    // Passo 6
    /** @var array<int,int> indice della scelta => indice dell'opzione */
    public array $equipment = [];

    // Passo 7
    /** @var list<string> */
    public array $spells = [];

    /** @var list<string> gli incantesimi con la descrizione aperta (solo UI) */
    public array $openSpells = [];

    public function mount(): void
    {
        $this->authorize('create', Character::class);

        $this->scores = PointBuy::starting();

        // Partiti da una build? Si arriva qui con `?build=slug`.
        if ($slug = request()->query('build')) {
            $this->applyBuild((string) $slug);
        }
    }

    /**
     * Riempie il modulo con una build consigliata.
     *
     * `wizardState()` restituisce **solo le caselle che la build sa riempire**:
     * quelle assenti non si toccano, o cancellerebbero i valori di partenza (i
     * punteggi del point buy su tutti). Una build **completa** porta dritti al
     * riepilogo; una incompleta lascia dal primo passo, con quel che sa già.
     */
    private function applyBuild(string $slug): void
    {
        $build = Build::where('slug', $slug)->first();

        if (! $build || ! $build->isPublished()) {
            return;
        }

        foreach ($build->wizardState() as $prop => $value) {
            $this->{$prop} = $value;
        }

        $this->buildTitle = $build->title;
        $this->buildSnapshot = [
            'class' => $this->class,
            'species' => $this->species,
            'scores' => $this->scores,
            'skills' => $this->skills,
        ];

        if ($build->isComplete()) {
            $this->step = self::LAST_STEP;
        }
    }

    // === Passo 3: point buy ===

    public function increase(string $ability): void
    {
        $current = $this->scores[$ability] ?? PointBuy::MIN_SCORE;
        $next = $current + 1;

        // Si può salire solo se il punteggio esiste a listino e il budget regge.
        if (PointBuy::costOf($next) === null) {
            return;
        }

        $candidate = [...$this->scores, $ability => $next];

        if (PointBuy::remaining($candidate) >= 0) {
            $this->scores = $candidate;
        }
    }

    public function decrease(string $ability): void
    {
        $next = ($this->scores[$ability] ?? PointBuy::MIN_SCORE) - 1;

        if (PointBuy::costOf($next) !== null) {
            $this->scores[$ability] = $next;
        }
    }

    // === Selezione delle schedine ===

    /**
     * Le schedine a tendina di Classe, Specie e Background: aprire una scheda
     * **è** sceglierla, e la selezionata è l'unica «aperta». Cambiando una
     * scelta si azzera quello che dipendeva da essa, altrimenti resterebbe una
     * scelta orfana che blocca l'«Avanti».
     */
    public function selectClass(string $class): void
    {
        if ($class === $this->class) {
            return;
        }

        $this->class = $class;

        // Abilità e incantesimi dipendono dalla classe: quelli di prima
        // potrebbero non essere più ammessi.
        $this->skills = [];
        $this->spells = [];
    }

    public function selectSpecies(string $species): void
    {
        if ($species === $this->species) {
            return;
        }

        $this->species = $species;

        // I +1 a scelta valgono per la specie che li dava: cambiando specie
        // vanno rifatti, o il conteggio in `canAdvance` non torna più.
        $this->speciesChoices = [];
    }

    public function selectBackground(string $background): void
    {
        $this->background = $background;
    }

    /** Apre o chiude la descrizione di un incantesimo: pura comodità di lettura. */
    public function toggleSpell(string $spell): void
    {
        $this->openSpells = in_array($spell, $this->openSpells, true)
            ? array_values(array_diff($this->openSpells, [$spell]))
            : [...$this->openSpells, $spell];
    }

    // === Navigazione ===

    public function next(): void
    {
        if (! $this->canAdvance()) {
            return;
        }

        // Cambiando classe le abilità scelte prima potrebbero non essere più
        // ammesse: si ripuliscono invece di arrivare a fondo e fallire.
        if ($this->step === 1) {
            $this->skills = [];
            $this->spells = [];
        }

        $this->step = min(self::LAST_STEP, $this->step + 1);

        // Chi non lancia incantesimi non ha un settimo passo da compilare: lo
        // salta e arriva dritto al riepilogo.
        if ($this->step === 7 && ! $this->isCaster()) {
            $this->step = 8;
        }
    }

    public function previous(): void
    {
        $this->step = max(1, $this->step - 1);

        // Simmetrico all'andata: tornando indietro il settimo passo non esiste
        // per chi non lancia.
        if ($this->step === 7 && ! $this->isCaster()) {
            $this->step = 6;
        }
    }

    /**
     * «Modifica» dal riepilogo: apre il passo di quel blocco e ci si mette in
     * modalità modifica, così in fondo compare solo «Torna al riepilogo».
     */
    public function goToStep(int $step): void
    {
        if ($step < 1 || $step >= self::LAST_STEP) {
            return;
        }

        $this->editing = true;
        $this->step = $step;
    }

    /** Chiude la modifica e torna al riepilogo, se il passo è a posto. */
    public function backToSummary(): void
    {
        if (! $this->canAdvance()) {
            return;
        }

        $this->editing = false;
        $this->step = self::LAST_STEP;
    }

    /** Lancia incantesimi al primo livello? Decide se il settimo passo esiste. */
    public function isCaster(): bool
    {
        return $this->cantripSlots() > 0 || $this->spellSlots() > 0;
    }

    /**
     * Partiti da una build, si è poi cambiato qualcosa di essenziale?
     * Semplice apposta: classe, specie, punteggi o abilità.
     */
    public function buildChanged(): bool
    {
        if (! $this->buildTitle) {
            return false;
        }

        $skills = $this->skills;
        $originali = $this->buildSnapshot['skills'] ?? [];
        sort($skills);
        sort($originali);

        return $this->class !== ($this->buildSnapshot['class'] ?? null)
            || $this->species !== ($this->buildSnapshot['species'] ?? null)
            || $this->scores !== ($this->buildSnapshot['scores'] ?? null)
            || $skills !== $originali;
    }

    /** Il passo corrente è completo? Regola il pulsante «Avanti». */
    public function canAdvance(): bool
    {
        return match ($this->step) {
            1 => filled($this->name) && ClassRules::exists($this->class),
            2 => filled($this->species)
                && count(array_filter($this->speciesChoices)) === PointBuy::freeBonusesFor($this->species),
            3 => PointBuy::isValid($this->scores),
            4 => filled($this->background),
            5 => count($this->skills) === ClassRules::skillCount($this->class),
            6 => true,
            default => true,
        };
    }

    public function save()
    {
        $this->authorize('create', Character::class);

        try {
            $character = app(CreateCharacter::class)->handle(
                owner: auth()->user(),
                name: $this->name,
                class: $this->class,
                species: $this->species,
                background: $this->background,
                boughtScores: $this->scores,
                speciesChoices: array_filter($this->speciesChoices),
                skills: array_values($this->skills),
                spells: array_values($this->spells),
                equipmentChoices: $this->equipment,
                story: filled($this->story) ? $this->story : null,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('creazione', $e->getMessage());

            return null;
        }

        return $this->redirectRoute('characters.show', $character, navigate: true);
    }

    // === Dati per la vista ===

    /** I punteggi finali, bonus di specie compresi. */
    public function finalScores(): array
    {
        return PointBuy::withSpecies($this->scores, $this->species, array_filter($this->speciesChoices));
    }

    public function remainingPoints(): int
    {
        return PointBuy::remaining($this->scores);
    }

    /** Le abilità che questa classe può scegliere, con quelle del background già segnate. */
    public function skillOptions(): Collection
    {
        $fromBackground = config("dnd.backgrounds.list.{$this->background}.skills", []);
        $names = config('dnd.character.skill_names', []);

        return collect(ClassRules::skillChoices($this->class))
            ->mapWithKeys(fn (string $key) => [$key => [
                'name' => $names[$key] ?? $key,
                // Quelle del background arrivano comunque: sceglierle di
                // nuovo sprecherebbe una scelta di classe.
                'fromBackground' => in_array($key, $fromBackground, true),
            ]]);
    }

    public function cantripSlots(): int
    {
        return (int) (ClassRules::spellsKnownAtFirst($this->class)['cantrips'] ?? 0);
    }

    public function spellSlots(): int
    {
        return ClassRules::spellCountAtFirst(
            $this->class,
            AbilityScores::fromArray($this->finalScores()),
        );
    }

    /**
     * Gli incantesimi che si possono imparare **al primo livello**, divisi fra
     * trucchetti e incantesimi di 1º.
     *
     * La lista di classe arriva fino ai livelli alti, ma qui si crea sempre a
     * livello 1: gli unici slot che si hanno sono di 1º, e non si conosce un
     * incantesimo per uno slot che non c'è. Per questo il gruppo dei non
     * trucchetti si ferma al livello 1.
     */
    public function spellOptions(): array
    {
        $available = collect(ClassRules::spellList($this->class))
            ->groupBy(fn (string $spell) => ClassRules::spellLevel($spell));

        return [
            'cantrips' => $available->get(0, collect())->values(),
            'spells' => $available->get(1, collect())->values(),
        ];
    }

    public function chosenCantrips(): int
    {
        return collect($this->spells)->filter(fn ($s) => ClassRules::spellLevel($s) === 0)->count();
    }

    public function chosenSpells(): int
    {
        return collect($this->spells)->filter(fn ($s) => ClassRules::spellLevel($s) > 0)->count();
    }

    public function render()
    {
        return view('livewire.character-wizard', [
            'abilities' => Ability::cases(),
            'classes' => ClassRules::names(),
            'speciesList' => array_keys(config('dnd.species', [])),
            'backgrounds' => config('dnd.backgrounds.list', []),
            'equipmentChoices' => config("dnd.equipment.{$this->class}.choices", []),
        ]);
    }
}
