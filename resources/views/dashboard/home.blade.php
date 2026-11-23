{{-- Vue d'ensemble (cahier § 4.1). --}}
@php($svg = new \App\Support\Charts\SvgChart('bar', $chart, 'Publications par mois sur 12 mois'))
<x-layouts.dashboard title="Vue d'ensemble">
    <section class="dash-hero mb-6" aria-labelledby="bienvenue">
        <div class="relative z-[1] min-w-0">
            <p class="font-mono text-[10px] uppercase tracking-[0.1em] text-amber">Vue d'ensemble · {{ now()->locale('fr')->translatedFormat('l j F') }}</p>
            <h1 id="bienvenue" class="mb-1.5 mt-2.5 text-[clamp(1.9rem,4vw,2.9rem)] font-normal tracking-[-0.04em] text-white">Bonjour {{ auth()->user()->name }}.</h1>
            <p class="max-w-[500px] text-[13px] leading-[1.6] text-white/75">Voici où en sont les publications, les consultations et les contenus récents.</p>
        </div>
        @if ($canContents)
            <a href="{{ route('dashboard.contents.create', ['type' => 'articles']) }}" class="dash-hero-action"><x-icon name="plus" class="size-4" /> Nouveau contenu</a>
        @endif
    </section>

    <ul class="grid grid-cols-1 gap-3.5 xs:grid-cols-2 xl:grid-cols-4 stagger">
        @foreach ($tiles as $tile)
            <li class="dash-stat">
                <div class="flex items-center justify-between gap-2 font-mono text-[10px] uppercase text-accent-2-ink"><span>{{ $tile['label'] }}</span><x-icon :name="$tile['icon']" class="size-[18px]" /></div>
                <p class="mb-1 mt-[18px] font-serif text-[38px] font-medium leading-none">{{ $tile['total'] }}</p>
                <p class="text-[11px] {{ $tile['month'] > 0 ? 'font-mono font-semibold text-accent-2-ink' : 'text-muted' }}">+{{ $tile['month'] }} ce mois</p>
            </li>
        @endforeach
    </ul>

    <div class="mt-6 grid grid-cols-1 gap-[18px] xl:grid-cols-[minmax(0,1.6fr)_minmax(240px,1fr)]">
        <section class="card p-5" aria-labelledby="graph">
            <p class="font-mono text-[10px] uppercase tracking-[0.1em] text-accent-2-ink">Activité</p><h2 id="graph" class="mt-1.5 text-2xl font-medium">Publications par mois</h2>
            <p class="text-sm text-muted">Tous types de publications et livres, 12 derniers mois.</p>
            <div class="mt-4">{!! $svg->render() !!}</div>
            <details class="mt-2 text-sm"><summary class="inline-flex min-h-11 cursor-pointer items-center font-semibold text-accent-2-ink">Voir les données</summary><div class="overflow-x-auto">{!! $svg->table() !!}</div></details>
        </section>
        <section class="card p-5" aria-labelledby="top">
            <p class="font-mono text-[10px] uppercase tracking-[0.1em] text-accent-2-ink">Audience</p><h2 id="top" class="mt-1.5 text-2xl font-medium">Les plus consultés</h2>
            <p class="text-sm text-muted">7 derniers jours.</p>
            @if ($top === [])
                <p class="mt-4 text-sm text-muted">Pas encore de consultation cette semaine.</p>
            @else
                <ol class="mt-3.5 divide-y divide-line">
                    @foreach ($top as $i => $t)
                        <li class="flex items-center gap-3 py-[11px]">
                            <span class="grid size-[22px] shrink-0 place-items-center rounded-full bg-accent-2/10 font-mono text-[11px] font-bold text-accent-2-ink">{{ $i + 1 }}</span>
                            <div class="min-w-0"><a href="{{ $t['url'] }}" target="_blank" rel="noopener" class="link line-clamp-2">{{ $t['title'] }}</a><p class="text-xs text-muted">{{ $t['kind'] }} · {{ $t['views'] }} vue{{ $t['views'] > 1 ? 's' : '' }}</p></div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>

    <section class="mt-6" aria-labelledby="recents">
        <h2 id="recents" class="section-label">Contenus récents</h2>
        @if ($recent->isEmpty())
            <x-empty-state icon="article" title="Aucun contenu">Les publications créées apparaîtront ici.</x-empty-state>
        @else
            <div class="card hidden overflow-hidden md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-2 text-xs uppercase tracking-wider text-muted"><tr><th class="px-4 py-3">Titre</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Auteur</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($recent as $c)
                            <tr>
                                <td class="px-4 py-3 font-semibold">@if ($canContents)<a class="hover:underline" href="{{ route('dashboard.contents.edit', ['type' => $c->type->value, 'content' => $c->id]) }}">{{ $c->title }}</a>@else{{ $c->title }}@endif</td>
                                <td class="px-4 py-3">{{ $c->type->label() }}</td>
                                <td class="px-4 py-3"><x-status-badge :status="$c->status" /></td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $c->displayDate()->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ $c->author?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <ul class="space-y-3 md:hidden">
                @foreach ($recent as $c)
                    <li class="card relative p-4">
                        <div class="mb-1 flex flex-wrap items-center gap-2"><x-status-badge :status="$c->status" /><span class="text-xs text-muted">{{ $c->type->label() }} · {{ $c->displayDate()->format('d/m/Y') }}</span></div>
                        @if ($canContents)<a class="font-semibold after:absolute after:inset-0" href="{{ route('dashboard.contents.edit', ['type' => $c->type->value, 'content' => $c->id]) }}">{{ $c->title }}</a>@else<p class="font-semibold">{{ $c->title }}</p>@endif
                        <p class="text-xs text-muted">{{ $c->author?->name ?? '—' }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.dashboard>
