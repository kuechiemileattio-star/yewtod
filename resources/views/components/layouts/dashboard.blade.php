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
<div x-data="drawer" class="dash-shell min-h-dvh lg:flex">
    {{-- Voile du tiroir mobile --}}
    <div x-show="open" x-cloak x-on:click="close" class="fixed inset-0 z-40 bg-[rgb(15_20_35/.55)] lg:hidden" aria-hidden="true"></div>

    {{-- Menu bleu nuit (référence D78) : tiroir sur mobile/tablette, colonne collante sur grand écran (décision 14) --}}
    <aside id="menu-dashboard" class="fixed inset-y-0 left-0 z-50 flex w-[min(300px,82vw)] -translate-x-full flex-col bg-navy px-[18px] pb-4 pt-7 text-white shadow-[0_0_40px_-10px_rgb(0_0_0/.5)] transition-transform duration-300 ease-out-expo lg:sticky lg:top-0 lg:h-dvh lg:w-[250px] lg:shrink-0 lg:translate-x-0 lg:shadow-none"
           x-bind:class="open ? 'translate-x-0' : ''" aria-label="Menu du tableau de bord">
        <div class="mb-[26px] flex items-center justify-between">
            <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center gap-2 text-[13px] text-on-navy-muted hover:text-white"><x-icon name="arrow-right" class="size-3.5 rotate-180" /> Retour au site</a>
            <button type="button" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded text-white hover:bg-white/10 lg:hidden" x-on:click="close" aria-label="Fermer le menu"><x-icon name="close" /></button>
        </div>
        <a href="{{ route('dashboard') }}" class="mb-7 flex min-h-11 items-center gap-2.5 px-1">
            <span class="font-serif text-[22px]">{{ config('app.name') }}</span>
            <span class="rounded-full border border-amber/40 px-1.5 py-px font-mono text-[9.5px] text-amber">CMS</span>
        </a>
        <nav class="-mx-1 flex-1 overflow-y-auto px-1">
            @foreach ($menu as $group)
                @if ($group['label'])<p class="px-3 pb-1.5 pt-2.5 font-mono text-[9.5px] uppercase tracking-[0.1em] text-white/60">{{ $group['label'] }}</p>@endif
                <ul class="mb-2.5 space-y-1">
                    @foreach ($group['items'] as $item)
                        @php($active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'))
                        <li>
                            <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                               @if ($item['external']) target="_blank" rel="noopener noreferrer" @endif
                               @class(['dash-tab flex min-h-10 items-center gap-[11px] rounded border-l-[3px] px-3 text-[13px]', 'border-white bg-amber font-extrabold text-navy' => $active, 'border-transparent font-semibold text-white/75 hover:bg-white/10 hover:text-white' => ! $active])>
                                <x-icon :name="$item['icon']" class="size-4 shrink-0" />
                                <span class="flex-1">{{ $item['label'] }}@if ($item['external'])<span class="sr-only"> (nouvel onglet)</span>@endif</span>
                                @if ($item['external'])<x-icon name="external" class="size-3.5" />@endif
                                @if ($item['badge'] > 0)
                                    <span @class(['badge', 'bg-navy text-white' => $active, 'bg-accent-strong text-white' => ! $active]) aria-label="{{ $item['badge'] }} en attente">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="mt-4 border-t border-white/15 pt-2">
            @csrf
            <button class="dash-tab flex min-h-10 w-full items-center gap-[11px] rounded px-3 text-[13px] font-semibold text-white/70 hover:bg-white/10 hover:text-white"><x-icon name="logout" class="size-4" /> Se déconnecter</button>
        </form>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 border-b border-line bg-page/85 backdrop-blur-md lg:static lg:border-0 lg:bg-transparent lg:backdrop-blur-none">
            <div class="flex min-h-16 items-center gap-3 px-4 py-2 sm:px-6 lg:mx-11 lg:mb-6 lg:mt-7 lg:border-b lg:border-line lg:px-0 lg:pb-6 lg:pt-0">
                <button type="button" class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-md border border-line text-ink lg:hidden" x-on:click="show" aria-controls="menu-dashboard" x-bind:aria-expanded="open" aria-label="Ouvrir le menu"><x-icon name="menu" /></button>
                <div class="min-w-0 flex-1">
                    <p class="font-mono text-[10px] uppercase tracking-[0.1em] text-accent-2-ink">{{ config('app.name') }} / CMS</p>
                    <p class="truncate font-serif text-[22px] leading-tight lg:text-[25px]">{{ $title }}</p>
                </div>
                <x-theme-switcher :save-url="route('dashboard.preferences.theme')" />
                <a href="{{ route('dashboard.profile') }}" class="flex min-h-11 shrink-0 items-center gap-2.5">
                    <span class="sr-only">Mon profil : {{ $me->name }},</span>
                    <span class="hidden items-center gap-2 font-mono text-[10px] uppercase tracking-[0.04em] text-muted sm:flex"><span class="live-dot" aria-hidden="true"></span>{{ $me->role->name }}</span>
                    @if ($me->avatarUrl())
                        <img src="{{ $me->avatarUrl() }}" alt="" class="size-[30px] rounded-full border border-line object-cover">
                    @else
                        <span class="grid size-[30px] place-items-center rounded-full bg-navy text-xs font-bold text-white" aria-hidden="true">{{ $me->initial() }}</span>
                    @endif
                </a>
            </div>
        </header>
        <main id="contenu" class="page-enter w-full min-w-0 flex-1 px-4 pb-[70px] pt-5 sm:px-6 lg:px-11 lg:pt-0">
            {{ $slot }}
        </main>
    </div>
</div>
<x-toasts />
@livewireScriptConfig(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
</body>
</html>
