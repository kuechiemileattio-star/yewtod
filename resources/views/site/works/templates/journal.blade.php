{{--
    Gabarit « revue scientifique » (cahier § 2.4.2) : rapports, études,
    notes de recherche, expérimentations, visualisations.
--}}
@php
    $meta = $content->meta;
    $authors = (array) ($meta['authors'] ?? []);
    $doi = $meta['doi'] ?? null;
    $volume = trim(implode(', ', array_filter([! empty($meta['volume']) ? 'Vol. '.$meta['volume'] : null, ! empty($meta['issue']) ? 'n° '.$meta['issue'] : null])));
    $toc = (array) ($meta['toc'] ?? []);
@endphp
<article class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:py-14">
    <nav aria-label="Fil d'Ariane" class="text-sm text-muted">
        <ol class="flex flex-wrap items-center gap-1">
            <li><a class="hover:underline" href="{{ url('/') }}">Accueil</a> /</li>
            <li><a class="hover:underline" href="{{ url('/works') }}">Travaux</a> /</li>
            <li><a class="hover:underline" href="{{ url('/works').'?categorie='.$content->type->value }}">{{ $content->type->plural() }}</a></li>
        </ol>
    </nav>

    <header class="mt-6 border-b-4 border-ink pb-8">
        <p class="flex flex-wrap gap-x-3 gap-y-1 text-xs font-bold uppercase tracking-[0.2em] text-accent-2-ink">
            <span>{{ $content->type->label() }}</span>
            @if ($volume)<span class="text-muted">{{ $volume }}</span>@endif
            <time class="text-muted" datetime="{{ $content->displayDate()->toDateString() }}">{{ $content->displayDate()->translatedFormat('F Y') }}</time>
        </p>
        <h1 class="mt-3 text-3xl font-bold leading-tight sm:text-5xl">{{ $content->title }}</h1>
        <p class="mt-4 text-lg font-semibold">{{ $authors ? implode(', ', $authors) : $content->displayAuthor() }}</p>
        @if (! empty($meta['affiliation']))<p class="text-sm text-muted">{{ $meta['affiliation'] }}</p>@endif
        @if ($doi)
            <p class="mt-2 text-sm">DOI : <a class="link" href="https://doi.org/{{ $doi }}" target="_blank" rel="noopener noreferrer">{{ $doi }}</a></p>
        @endif
        <div class="mt-6 flex flex-wrap items-center gap-3">
            @if ($content->downloadUrl())
                <a href="{{ $content->downloadUrl() }}" class="btn-primary"><x-icon name="download" class="size-4" /> Télécharger le PDF</a>
            @endif
            <span x-data="copyText" data-text="{{ $content->citation() }}" class="no-print">
                <button type="button" x-on:click="copy" class="btn-secondary"><x-icon name="quote" class="size-4" /> <span x-show="!copied">Citer</span><span x-show="copied" x-cloak>Référence copiée</span></button>
            </span>
            <dl class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted">
                @if (! empty($meta['pages']))<div class="flex gap-1"><dt>Pages :</dt><dd class="font-semibold text-ink">{{ $meta['pages'] }}</dd></div>@endif
                @if (! empty($meta['version']))<div class="flex gap-1"><dt>Version :</dt><dd class="font-semibold text-ink">{{ $meta['version'] }}</dd></div>@endif
                <div class="flex gap-1"><dt>Téléchargements :</dt><dd class="font-semibold text-ink">{{ number_format($content->downloads_count, 0, ',', ' ') }}</dd></div>
                <div class="flex gap-1"><dt>Vues :</dt><dd class="font-semibold text-ink">{{ number_format($content->views_count, 0, ',', ' ') }}</dd></div>
            </dl>
        </div>
    </header>

    @if ($toc && $content->type === \App\Enums\ContentType::Report)
        <details class="card mt-8 p-5" open>
            <summary class="inline-flex min-h-11 cursor-pointer items-center text-xl font-bold">Sommaire du rapport</summary>
            <ol class="mt-3 space-y-3">
                @foreach ($toc as $entry)
                    <li class="grid grid-cols-[1fr_auto] gap-x-4">
                        <span class="font-semibold">{{ $entry['title'] }}</span>
                        @if (! empty($entry['page']))<span class="text-sm text-muted">p. {{ $entry['page'] }}</span>@endif
                        @if (! empty($entry['summary']))<p class="col-span-2 text-sm text-muted">{{ $entry['summary'] }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </details>
    @endif

    @php($excerpt = $content->type === \App\Enums\ContentType::Study ? ($meta['research_question'] ?? null) : $content->summary)
    @if ($excerpt)
        <section class="mt-10 rounded-2xl bg-surface-2 p-6" aria-labelledby="extrait">
            <h2 id="extrait" class="text-xs font-bold uppercase tracking-[0.2em] text-muted">{{ $content->type->excerptLabel() }}</h2>
            <p class="mt-3 whitespace-pre-line font-serif text-lg leading-relaxed">{{ $excerpt }}</p>
        </section>
        @if ($content->type === \App\Enums\ContentType::Study && $content->summary)
            <p class="mt-6 whitespace-pre-line text-muted">{{ $content->summary }}</p>
        @endif
    @endif

    <div class="mt-10">@include('site.works.partials.sections')</div>
    @include('site.works.partials.type-fields')
    @if ($content->tags)
        <ul class="mt-8 flex flex-wrap gap-2" aria-label="Mots-clés">
            @foreach ($content->tags as $tag)<li><a class="badge min-h-11 bg-surface-2 px-3 text-sm text-muted hover:text-ink" href="{{ url('/works').'?'.http_build_query(['q' => $tag]) }}">#{{ $tag }}</a></li>@endforeach
        </ul>
    @endif
    @include('site.works.partials.share-cite')
</article>
@include('site.works.partials.associated')
