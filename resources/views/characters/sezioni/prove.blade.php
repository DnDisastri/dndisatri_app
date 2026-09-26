@php
    use App\Domain\Dnd\Ability;

    $proficiency = $character->proficiencyBonus();
    $skills = config('dnd.character.skills');
    $skillNames = config('dnd.character.skill_names');
@endphp

<x-panel title="Caratteristiche">
    <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
        @foreach (Ability::cases() as $ability)
            @php
                $score = $effective->score($ability);
                $altered = $score !== $base->score($ability);
            @endphp

            <div @class([
                'rounded-md border-2 bg-surface px-2 py-3 text-center',
                'border-on-accent-soft' => $altered,
                'border-line' => ! $altered,
            ])>
                <span class="block text-xs text-muted">{{ $ability->label() }}</span>
                <span class="block text-2xl font-bold text-fg">{{ $score }}</span>
                <span class="block text-sm text-fg">{{ Ability::format($effective->modifier($ability)) }}</span>
                @if ($altered)

                    <span class="block text-xs text-on-accent-soft" title="Alterata da un oggetto magico">
                        base {{ $base->score($ability) }}
                    </span>
                @endif
            </div>
        @endforeach
    </div>
</x-panel>

<div class="grid gap-4 md:grid-cols-2">
    <x-panel title="Tiri salvezza">
        <ul class="space-y-1">
            @foreach (Ability::cases() as $ability)
                @php $proficient = (bool) ($character->saving_throws[$ability->value] ?? false); @endphp
                <li class="flex items-center justify-between rounded bg-page px-2 py-1 text-sm">
                    <span>
                        {{ $ability->fullName() }}
                        @if ($proficient)
                            <x-icona :is="\App\Enums\Icon::Proficient" class="inline h-3 w-3 text-primary" title="Competente" />
                        @endif
                    </span>
                    <span class="font-bold text-fg">
                        {{ Ability::format($character->savingThrow($ability)) }}
                    </span>
                </li>
            @endforeach
        </ul>
    </x-panel>

    <x-panel title="Abilità">
        <ul class="grid gap-x-4 gap-y-1 sm:grid-cols-2">
            @foreach ($skills as $key => $ability)
                @php $level = $character->skills[$key] ?? 'none'; @endphp
                <li class="flex items-center justify-between rounded bg-page px-2 py-1 text-sm">
                    <span>
                        {{ $skillNames[$key] }}
                        @if ($level === 'proficient')
                            <x-icona :is="\App\Enums\Icon::Proficient" class="inline h-3 w-3 text-primary" title="Competente" />
                        @elseif ($level === 'expert')
                            <x-icona :is="\App\Enums\Icon::Expert" class="inline h-4 w-4 text-on-accent-soft" title="Esperto" />
                        @endif
                    </span>
                    <span class="font-bold text-fg">
                        {{ Ability::format($character->skillBonus($key)) }}
                    </span>
                </li>
            @endforeach
        </ul>
    </x-panel>
</div>
