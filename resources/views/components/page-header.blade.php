@props(['title', 'subtitle' => null])
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        <h1 class="text-[26px] font-medium sm:text-[30px]">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
</div>
