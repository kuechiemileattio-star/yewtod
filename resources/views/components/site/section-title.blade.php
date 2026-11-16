@props(['eyebrow' => null, 'title', 'id' => null, 'link' => null, 'linkLabel' => null])
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        @if ($eyebrow)<p class="text-xs font-bold uppercase tracking-[0.2em] text-accent-ink dark:text-accent">{{ $eyebrow }}</p>@endif
        <h2 @if ($id) id="{{ $id }}" @endif class="mt-1 text-3xl font-bold sm:text-4xl">{{ $title }}</h2>
    </div>
    @if ($link)
        <a href="{{ $link }}" class="link inline-flex min-h-11 items-center gap-1">{{ $linkLabel }} <x-icon name="arrow-right" class="size-4" /></a>
    @endif
</div>
