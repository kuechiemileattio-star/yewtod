{{-- Titre de section (référence D78) : sur-titre à chasse fixe, titre serif, lien éventuel. --}}
@props(['eyebrow' => null, 'title', 'id' => null, 'link' => null, 'linkLabel' => null])
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        @if ($eyebrow)<p class="kicker">{{ $eyebrow }}</p>@endif
        <h2 @if ($id) id="{{ $id }}" @endif class="mt-1.5 text-[clamp(1.6rem,3vw,2.1rem)] font-medium">{{ $title }}</h2>
    </div>
    @if ($link)
        <a href="{{ $link }}" class="link inline-flex min-h-11 items-center gap-1 text-sm">{{ $linkLabel }} <x-icon name="arrow-right" class="size-4" /></a>
    @endif
</div>
