@php
    $links = [
        ['/', 'Accueil', request()->is('/')],
        ['/works', 'Travaux', request()->is('works*')],
        ['/meet', 'Meet Yewtod', request()->is('meet')],
        ['/books', 'Livres', request()->is('books*')],
        ['/collaborations', 'Collaborations', request()->is('collaborations')],
    ];
@endphp
{{--
    En-tête de la référence (D78) : barre bleu nuit, navigation en capitales
    soulignée d'ambre, bouton « Parlons projet ». Le menu mobile est un
    FRÈRE de l'en-tête, pas un enfant : un élément « fixed » placé dans un
    parent qui a un filtre (backdrop-filter, transform…) est positionné par
    rapport à ce parent, ce qui écrasait le menu dans les 64 px de la barre
    (bug du chevauchement « Accueil » sur mobile).
--}}
<div x-data="siteNav" class="contents">
<header class="sticky top-0 z-40 bg-navy text-on-navy transition-shadow duration-300" x-bind:class="scrolled ? 'shadow-[0_14px_32px_-22px_rgb(23_42_70_/_0.6)]' : ''">
    {{-- Barre de progression de lecture (ambre) --}}
    <div class="absolute inset-x-0 top-0 h-[3px] origin-left bg-amber" x-bind:style="progressStyle" aria-hidden="true"></div>
    <nav class="mx-auto flex max-w-[1180px] items-center gap-3 px-4 py-3 sm:px-7" x-bind:class="scrolled ? 'lg:py-2.5' : 'lg:py-3.5'" aria-label="Navigation principale">
        <a href="{{ url('/') }}" class="flex min-h-11 shrink-0 items-center gap-2 text-white">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $siteName }}" class="h-8 w-auto max-w-[170px] brightness-0 invert">
            @else
                <span class="font-serif text-[26px] font-medium leading-none tracking-[-0.03em]">{{ $siteName }}</span>
            @endif
        </a>
        <ul class="hidden flex-1 items-center justify-center gap-1 lg:flex">
            @foreach ($links as [$href, $label, $active])
                <li>
                    <a href="{{ url($href) }}" @if ($active) aria-current="page" @endif
                       class="group relative inline-flex min-h-11 items-center px-3 text-xs uppercase tracking-[0.06em] transition-colors {{ $active ? 'font-bold text-white' : 'font-semibold text-on-navy-muted hover:text-white' }}">
                        {{ $label }}
                        <span class="absolute inset-x-3 bottom-1.5 h-0.5 rounded-full bg-amber transition-transform duration-300 {{ $active ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}" aria-hidden="true"></span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="ml-auto flex items-center gap-1 lg:ml-0">
            @include('partials.public.search', ['buttonClass' => 'btn-on-navy'])
            <x-theme-switcher button-class="btn-on-navy" />
            <a href="{{ url('/collaborations') }}" class="btn-amber ml-1 hidden min-h-10 px-4 text-[12.5px] font-bold sm:inline-flex">Parlons projet <x-icon name="arrow-up-right" class="size-3.5" /></a>
            <a href="{{ route('dashboard') }}" class="btn-on-navy hidden xs:inline-flex" aria-label="Espace membres" title="Espace membres"><x-icon name="cog" class="size-[18px]" /></a>
            <button type="button" class="btn-on-navy text-white lg:hidden" x-on:click="toggleMenu" aria-controls="menu-mobile" x-bind:aria-expanded="menuOpen" aria-label="Ouvrir le menu">
                <span x-show="!menuOpen"><x-icon name="menu" class="size-6" /></span><span x-show="menuOpen" x-cloak><x-icon name="close" class="size-6" /></span>
            </button>
        </div>
    </nav>
</header>
{{-- Menu mobile : panneau bleu nuit sous la barre, défilement de la page bloqué quand il est ouvert --}}
<div id="menu-mobile" x-show="menuOpen" x-cloak x-transition.opacity x-on:keydown.escape.window="closeMenu"
     class="fixed inset-x-0 bottom-0 top-[68px] z-40 overflow-y-auto border-t border-white/15 bg-navy px-6 pb-8 pt-4 text-on-navy lg:hidden">
    <ul class="flex flex-col gap-1">
        @foreach ($links as [$href, $label, $active])
            <li><a href="{{ url($href) }}" @if ($active) aria-current="page" @endif class="flex min-h-12 items-center text-base font-semibold {{ $active ? 'text-white' : 'text-on-navy-muted hover:text-white' }}">
                @if ($active)<span class="mr-3 inline-block h-4 w-0.5 rounded-full bg-amber" aria-hidden="true"></span>@endif{{ $label }}
            </a></li>
        @endforeach
    </ul>
    <a href="{{ url('/collaborations') }}" class="btn-amber mt-6 w-full font-bold">Parlons projet <x-icon name="arrow-up-right" class="size-4" /></a>
    <a href="{{ route('dashboard') }}" class="mt-3 flex min-h-11 items-center gap-2 border border-white/20 px-4 font-mono text-xs text-on-navy-muted hover:text-white"><x-icon name="cog" class="size-4" /> Espace membres</a>
</div>
</div>
