@extends('layouts.app')
@section('title', 'Passaggio di livello')

@php use App\Domain\Dnd\Ability; @endphp

@section('content')
<x-pagina larghezza="stretta" class="space-y-4">
{{-- La classe viene selezionata tramite route perché sottoclasse, requisiti e competenze dipendono dalla scelta. --}}
    <x-panel class="text-center">
        <h2 class="text-xl text-fg">{{ $character->name }}</h2>
        <p class="mt-0.5 text-lg text-fg">
            livello {{ $character->level }} <span class="text-muted">→</span> {{ $newLevel }}
        </p>
        <p class="mt-2 text-sm text-muted">
            Il dungeon master vedrà tutto quello che scegli qui prima di approvare.
        </p>
    </x-panel>

    <x-legenda-livello :character="$character" />

    @error('proposta')
        <x-note tone="danger">{{ $message }}</x-note>
    @enderror

    <x-panel title="In quale classe sali">
        <div class="space-y-2">
            @foreach ($levels as $name => $level)
                <a href="{{ route('proposals.level-up', [$character, 'classe' => $name]) }}"
                   @class([
                       'flex items-center justify-between rounded-md border-2 px-3 py-2 text-sm transition',
                       'border-active bg-page' => $pickedClass === $name,
                       'border-line hover:border-line' => $pickedClass !== $name,
                   ])>
                    <span>{{ $name }} {{ $level }} → {{ $level + 1 }}</span>
                    <span class="text-xs text-muted">d{{ App\Domain\Dnd\ClassRules::hitDie($name) }}</span>
                </a>
            @endforeach

            @if ($canAddClass)
                <details class="rounded-md border-2 border-line px-3 py-2"
                         @if (! array_key_exists($pickedClass, $levels)) open @endif>
                    <summary class="cursor-pointer text-sm text-muted">Prendi una classe nuova</summary>

                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach ($availableClasses as $name)
                            <a href="{{ route('proposals.level-up', [$character, 'classe' => $name]) }}"
                               @class([
                                   'rounded border px-2 py-1 text-xs',
                                   'border-active bg-page' => $pickedClass === $name,
                                   'border-line hover:bg-page' => $pickedClass !== $name,
                               ])>{{ $name }}</a>
                        @endforeach
                    </div>
                </details>
            @else
                <p class="text-xs text-muted">
                    Hai già {{ App\Models\Character::MAX_CLASSES }} classi: non se ne prendono altre.
                </p>
            @endif
        </div>

        @if ($unmet)
            <div class="mt-3 rounded-md border border-line/40 bg-accent-soft px-3 py-2 text-sm text-on-accent-soft">
                <p class="font-semibold">Non hai i requisiti per {{ $pickedClass }}:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($unmet as $why)
                        <li>{{ $why }}</li>
                    @endforeach
                </ul>
                <p class="mt-1 text-xs">
                    Puoi chiederlo lo stesso: la richiesta arriverà col dettaglio, e sarà un dungeon master a decidere.
                </p>
            </div>
        @endif
    </x-panel>

    <form method="POST" action="{{ route('proposals.level-up', $character) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="class" value="{{ $pickedClass }}">

        @php $campo = 'w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg placeholder:text-muted focus:border-active focus:outline-none'; @endphp

        <x-panel title="Punti ferita">
            <div class="space-y-3">
                <label class="flex items-start gap-2">
                    <input type="radio" name="hp_method" value="media" class="mt-1 accent-active"
                           @checked(old('hp_method', 'media') === 'media')>
                    <span>
                        <span class="font-medium">La media: +{{ $hpAverage }}</span>
                        <span class="block text-xs text-muted">{{ App\Domain\Dnd\HitPoints::average($hitDie) }} del d{{ $hitDie }} più la Costituzione.</span>
                    </span>
                </label>

                <label class="flex items-start gap-2">
                    <input type="radio" name="hp_method" value="tiro" class="mt-1 accent-active"
                           @checked(old('hp_method') === 'tiro')>
                    <span class="flex-1">
                        <span class="font-medium">Tirato al tavolo</span>
                        <span class="block text-xs text-muted">Scrivi il risultato del d{{ $hitDie }}: la Costituzione si aggiunge da sola.</span>
                        <input type="number" name="hp_roll" min="1" max="{{ $hitDie }}" inputmode="numeric"
                               value="{{ old('hp_roll') }}" placeholder="Da 1 a {{ $hitDie }}"
                               class="{{ $campo }} mt-1 max-w-40">
                        @error('hp_roll')
                            <span class="mt-1 block text-sm text-on-danger-soft">{{ $message }}</span>
                        @enderror
                    </span>
                </label>
            </div>
        </x-panel>

        @if ($entrySkills['count'] > 0)
            <x-panel title="Competenze da {{ $pickedClass }}">
                <p class="mb-2 text-sm text-muted">
                    Entrando in questa classe scegli {{ $entrySkills['count'] }} abilità.
                    I tiri salvezza non cambiano: restano quelli della prima classe.
                </p>

                <div class="grid gap-1 sm:grid-cols-2">
                    @foreach ($entrySkills['from'] as $skill)
                        <label class="flex items-center gap-2 rounded bg-page px-3 py-1.5 text-sm">
                            <input type="checkbox" name="skills[]" value="{{ $skill }}" class="accent-active">
                            <span>{{ $skillNames[$skill] ?? $skill }}</span>
                        </label>
                    @endforeach
                </div>
            </x-panel>
        @endif

        @if ($canPickSubclass)
            <x-panel title="Sottoclasse">
                <p class="mb-2 text-sm text-muted">
                    È il livello in cui un {{ $pickedClass }} sceglie la sottoclasse: il
                    {{ $classLevel }}° di quella classe, non del personaggio. Si sceglie una volta sola.
                </p>
                <select name="subclass"
                        class="w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-fg focus:border-active focus:outline-none">
                    <option value="">Scelgo più avanti</option>
                    @foreach ($subclasses as $subclass)
                        <option value="{{ $subclass }}" @selected(old('subclass') === $subclass)>{{ $subclass }}</option>
                    @endforeach
                </select>
            </x-panel>
        @endif

        @if ($isAsiLevel)
            <x-panel title="Aumento di caratteristica o talento">
                <p class="mb-3 text-sm text-muted">
                    Al livello {{ $newLevel }} scegli una delle tre. Il tetto dei punteggi è 20.
                </p>

                <div class="space-y-4">
                    <label class="flex items-start gap-2">
                        <input type="radio" name="asi_mode" value="plus2" class="mt-1 accent-active"
                               @checked(old('asi_mode') === 'plus2')>
                        <span class="flex-1">
                            <span class="font-medium">+2 a una caratteristica</span>
                            <select name="asi_first"
                                    class="mt-1 w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg">
                                @foreach (Ability::cases() as $ability)
                                    <option value="{{ $ability->value }}" @selected(old('asi_first') === $ability->value)>
                                        {{ $ability->fullName() }} ({{ $character->getAttribute($ability->value) }})
                                    </option>
                                @endforeach
                            </select>
                        </span>
                    </label>

                    <label class="flex items-start gap-2">
                        <input type="radio" name="asi_mode" value="plus1" class="mt-1 accent-active"
                               @checked(old('asi_mode') === 'plus1')>
                        <span class="flex-1">
                            <span class="font-medium">+1 a due caratteristiche diverse</span>
                            <div class="mt-1 grid grid-cols-2 gap-2">
                                @foreach (['asi_first', 'asi_second'] as $field)
                                    <select name="{{ $field }}"
                                            class="rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg">
                                        @foreach (Ability::cases() as $ability)
                                            <option value="{{ $ability->value }}" @selected(old($field) === $ability->value)>
                                                {{ $ability->fullName() }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endforeach
                            </div>
                        </span>
                    </label>

                    <label class="flex items-start gap-2">
                        <input type="radio" name="asi_mode" value="feat" class="mt-1 accent-active"
                               @checked(old('asi_mode') === 'feat')>
                        <span class="flex-1">
                            <span class="font-medium">Un talento</span>
                            @include('proposals.partials.talento')
                        </span>
                    </label>
                </div>
            </x-panel>
        @endif

        @if ($learnable->isNotEmpty())
            <x-panel title="Incantesimi da {{ $pickedClass }}">
                <p class="mb-3 text-sm text-muted">
                    Spunta quelli che impari salendo. Quanti dipende dalla tua classe: il dungeon master controlla prima di approvare.
                </p>

                <div class="space-y-2">
                    @foreach ($learnable as $livello => $nomi)
                        <details class="rounded-md border border-line" @if (array_intersect($nomi->all(), old('spells', []))) open @endif>
                            <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-fg">
                                {{ $livello === 0 ? 'Trucchetti' : 'Livello '.$livello }}
                                <span class="font-normal text-muted">({{ $nomi->count() }})</span>
                            </summary>
                            <div class="grid gap-1 px-3 pb-3 sm:grid-cols-2">
                                @foreach ($nomi as $nome)
                                    <label class="flex items-center gap-2 rounded bg-page px-3 py-1.5 text-sm">
                                        <input type="checkbox" name="spells[]" value="{{ $nome }}" class="accent-active"
                                               @checked(in_array($nome, old('spells', []), true))>
                                        <span>{{ $nome }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>

                @if ($swappable->isNotEmpty())
                    <div class="mt-4 border-t border-line pt-4">
                        <p class="mb-2 text-sm font-medium text-fg">Sostituisci un incantesimo</p>
                        <p class="mb-2 text-xs text-muted">Facoltativo: ne dimentichi uno che conosci e al suo posto ne impari un altro (non un trucchetto).</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <select name="forget" aria-label="Incantesimo da dimenticare" class="{{ $campo }}">
                                <option value="">Non sostituisco</option>
                                @foreach ($swappable as $nome)
                                    <option value="{{ $nome }}" @selected(old('forget') === $nome)>Dimentico {{ $nome }}</option>
                                @endforeach
                            </select>
                            <select name="replace_with" aria-label="Incantesimo nuovo" class="{{ $campo }}">
                                <option value="">Al suo posto…</option>
                                @foreach ($learnable->except(0)->flatten() as $nome)
                                    <option value="{{ $nome }}" @selected(old('replace_with') === $nome)>{{ $nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </x-panel>
        @endif

        @include('proposals.partials.sessione')

        <div class="flex gap-3">
            <x-button size="lg" class="flex-1">Chiedi il levelup</x-button>
            <x-button size="lg" variant="quiet" :href="route('characters.show', $character)">Annulla</x-button>
        </div>
    </form>
</x-pagina>
@endsection
