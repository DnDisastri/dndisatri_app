@extends('layouts.app')
@section('title', 'Chi siamo')

@section('content')
@php
    use App\Enums\Icon;
    use Illuminate\Support\Str;

    // Solo link http(s): un href `javascript:` non deve diventare cliccabile.
    $social = collect([
        ['icona' => Icon::Instagram, 'label' => 'Instagram', 'url' => $about?->socials['instagram'] ?? null],
        ['icona' => Icon::Tiktok, 'label' => 'TikTok', 'url' => $about?->socials['tiktok'] ?? null],
        ['icona' => Icon::Telegram, 'label' => 'Telegram', 'url' => $about?->socials['telegram'] ?? null],
    ])->filter(fn ($s) => filled($s['url']) && Str::startsWith($s['url'], ['http://', 'https://']));
@endphp

<x-pagina larghezza="stretta">
    @if ($about?->coverUrl())
        {{-- Copertina decorativa: il titolo è già testo. --}}
        <img src="{{ $about->coverUrl() }}" alt=""
             class="mb-6 aspect-video w-full rounded-poster rounded-br-poster-cut border border-line object-cover">
    @endif

    <h2 class="mb-4 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="Icon::General" class="h-7 w-7" /> Chi siamo
    </h2>

    @if ($about && filled($about->body))
        {{-- Blade esegue l'escape; `whitespace-pre-line` conserva gli a capo. --}}
        <div class="whitespace-pre-line leading-relaxed text-fg">{{ $about->body }}</div>
    @else
        <x-empty size="lg">Questa pagina non è ancora stata scritta.</x-empty>
    @endif

    @if ($social->isNotEmpty())
        <div class="mt-8 text-center">
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Seguici</h3>

            <div class="flex flex-wrap items-center justify-center gap-3">
                @foreach ($social as $s)
                    <a href="{{ $s['url'] }}" target="_blank" rel="noopener noreferrer"
                       title="{{ $s['label'] }}" aria-label="{{ $s['label'] }}"
                       class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary text-on-primary transition hover:opacity-90">
                        <x-icona :is="$s['icona']" class="h-8 w-8" />
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <x-back :href="route('home')">Torna alla Home</x-back>
</x-pagina>
@endsection
