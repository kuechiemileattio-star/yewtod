{{-- Bloc de graphique d'une visualisation : SVG rendu côté serveur + tableau de données accessible. --}}
@php($chart = new \App\Support\Charts\SvgChart((string) ($block['chart_type'] ?? 'bar'), (array) ($block['data'] ?? []), (string) ($block['title'] ?? '')))
<figure class="card p-5 transition duration-700" x-data="reveal" x-bind:class="revealClass">
    @if (! empty($block['title']))<figcaption class="text-xl font-bold">{{ $block['title'] }}</figcaption>@endif
    @if (! empty($block['description']))<p class="mt-1 text-sm text-muted">{{ $block['description'] }}</p>@endif
    @if ($chart->isEmpty())
        <p class="mt-4 text-sm text-muted">Données indisponibles pour ce graphique.</p>
    @else
        {{-- SVG construit par SvgChart : chaque texte issu des données y est échappé. --}}
        <div class="mt-4">{!! $chart->render() !!}</div>
        {!! $chart->legend() !!}
        <details class="mt-3 text-sm">
            <summary class="inline-flex min-h-11 cursor-pointer items-center font-semibold text-accent-2-ink">Voir les données</summary>
            <div class="overflow-x-auto">{!! $chart->table() !!}</div>
        </details>
    @endif
</figure>
