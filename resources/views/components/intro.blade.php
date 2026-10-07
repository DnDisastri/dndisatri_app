@props(['page'])

{{-- La riga sotto il titolo; il testo lo scrivono gli admin dal pannello. --}}
<p {{ $attributes->merge(['class' => 'text-sm text-muted']) }}>{!! nl2br(e($page->text())) !!}</p>
