@props([
    'name' => null,
    'model' => null,
    'label' => 'Monete',
    'valori' => [],
])

{{-- Con `name` è un campo di modulo (`name[gp]`), con `model` è legato a Livewire (`model.gp`). --}}
@php
    $chiave = $model ?? $name;
    $id = str_replace(['.', '[', ']'], '-', $chiave);
@endphp

<fieldset {{ $attributes }}>
    <legend class="mb-1 block text-sm font-medium text-fg">{{ $label }}</legend>

    <div class="grid grid-cols-4 gap-2">
        @foreach (\App\Domain\Dnd\Coin::descending() as $moneta)
            <label for="{{ $id }}-{{ $moneta->value }}" class="block">
                <span class="mb-0.5 block text-xs text-muted" title="{{ $moneta->label() }}">{{ $moneta->abbreviation() }}</span>
                <input id="{{ $id }}-{{ $moneta->value }}" type="number" min="0" inputmode="numeric"
                       @if ($model) wire:model="{{ $model }}.{{ $moneta->value }}"
                       @else name="{{ $name }}[{{ $moneta->value }}]"
                             value="{{ old("{$name}.{$moneta->value}", $valori[$moneta->value] ?? '') }}" @endif
                       placeholder="0" aria-label="{{ $moneta->label() }}"
                       class="w-full rounded-md border border-line bg-page px-2 py-2 text-fg placeholder:text-muted focus:border-active focus:outline-none">
            </label>
        @endforeach
    </div>

    @error($chiave)
        <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
    @enderror
    @error("{$chiave}.*")
        <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
    @enderror
</fieldset>
