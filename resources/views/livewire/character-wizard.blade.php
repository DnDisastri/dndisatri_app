@php
    use App\Domain\Dnd\Ability;
    use App\Domain\Dnd\ClassRules;
    use App\Domain\Dnd\PointBuy;
    use App\Enums\Icon;
    use App\Livewire\CharacterWizard;

    $titles = [
        1 => 'Chi sei', 2 => 'Specie', 3 => 'Caratteristiche', 4 => 'Background',
        5 => 'Abilità', 6 => 'Equipaggiamento', 7 => 'Incantesimi', 8 => 'Riepilogo',
    ];
@endphp

<div class="mx-auto max-w-3xl space-y-4 px-4 py-6">

    {{-- Dove siamo. Tre stati distinti, e non è un vezzo: prima «fatto» e
         «da fare» avevano la stessa classe e non si distinguevano. Navy per i
         passi conclusi, rosso per quello corrente («sei qui», §5 della guida),
         spento per quelli che ancora non si toccano. --}}
    <div>
        <p class="mb-2 text-xs font-medium text-muted">
            Passo {{ $step }} di {{ CharacterWizard::LAST_STEP }}: {{ $titles[$step] }}
        </p>
        <div class="flex gap-1">
            @foreach ($titles as $number => $title)
                <div @class([
                        'h-1.5 flex-1 rounded-full transition',
                        'bg-active' => $number === $step,
                        'bg-primary' => $number < $step,
                        'bg-quiet' => $number > $step,
                    ])
                    title="{{ $title }}"></div>
            @endforeach
        </div>
    </div>

    @error('creazione')
        <x-note tone="danger">{{ $message }}</x-note>
    @enderror

    <x-panel :title="$titles[$step]">
        {{-- 1. Nome, storia e classe. La classe decide dado vita, tiri salvezza
             e quante abilità si sceglieranno. --}}
        @if ($step === 1)
            <div class="space-y-4">
                {{-- L'altra porta: partire da una build già pensata. Solo se non
                     si è già partiti da una. --}}
                @unless ($buildTitle)
                    <a href="{{ route('builds.index') }}" wire:navigate
                       class="flex items-center gap-3 rounded-md border border-dashed border-primary bg-surface px-3 py-2.5 text-sm">
                        <span class="flex-1 text-fg"><strong>Non sai da dove iniziare?</strong> Parti da una build consigliata.</span>
                        <span class="shrink-0 font-semibold text-primary">Sfoglia →</span>
                    </a>
                @endunless

                <div>
                    <label for="nome" class="mb-1 block text-sm font-medium text-fg">Nome del personaggio</label>
                    <input id="nome" type="text" wire:model.live="name"
                           class="w-full rounded-md border border-line bg-page px-3 py-2 text-fg placeholder:text-muted focus:border-active focus:outline-none">
                </div>

                {{-- La storia: l'unico campo pubblico, quello che leggono gli altri
                     giocatori. Facoltativo — si può scrivere anche dopo. --}}
                <div>
                    <label for="storia" class="mb-1 block text-sm font-medium text-fg">
                        La sua storia <span class="font-normal text-muted">(facoltativa)</span>
                    </label>
                    <textarea id="storia" wire:model.live="story" rows="3"
                              placeholder="Chi è, in due righe."
                              class="w-full rounded-md border border-line bg-page px-3 py-2 text-fg placeholder:text-muted focus:border-active focus:outline-none"></textarea>
                    <p class="mt-1 text-xs text-muted">È l'unico testo pubblico: gli altri vedono questo, non i tuoi numeri.</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-fg">Classe</label>
                    <div class="space-y-2">
                        @foreach ($classes as $option)
                            @php $scelta = $class === $option; @endphp
                            <div @class([
                                    'overflow-hidden rounded-card border transition',
                                    'border-active bg-page' => $scelta,
                                    'border-line' => ! $scelta,
                                ])>
                                <button type="button" wire:click="selectClass('{{ $option }}')"
                                        class="flex w-full items-center gap-2 px-4 py-3 text-left">
                                    <span class="font-bold text-fg">{{ $option }}</span>
                                    @if ($scelta)
                                        <span class="ml-auto text-xs font-bold text-active">Selezionata</span>
                                    @else
                                        <span class="ml-auto text-xs text-muted">d{{ ClassRules::hitDie($option) }} · {{ ClassRules::skillCount($option) }} abilità</span>
                                    @endif
                                    <x-icona :is="Icon::Expand" class="h-4 w-4 shrink-0 text-muted transition {{ $scelta ? 'rotate-180' : '' }}" />
                                </button>

                                @if ($scelta)
                                    <div class="px-4 pb-4">
                                        <div class="grid grid-cols-2 gap-2">
                                            <x-stat label="Dado vita" :value="'d' . ClassRules::hitDie($option)" />
                                            <x-stat label="Abilità da scegliere" :value="ClassRules::skillCount($option)" />
                                        </div>
                                        <div class="mt-3">
                                            <span class="mb-1 block text-xs text-muted">Tiri salvezza</span>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach (ClassRules::savingThrows($option) as $save)
                                                    <x-badge tone="accent">{{ Ability::from($save)->fullName() }}</x-badge>
                                                @endforeach
                                            </div>
                                        </div>
                                        <p class="mt-3 text-sm text-muted">{{ config("dnd.classes.list.$option.prof") }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- 2. Specie a schedine: aprire è scegliere. I bonus si sommano DOPO
             l'acquisto dei punteggi. --}}
        @if ($step === 2)
            <div class="space-y-2">
                @foreach ($speciesList as $option)
                    @php
                        $scelta = $species === $option;
                        $dati = config("dnd.species.$option");
                        $bonus = PointBuy::freeBonusesFor($option);
                    @endphp
                    <div @class([
                            'overflow-hidden rounded-card border transition',
                            'border-active bg-page' => $scelta,
                            'border-line' => ! $scelta,
                        ])>
                        <button type="button" wire:click="selectSpecies('{{ $option }}')"
                                class="flex w-full items-center gap-2 px-4 py-3 text-left">
                            <span class="font-bold text-fg">{{ $option }}</span>
                            @if ($scelta)
                                <span class="ml-auto text-xs font-bold text-active">Selezionata</span>
                            @else
                                <span class="ml-auto text-xs text-muted">Velocità {{ rtrim(rtrim(number_format($dati['speed'], 1, ',', ''), '0'), ',') }} m</span>
                            @endif
                            <x-icona :is="Icon::Expand" class="h-4 w-4 shrink-0 text-muted transition {{ $scelta ? 'rotate-180' : '' }}" />
                        </button>

                        @if ($scelta)
                            <div class="px-4 pb-4">
                                @php $descrizione = config("dnd.species_descriptions.$option"); @endphp
                                @if ($descrizione)
                                    <p class="mb-3 text-sm text-fg">{{ $descrizione }}</p>
                                @endif

                                <div class="flex flex-wrap gap-1">
                                    @foreach ($dati['asi'] ?? [] as $abil => $punti)
                                        <x-badge tone="accent">+{{ $punti }} {{ $abil === 'all' ? 'a tutto' : Ability::from($abil)->fullName() }}</x-badge>
                                    @endforeach
                                    <x-badge>Velocità {{ rtrim(rtrim(number_format($dati['speed'], 1, ',', ''), '0'), ',') }} m</x-badge>
                                </div>

                                <p class="mt-2 text-sm text-muted">{{ $dati['traits'] }}</p>

                                @if ($bonus > 0)
                                    <x-inset class="mt-3">
                                        <p class="mb-2 text-sm text-fg">
                                            Assegna <strong>{{ $bonus }} bonus da +1</strong> a caratteristiche a scelta.
                                        </p>
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach (range(0, $bonus - 1) as $slot)
                                                <select wire:model.live="speciesChoices.{{ $slot }}"
                                                        class="rounded-md border border-line bg-page px-3 py-2 text-sm text-fg focus:border-active focus:outline-none">
                                                    <option value="">Scegli</option>
                                                    @foreach ($abilities as $ability)
                                                        <option value="{{ $ability->value }}">{{ $ability->fullName() }}</option>
                                                    @endforeach
                                                </select>
                                            @endforeach
                                        </div>
                                    </x-inset>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 3. Point buy: 27 punti, da 8 a 15. I bonus di specie vengono dopo,
             ed è per questo che il tetto qui è 15 e non 20. --}}
        @if ($step === 3)
            <p class="mb-3 text-sm text-muted">
                Hai 27 punti. Ogni punteggio parte da 8 e arriva al massimo a 15.
                I valori da 9 a 13 costano un punto, il 14 e il 15 ne costano due.
                <strong>I bonus di specie si sommano dopo.</strong>
            </p>

            <p class="mb-3 text-lg text-fg">
                Punti rimanenti:
                <strong class="{{ $this->remainingPoints() === 0 ? 'text-primary' : 'text-fg' }}">
                    {{ $this->remainingPoints() }}
                </strong> / 27
            </p>

            @php $final = $this->finalScores(); @endphp

            <div class="space-y-2">
                @foreach ($abilities as $ability)
                    @php
                        $bought = $scores[$ability->value] ?? 8;
                        $extra = $final[$ability->value] - $bought;
                    @endphp
                    <div class="flex items-center gap-2.5 rounded-md bg-page px-2.5 py-2">
                        <button type="button" wire:click="decrease('{{ $ability->value }}')"
                                class="h-8 w-8 shrink-0 rounded-full bg-surface text-lg disabled:opacity-30"
                                @disabled($bought <= PointBuy::MIN_SCORE)>−</button>

                        <span class="w-6 shrink-0 text-center text-lg font-bold text-fg">{{ $bought }}</span>

                        <button type="button" wire:click="increase('{{ $ability->value }}')"
                                class="h-8 w-8 shrink-0 rounded-full bg-surface text-lg disabled:opacity-30"
                                @disabled($bought >= PointBuy::MAX_SCORE || PointBuy::remaining([...$scores, $ability->value => $bought + 1]) < 0)>+</button>

                        {{-- Nome e pillola «+specie» impilati al centro: la pillola ha
                             un posto suo e va a capo da sola, senza affollare la riga. --}}
                        <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span class="text-sm text-fg">{{ $ability->fullName() }}</span>
                            @if ($extra > 0)
                                <x-badge tone="accent" class="self-start">+{{ $extra }} specie</x-badge>
                            @endif
                        </span>

                        <span class="flex shrink-0 items-baseline gap-1.5">
                            <strong class="text-lg text-fg">{{ $final[$ability->value] }}</strong>
                            <span class="text-sm text-muted">
                                ({{ Ability::format(Ability::modifierFor($final[$ability->value])) }})
                            </span>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 4. Background a schedine: competenze, oro, la feature (dato che
             c'era già ma non si mostrava) e il kit. --}}
        @if ($step === 4)
            <div class="space-y-2">
                @foreach ($backgrounds as $option => $data)
                    @php $scelta = $background === $option; @endphp
                    <div @class([
                            'overflow-hidden rounded-card border transition',
                            'border-active bg-page' => $scelta,
                            'border-line' => ! $scelta,
                        ])>
                        <button type="button" wire:click="selectBackground('{{ $option }}')"
                                class="flex w-full items-center gap-2 px-4 py-3 text-left">
                            <span class="font-bold text-fg">{{ $option }}</span>
                            @if ($scelta)
                                <span class="ml-auto text-xs font-bold text-active">Selezionato</span>
                            @else
                                <span class="ml-auto text-xs text-muted">{{ $data['gp'] }} mo</span>
                            @endif
                            <x-icona :is="Icon::Expand" class="h-4 w-4 shrink-0 text-muted transition {{ $scelta ? 'rotate-180' : '' }}" />
                        </button>

                        @if ($scelta)
                            <div class="px-4 pb-4">
                                <div class="flex flex-wrap gap-1">
                                    <x-badge tone="accent">{{ $data['gp'] }} mo</x-badge>
                                    @foreach ($data['skills'] as $s)
                                        <x-badge>{{ config("dnd.character.skill_names.$s") }}</x-badge>
                                    @endforeach
                                </div>

                                @php $feature = config("dnd.backgrounds.features.$option"); @endphp
                                @if ($feature)
                                    <p class="mt-3 text-sm text-fg">{{ $feature }}</p>
                                @endif

                                <p class="mt-2 text-xs text-muted">{{ $data['equip'] }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 5. Abilità: quelle del background arrivano già, e restano bloccate —
             pre-spuntate e non togliibili, perché riselezionarle non darebbe
             nulla e sprecherebbe una scelta di classe. --}}
        @if ($step === 5)
            <p class="mb-3 text-sm text-muted">
                Scegli <strong>{{ ClassRules::skillCount($class) }}</strong> abilità del tuo {{ $class }}.
                Quelle che hai già dal background sono <strong>bloccate</strong>: sono tue comunque,
                sceglierle non aggiungerebbe niente.
            </p>

            <p class="mb-3 text-fg">
                Scelte: <strong class="{{ count($skills) === ClassRules::skillCount($class) ? 'text-primary' : 'text-fg' }}">
                    {{ count($skills) }}</strong> / {{ ClassRules::skillCount($class) }}
            </p>

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($this->skillOptions() as $key => $skill)
                    @if ($skill['fromBackground'])
                        {{-- Già competente dal background: spuntata e bloccata. Non
                             passa da `wire:model` — non deve entrare fra le scelte
                             di classe, e infatti il contatore non la conta. --}}
                        <div class="flex items-center gap-2 rounded-md border border-line bg-page px-3 py-2 text-sm">
                            <input type="checkbox" checked disabled class="accent-active">
                            <span class="text-muted">{{ $skill['name'] }}</span>
                            <x-badge tone="own" class="ml-auto">dal background</x-badge>
                        </div>
                    @else
                        <label @class([
                                'flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm transition',
                                'border-active bg-page' => in_array($key, $skills, true),
                                'border-line hover:border-active' => ! in_array($key, $skills, true),
                            ])>
                            <input type="checkbox" wire:model.live="skills" value="{{ $key }}" class="accent-active"
                                   @disabled(! in_array($key, $skills, true) && count($skills) >= ClassRules::skillCount($class))>
                            <span class="text-fg">{{ $skill['name'] }}</span>
                        </label>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- 6. Equipaggiamento: le scelte A/B della classe. --}}
        @if ($step === 6)
            @forelse ($equipmentChoices as $index => $choice)
                <div class="mb-4">
                    <p class="mb-2 text-sm font-semibold text-fg">{{ $choice['label'] }}</p>
                    <div class="space-y-1">
                        @foreach ($choice['options'] as $option => $data)
                            <label @class([
                                    'flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm transition',
                                    'border-active bg-page' => ($equipment[$index] ?? 0) === $option,
                                    'border-line hover:border-active' => ($equipment[$index] ?? 0) !== $option,
                                ])>
                                <input type="radio" wire:model.live="equipment.{{ $index }}" value="{{ $option }}" class="accent-active">
                                <span class="text-fg">{{ $data['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted">
                    Il tuo {{ $class }} non ha alternative da scegliere: prende il kit standard.
                </p>
            @endforelse

            <p class="mt-3 text-xs text-muted">
                Il kit del background arriva comunque, e il primo oggetto adatto a ogni slot
                verrà indossato da solo.
            </p>
        @endif

        {{-- 7. Incantesimi a schedine: la spunta sceglie, il resto della riga
             apre la descrizione (che prima era un tooltip, invisibile sul
             telefono). Solo per chi ne lancia al primo livello. --}}
        @if ($step === 7)
            @php $options = $this->spellOptions(); @endphp

            @if ($this->cantripSlots() > 0)
                <p class="mb-2 text-sm text-fg">
                    Trucchetti: <strong>{{ $this->chosenCantrips() }}</strong> / {{ $this->cantripSlots() }}
                </p>
                <div class="mb-4 space-y-1.5">
                    @foreach ($options['cantrips'] as $spell)
                        @include('livewire.partials.spell-schedina', [
                            'spell' => $spell,
                            'bloccato' => ! in_array($spell, $spells, true) && $this->chosenCantrips() >= $this->cantripSlots(),
                        ])
                    @endforeach
                </div>
            @endif

            @if ($this->spellSlots() > 0)
                <p class="mb-2 text-sm text-fg">
                    Incantesimi: <strong>{{ $this->chosenSpells() }}</strong> / {{ $this->spellSlots() }}
                </p>
                <div class="space-y-1.5">
                    @foreach ($options['spells'] as $spell)
                        @include('livewire.partials.spell-schedina', [
                            'spell' => $spell,
                            'bloccato' => ! in_array($spell, $spells, true) && $this->chosenSpells() >= $this->spellSlots(),
                        ])
                    @endforeach
                </div>
            @endif
        @endif

        {{-- 8. Riepilogo: tutto quello che si è scelto, con un «Modifica» per
             blocco che apre il passo e riporta qui. Ci arrivano tutti, non solo
             chi parte da una build. --}}
        @if ($step === 8)
            @if ($buildTitle)
                <x-note tone="info" class="mb-3">
                    Da <strong>{{ $buildTitle }}</strong>. Controlla e crea, oppure ritocca un blocco.
                </x-note>
            @endif

            @php
                $final = $this->finalScores();
                $scoreStr = collect($abilities)->map(fn ($a) => $a->fullName().' '.$final[$a->value])->join(' · ');
                $skillNames = collect($skills)->map(fn ($s) => config("dnd.character.skill_names.$s", $s));

                $righe = [
                    ['Chi sei', filled($name) ? $name : 'Nome da scrivere', 1],
                    ['Classe', filled($class) ? $class : 'Vuoto', 1],
                    ['Specie', filled($species) ? $species : 'Vuoto', 2],
                    ['Caratteristiche', $scoreStr, 3],
                    ['Background', filled($background) ? $background : 'Vuoto', 4],
                    ['Abilità', $skillNames->isNotEmpty() ? $skillNames->join(', ') : 'Vuoto', 5],
                    ['Equipaggiamento', 'Kit di classe e background', 6],
                ];

                if ($this->isCaster()) {
                    $righe[] = ['Incantesimi', count($spells).' scelti', 7];
                }
            @endphp

            <div class="space-y-2">
                @foreach ($righe as [$label, $valore, $vaAl])
                    <div class="flex items-start gap-3 rounded-md border border-line bg-page px-3 py-2.5">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ $label }}</p>
                            <p class="text-sm text-fg">{{ $valore }}</p>
                        </div>
                        <button type="button" wire:click="goToStep({{ $vaAl }})"
                                class="shrink-0 text-xs font-bold text-primary">Modifica</button>
                    </div>
                @endforeach
            </div>

            @if (blank($name))
                <x-note tone="danger" class="mt-3">
                    Manca il nome. Aprilo con «Modifica» su «Chi sei» prima di creare.
                </x-note>
            @endif

            @if ($this->buildChanged())
                <x-note tone="danger" class="mt-3">
                    Hai cambiato qualcosa di essenziale rispetto a «{{ $buildTitle }}»: la build
                    potrebbe rendere meno. Va benissimo, è il tuo personaggio.
                </x-note>
            @endif
        @endif
    </x-panel>

    <div class="flex gap-3">
        @if ($editing)
            {{-- Si sta modificando un blocco dal riepilogo: una porta sola,
                 «Torna al riepilogo», attiva solo se il passo è a posto. --}}
            <x-button size="lg" variant="secondary" class="flex-1" type="button"
                      wire:click="backToSummary" :disabled="! $this->canAdvance()">
                Torna al riepilogo
            </x-button>
        @elseif ($step === \App\Livewire\CharacterWizard::LAST_STEP)
            <x-button size="lg" variant="quiet" type="button" wire:click="previous">Indietro</x-button>
            {{-- Senza nome non si crea: partendo da una build si arriva qui col
                 nome vuoto, e il gate del primo passo è stato scavalcato. --}}
            <x-button size="lg" variant="secondary" class="flex-1" type="button" wire:click="save"
                      :disabled="blank($name)">
                Crea il personaggio
            </x-button>
        @else
            @if ($step > 1)
                <x-button size="lg" variant="quiet" type="button" wire:click="previous">Indietro</x-button>
            @endif
            <x-button size="lg" class="flex-1" type="button" wire:click="next"
                      :disabled="! $this->canAdvance()">
                Avanti
            </x-button>
        @endif
    </div>
</div>
