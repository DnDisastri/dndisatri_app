@props(['larghezza' => 'ampia'])

{{-- `stretta` per testi e moduli, `ampia` per griglie e colonne.
     Gli avvisi stanno qui per prendere la larghezza della pagina. --}}
<div {{ $attributes->class([
    'mx-auto w-full px-4 py-6 md:px-6 lg:px-8 lg:py-8',
    $larghezza === 'stretta' ? 'max-w-3xl' : 'max-w-6xl',
]) }}>
    @if (session('status') || session('error'))
        <div class="mb-4 space-y-2">
            @if (session('status'))
                <x-note>{{ session('status') }}</x-note>
            @endif

            @if (session('error'))
                <x-note tone="danger">{{ session('error') }}</x-note>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
