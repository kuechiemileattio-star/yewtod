{{-- Gabarit des pages Livewire du dashboard (config livewire.component_layout). --}}
<x-layouts.dashboard :title="$title ?? 'Tableau de bord'">
    {{ $slot }}
</x-layouts.dashboard>
