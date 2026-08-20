@extends('layouts.app')
@section('title', 'Passaggio di livello')

@php use App\Domain\Dnd\Ability; @endphp

@section('content')
<div class="mx-auto max-w-2xl space-y-4 px-4 py-6">
{{-- La classe viene selezionata tramite route perché sottoclasse, requisiti e competenze dipendono dalla scelta. --}}
    <x-panel class="text-center">
        <h2 class="text-xl text-fg">{{ $character->name }}</h2>
        <p class="mt-0.5 text-lg text-fg">
            livello {{ $character->level }} <span class="text-muted">→</span> {{ $newLevel }}
        </p>
        <p class="mt-2 text-sm text-muted">
            I punti ferita si calcolano col metodo media, e
            l'aumento lo vedrà il dungeon master prima di approvare.
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
                    Puoi chiederlo lo stesso: la richiesta arriverà col dettaglio, e sarà un DM a decidere.
                </p>
            </div>
        @endif
    </x-panel>

    <form method="POST" action="{{ route('proposals.level-up', $character) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="class" value="{{ $pickedClass }}">

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
                    È il livello in cui un {{ $pickedClass }} sceglie la sottoclasse — il
                    {{ $classLevel }}° di quella classe, non del personaggio. Si sceglie una volta sola.
                </p>
                <select name="subclass"
                        class="w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-fg focus:border-active focus:outline-none">
                    <option value="">— scelgo più avanti —</option>
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
                            <input type="text" name="feat_name" value="{{ old('feat_name') }}"
                                   placeholder="Nome del talento"
                                   class="mt-1 w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg placeholder:text-muted">
                            <textarea name="feat_description" rows="2" placeholder="Cosa fa (facoltativo)"
                                      class="mt-1 w-full rounded-md border-2 border-line bg-surface px-3 py-2 text-sm text-fg placeholder:text-muted">{{ old('feat_description') }}</textarea>
                        </span>
                    </label>
                </div>
            </x-panel>
        @endif

        <div class="flex gap-3">
            <x-button size="lg" class="flex-1">Chiedi il levelup</x-button>
            <x-button size="lg" variant="quiet" :href="route('characters.show', $character)">Annulla</x-button>
        </div>
    </form>
</div>
@endsection
