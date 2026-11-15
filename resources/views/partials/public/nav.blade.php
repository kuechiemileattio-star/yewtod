@php
    $links = [
        ['/', 'Accueil', request()->is('/')],
        ['/works', 'Travaux', request()->is('works*')],
        ['/meet', 'Meet Yewtod', request()->is('meet')],
        ['/books', 'Livres', request()->is('books*')],
        ['/collaborations', 'Collaborations', request()->is('collaborations')],
    ];
@endphp
<header x-data="siteNav" class="sticky top-0 z-40 border-b border-transparent bg-page/90 backdrop-blur transition-shadow" x-bind:class="scrolled ? 'shadow-md border-line' : ''">
    {{-- Barre de progression de lecture --}}
    <div class="absolute inset-x-0 top-0 h-0.5 origin-left bg-accent" x-bind:style="progressStyle" aria-hidden="true"></div>
    <nav class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8" aria-label="Navigation principale">
        <a href="{{ url('/') }}" class="flex min-h-11 shrink-0 items-center gap-2 font-serif text-xl font-bold">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $siteName }}" class="h-8 w-auto">
            @else
                <span class="inline-block size-3 rounded-full bg-accent" aria-hidden="true"></span>{{ $siteName }}
            @endif
        </a>
        <ul class="ml-6 hidden flex-1 items-center gap-1 lg:flex">
            @foreach ($links as [$href, $label, $active])
                <li>
                    <a href="{{ url($href) }}" @if ($active) aria-current="page" @endif
                       class="group relative inline-flex min-h-11 items-center px-3 text-sm font-semibold {{ $active ? 'text-ink' : 'text-muted hover:text-ink' }}">
                        {{ $label }}
                        <span class="absolute inset-x-3 bottom-1.5 h-0.5 rounded-full bg-accent transition-transform {{ $active ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}" aria-hidden="true"></span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="ml-auto flex items-center gap-1">
            <x-theme-switcher />
            <a href="{{ url('/collaborations') }}" class="btn-primary hidden sm:inline-flex">Collaborer</a>
            <a href="{{ route('dashboard') }}" class="btn-ghost min-w-11 px-2" aria-label="Espace membres" title="Espace membres"><x-icon name="user" /></a>
            <button type="button" class="btn-ghost min-w-11 px-2 lg:hidden" x-on:click="toggleMenu" aria-controls="menu-mobile" x-bind:aria-expanded="menuOpen" aria-label="Ouvrir le menu">
                <span x-show="!menuOpen"><x-icon name="menu" /></span><span x-show="menuOpen" x-cloak><x-icon name="close" /></span>
            </button>
        </div>
    </nav>
    {{-- Menu mobile plein écran : défilement de la page bloqué quand il est ouvert --}}
    <div id="menu-mobile" x-show="menuOpen" x-cloak x-on:keydown.escape.window="closeMenu" class="fixed inset-x-0 bottom-0 top-16 z-40 overflow-y-auto bg-page px-6 py-8 lg:hidden">
        <ul class="space-y-2">
            @foreach ($links as [$href, $label, $active])
                <li><a href="{{ url($href) }}" @if ($active) aria-current="page" @endif class="flex min-h-14 items-center border-b border-line font-serif text-2xl font-bold {{ $active ? 'text-accent-ink dark:text-accent' : '' }}">{{ $label }}</a></li>
            @endforeach
        </ul>
        <a href="{{ url('/collaborations') }}" class="btn-primary mt-8 w-full">Collaborer</a>
    </div>
</header>
