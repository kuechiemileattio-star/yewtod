{{-- Bascule Clair / Sombre / Système (cahier § 9.1). --}}
@props(['saveUrl' => null])
<div x-data="themeSwitcher" data-theme="{{ \App\Support\Theme::current() }}" @if ($saveUrl) data-save-url="{{ $saveUrl }}" @endif {{ $attributes }}>
    <button type="button" x-on:click="cycle" class="btn-ghost min-w-11 px-2" x-bind:aria-label="label" aria-label="Changer de thème" x-bind:title="label">
        <span x-show="isSystem"><x-icon name="monitor" /></span>
        <span x-show="isLight" x-cloak><x-icon name="sun" /></span>
        <span x-show="isDark" x-cloak><x-icon name="moon" /></span>
    </button>
</div>
