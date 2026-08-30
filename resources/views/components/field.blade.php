@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'autocomplete' => null,
    'required' => false,
])

<div>
    <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-fg">
        {{ $label }}
    </label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($required) required @endif
        {{ $attributes->merge([
            'class' => 'w-full rounded-md border border-line bg-page px-3 py-2 text-fg
                        placeholder:text-muted focus:border-active focus:outline-none',
        ]) }}
    >

    @error($name)
        <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
    @enderror
</div>
