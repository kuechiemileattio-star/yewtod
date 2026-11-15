{{-- Bouton + confirmation pour une méthode Livewire (remplace window.confirm, cahier § 10). --}}
@props(['method', 'arg' => null, 'title', 'confirm' => 'Confirmer', 'label', 'buttonClass' => 'btn-ghost text-danger'])
<div x-data="confirmAction" data-method="{{ $method }}" @if (! is_null($arg)) data-arg="{{ $arg }}" @endif class="inline-block">
    <button type="button" class="{{ $buttonClass }}" x-on:click="ask" {{ $attributes }}>{{ $label }}</button>
    <x-confirm-dialog :title="$title" :confirm="$confirm">{{ $slot }}</x-confirm-dialog>
</div>
