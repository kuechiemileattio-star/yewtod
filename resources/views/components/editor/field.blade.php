@props(['model', 'label', 'type' => 'text', 'help' => null, 'textarea' => false, 'rows' => 4, 'live' => false])
@php($wm = $live ? 'wire:model.live.debounce.400ms' : 'wire:model')
@php($id = 'f-'.str_replace('.', '-', $model))
@php($errorKey = str_replace('form.', '', $model))
<div class="mb-4">
    <label for="{{ $id }}" class="field-label">{{ $label }}</label>
    @if ($textarea)
        <textarea id="{{ $id }}" {{ $wm }}="{{ $model }}" rows="{{ $rows }}" {{ $attributes->merge(['class' => 'field-input']) }}></textarea>
    @else
        <input id="{{ $id }}" type="{{ $type }}" {{ $wm }}="{{ $model }}" {{ $attributes->merge(['class' => 'field-input']) }}>
    @endif
    @if ($help)<p class="field-help">{{ $help }}</p>@endif
    @error($errorKey)<p class="field-error">{{ $message }}</p>@enderror
</div>
