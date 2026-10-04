@extends('layouts.app')
@section('title', 'Notifiche')

@section('content')
<x-pagina larghezza="stretta">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 text-2xl text-fg">
            <x-icona :is="\App\Enums\Icon::Notifications" class="h-7 w-7" />
            {{ $mostraArchiviate ? 'Notifiche archiviate' : 'Notifiche' }}
        </h2>

        <div class="flex flex-wrap items-center gap-2">
            @if ($mostraArchiviate)
                <x-button variant="quiet" size="sm" :href="route('notifications.index')">
                    <x-icona :is="\App\Enums\Icon::Back" class="h-4 w-4" /> Torna alle attive
                </x-button>

                @if ($notifications->isNotEmpty())
                    <form method="POST" action="{{ route('notifications.empty-archive') }}"
                          data-conferma="Eliminare tutte le notifiche archiviate? Non si possono recuperare.">
                        @csrf
                        @method('DELETE')
                        <x-button variant="quiet" size="sm">
                            <x-icona :is="\App\Enums\Icon::Delete" class="h-4 w-4" /> Svuota archivio
                        </x-button>
                    </form>
                @endif
            @else
                @if ($daSvuotare > 0)
                    <form method="POST" action="{{ route('notifications.clear') }}">
                        @csrf
                        <x-button variant="quiet" size="sm">
                            <x-icona :is="\App\Enums\Icon::Stash" class="h-4 w-4" /> Svuota
                        </x-button>
                    </form>
                @endif

                @if ($archiviate > 0)
                    <x-button variant="quiet" size="sm" :href="route('notifications.index', ['archiviate' => 1])">
                        Archiviate ({{ $archiviate }})
                    </x-button>
                @endif
            @endif
        </div>
    </div>

    <div class="space-y-2">
        @forelse ($notifications as $notification)
            @php $data = $notification->data; @endphp

            <x-card>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="flex items-center gap-2 font-semibold text-fg">
                            @if ($notification->unread())
                                <span class="h-2 w-2 shrink-0 rounded-full bg-active" title="Non letta"></span>
                            @endif
                            {{ $data['title'] }}
                        </p>

                        @if (filled($data['body'] ?? null))
                            <p class="mt-1 whitespace-pre-line text-sm text-muted">{{ $data['body'] }}</p>
                        @endif

                        @if (filled($data['url'] ?? null))
                            <a href="{{ $data['url'] }}" class="mt-2 inline-flex items-center gap-1 text-sm text-fg hover:underline">
                                Vai a vedere <x-icona :is="\App\Enums\Icon::GoTo" class="h-4 w-4" />
                            </a>
                        @endif
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <span class="text-xs text-muted">{{ $notification->created_at->diffForHumans() }}</span>

                        @if ($mostraArchiviate)
                            <div class="flex gap-1">
                                <form method="POST" action="{{ route('notifications.restore', $notification->id) }}">
                                    @csrf
                                    <button type="submit" title="Rimetti in lista"
                                            class="rounded-full p-1 text-muted transition hover:bg-page hover:text-fg">
                                        <x-icona :is="\App\Enums\Icon::Unstash" class="h-5 w-5" />
                                        <span class="sr-only">Ripristina</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}"
                                      data-conferma="Eliminare questa notifica? Non si potrà recuperare.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Elimina per sempre"
                                            class="rounded-full p-1 text-muted transition hover:bg-page hover:text-on-danger-soft">
                                        <x-icona :is="\App\Enums\Icon::Delete" class="h-5 w-5" />
                                        <span class="sr-only">Elimina</span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('notifications.archive', $notification->id) }}">
                                @csrf
                                <button type="submit" title="Archivia"
                                        class="rounded-full p-1 text-muted transition hover:bg-page hover:text-fg">
                                    <x-icona :is="\App\Enums\Icon::Stash" class="h-5 w-5" />
                                    <span class="sr-only">Archivia</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-card>
        @empty
            <p class="text-center text-muted">
                {{ $mostraArchiviate
                    ? 'Nessuna notifica archiviata.'
                    : 'Nessuna notifica. Tutto tranquillo.' }}
            </p>
        @endforelse
    </div>

    <x-back :href="route('home')">Torna alla Home</x-back>
</x-pagina>

@include('partials.conferma')
@endsection
