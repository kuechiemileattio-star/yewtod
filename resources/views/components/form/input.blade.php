@props(['name', 'label', 'type' => 'text', 'help' => null])
@php($id = $attributes->get('id', $name))
<div class="mb-4">
    <label for="{{ $id }}" class="field-label">{{ $label }}</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $attributes->get('value')) }}"
           @if ($help) aria-describedby="{{ $id }}-aide" @endif
           @error($name) aria-invalid="true" @enderror
           {{ $attributes->except(['id', 'value'])->merge(['class' => 'field-input']) }}>
    @if ($help)<p id="{{ $id }}-aide" class="field-help">{{ $help }}</p>@endif
    @error($name)<p class="field-error">{{ $message }}</p>@enderror
</div>
