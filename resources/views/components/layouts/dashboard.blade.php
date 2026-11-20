@props(['title' => 'Tableau de bord'])
@php
    /** @var \App\Models\User $me */
    $me = auth()->user();
    $menu = \App\Support\DashboardMenu::for($me);
@endphp
<!DOCTYPE html>
<html lang="fr" class="{{ \App\Support\Theme::htmlClass() }}">
<head>
    @include('partials.head')
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="min-h-dvh">
<a href="#contenu" class="skip-link">Aller au contenu</a>
<div x-data="drawer" class="min-h-dvh lg:flex">
    {{-- Voile du tiroir mobile --}}
    <div x-show="open" x-cloak x-on:click="close" class="fixed inset-0 z-40 bg-black/50 lg:hidden" aria-hidden="true"></div>

    {{-- Menu : tiroir sur mobile/tablette, colonne fixe sur grand écran (décision 14) --}}
    <aside id="menu-dashboard" class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col border-r border-line bg-surface transition-transform lg:static lg:translate-x-0"
           x-bind:class="open ? 'translate-x-0' : ''" aria-label="Menu du tableau de bord">
        <div class="flex h-16 items-center justify-between border-b border-line px-4">
            <a href="{{ route('dashboard') }}" class="flex min-h-11 items-center gap-2 font-serif text-lg font-bold">
                <span class="inline-block size-2.5 rounded-full bg-accent" aria-hidden="true"></span>{{ config('app.name') }}
            </a>
            <button type="button" class="btn-ghost min-w-11 px-2 lg:hidden" x-on:click="close" aria-label="Fermer le menu"><x-icon name="close" /></button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            @foreach ($menu as $group)
                @if ($group['label'])<p class="mb-1 mt-5 px-3 text-xs font-bold uppercase tracking-wider text-muted">{{ $group['label'] }}</p>@endif
                <ul class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @php($active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'))
                        <li>
                            <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                               @if ($item['external']) target="_blank" rel="noopener noreferrer" @endif
                               @class(['flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition', 'bg-surface-2 text-ink' => $active, 'text-muted hover:bg-surface-2 hover:text-ink' => ! $active])>
                                <x-icon :name="$item['icon']" />
                                <span class="flex-1">{{ $item['label'] }}@if ($item['external'])<span class="sr-only"> (nouvel onglet)</span>@endif</span>
                                @if ($item['external'])<x-icon name="external" class="size-4" />@endif
                                @if ($item['badge'] > 0)
                                    <span class="badge bg-accent-ink text-white dark:bg-accent dark:text-on-accent" aria-label="{{ $item['badge'] }} en attente">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>
        <div class="border-t border-line p-4">
            <div class="flex items-center gap-3">
                @if ($me->avatarUrl())
                    <img src="{{ $me->avatarUrl() }}" alt="" class="size-10 rounded-full object-cover">
                @else
                    <span class="flex size-10 items-center justify-center rounded-full bg-accent-2-ink font-bold text-white dark:bg-accent-2 dark:text-page" aria-hidden="true">{{ $me->initial() }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">{{ $me->name }}</p>
                    <p class="truncate text-xs text-muted">{{ $me->role->name }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-ghost min-w-11 px-2" aria-label="Se déconnecter" title="Se déconnecter"><x-icon name="logout" /></button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-line bg-page/90 px-3 backdrop-blur sm:px-6">
            <button type="button" class="btn-ghost min-w-11 px-2 lg:hidden" x-on:click="show" aria-controls="menu-dashboard" x-bind:aria-expanded="open" aria-label="Ouvrir le menu"><x-icon name="menu" /></button>
            <p class="min-w-0 flex-1 truncate font-serif text-lg font-bold sm:text-xl">{{ $title }}</p>
            <a href="{{ url('/') }}" class="btn-ghost hidden px-3 sm:inline-flex" target="_blank" rel="noopener">Voir le site</a>
            <x-theme-switcher :save-url="route('dashboard.preferences.theme')" />
        </header>
        <main id="contenu" class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>
</div>
<x-toasts />
@livewireScriptConfig(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
</body>
</html>
