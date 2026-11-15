{{-- Monter / descendre / supprimer une ligne d'un constructeur de blocs. --}}
@props(['list', 'index', 'count'])
<div class="flex shrink-0 gap-1">
    <button type="button" wire:click="moveRow('{{ $list }}', {{ $index }}, -1)" class="btn-ghost min-w-11 px-2" aria-label="Monter" @disabled($index === 0)><x-icon name="arrow-up" class="size-4" /></button>
    <button type="button" wire:click="moveRow('{{ $list }}', {{ $index }}, 1)" class="btn-ghost min-w-11 px-2" aria-label="Descendre" @disabled($index === $count - 1)><x-icon name="arrow-down" class="size-4" /></button>
    <button type="button" wire:click="removeRow('{{ $list }}', {{ $index }})" class="btn-ghost min-w-11 px-2 text-danger" aria-label="Supprimer"><x-icon name="trash" class="size-4" /></button>
</div>
