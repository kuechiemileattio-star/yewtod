{{-- Vue d'ensemble (cahier § 4.1). --}}
@php($svg = new \App\Support\Charts\SvgChart('bar', $chart, 'Publications par mois sur 12 mois'))
<x-layouts.dashboard title="Vue d'ensemble">
    <x-page-header title="Vue d'ensemble" :subtitle="'Bonjour '.auth()->user()->name.'.'">
        @if ($canContents)
            <x-slot:actions><a href="{{ route('dashboard.contents.create', ['type' => 'articles']) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Nouveau contenu</a></x-slot:actions>
        @endif
    </x-page-header>

    <ul class="grid grid-cols-1 gap-4 xs:grid-cols-2 xl:grid-cols-4">
        @foreach ($tiles as $tile)
            <li class="card p-5">
                <div class="flex items-center justify-between text-muted"><span class="text-sm font-semibold">{{ $tile['label'] }}</span><x-icon :name="$tile['icon']" /></div>
                <p class="mt-2 font-serif text-4xl font-bold">{{ $tile['total'] }}</p>
                <p class="mt-1 text-sm {{ $tile['month'] > 0 ? 'text-success' : 'text-muted' }}">+{{ $tile['month'] }} ce mois</p>
            </li>
        @endforeach
    </ul>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[2fr_1fr]">
        <section class="card p-5" aria-labelledby="graph">
            <h2 id="graph" class="text-lg font-bold">Publications par mois</h2>
            <p class="text-sm text-muted">Tous types de publications et livres, 12 derniers mois.</p>
            <div class="mt-4">{!! $svg->render() !!}</div>
            <details class="mt-2 text-sm"><summary class="inline-flex min-h-11 cursor-pointer items-center font-semibold text-accent-2-ink">Voir les données</summary><div class="overflow-x-auto">{!! $svg->table() !!}</div></details>
        </section>
        <section class="card p-5" aria-labelledby="top">
            <h2 id="top" class="text-lg font-bold">Les plus consultés</h2>
            <p class="text-sm text-muted">7 derniers jours.</p>
            @if ($top === [])
                <p class="mt-4 text-sm text-muted">Pas encore de consultation cette semaine.</p>
            @else
                <ol class="mt-4 space-y-3">
                    @foreach ($top as $i => $t)
                        <li class="flex gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent/15 font-bold text-accent-ink dark:text-accent">{{ $i + 1 }}</span>
                            <div class="min-w-0"><a href="{{ $t['url'] }}" target="_blank" rel="noopener" class="link line-clamp-2">{{ $t['title'] }}</a><p class="text-xs text-muted">{{ $t['kind'] }} · {{ $t['views'] }} vue{{ $t['views'] > 1 ? 's' : '' }}</p></div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>

    <section class="mt-6" aria-labelledby="recents">
        <h2 id="recents" class="mb-3 text-lg font-bold">Contenus récents</h2>
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
