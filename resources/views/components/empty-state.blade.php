{{-- État vide avec action (cahier § 10 P3). --}}
@props(['title', 'icon' => 'stack'])
<div {{ $attributes->merge(['class' => 'card flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="mb-4 flex size-14 items-center justify-center rounded-full bg-surface-2 text-muted"><x-icon :name="$icon" class="size-7" /></span>
    <p class="font-serif text-xl font-medium">{{ $title }}</p>
    @if ($slot->isNotEmpty())<div class="mt-2 max-w-md text-sm text-muted">{{ $slot }}</div>@endif
    @isset($action)<div class="mt-5">{{ $action }}</div>@endisset
</div>
