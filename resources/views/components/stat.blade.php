@props(['label', 'value', 'note' => null])

<div class="rounded-md border-2 border-line bg-surface px-2 py-3 text-center">
    <span class="block text-xs text-muted">{{ $label }}</span>
    <span class="block text-2xl font-bold text-fg">{{ $value }}</span>
    @if ($note)
        <span class="block text-xs text-muted">{{ $note }}</span>
    @endif
</div>
