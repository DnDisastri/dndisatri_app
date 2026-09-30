@props(['larghezza' => 'ampia'])

{{--
    Il contenitore di ogni pagina: tre larghezze e margini che crescono con lo
    schermo. `lettura` per testi e form, `media` per le colonne singole,
    `ampia` per elenchi e schede che su PC si dispongono a colonne.
--}}
<div {{ $attributes->class([
    'mx-auto w-full px-4 py-6 md:px-6 lg:px-8 lg:py-8',
    match ($larghezza) {
        'lettura' => 'max-w-2xl',
        'media' => 'max-w-3xl',
        default => 'max-w-6xl',
    },
]) }}>
    {{ $slot }}
</div>
