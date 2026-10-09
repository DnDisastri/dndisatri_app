{{-- Le quest che il DM ha messo in questa sessione. --}}
<x-panel>
    <h3 class="flex items-center gap-2 text-lg font-semibold text-fg">
        <x-icona :is="\App\Enums\Icon::Quests" class="h-5 w-5" /> Le quest della sessione
    </h3>

    <ul class="mt-3 space-y-2">
        @foreach ($session->quests as $quest)
            <li>
                <a href="{{ route('quests.show', $quest) }}" class="flex items-baseline justify-between gap-3 text-sm hover:underline">
                    <span class="min-w-0 font-semibold text-fg">{{ $quest->title }}</span>
                    @if ($quest->difficulty)
                        <span class="shrink-0 text-xs text-muted">{{ $quest->difficulty->label() }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</x-panel>
