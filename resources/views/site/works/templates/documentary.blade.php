{{-- Gabarit « documentaire » (cahier § 2.4.3) : séries et épisodes. --}}
@php
    $meta = $content->meta;
    $isSeries = $content->type === \App\Enums\ContentType::DocumentarySeries;
    $videoUrl = $isSeries ? ($meta['trailer_url'] ?? null) : ($meta['video_url'] ?? null);
@endphp
<article class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:py-14">
    @if ($series)
        <a href="{{ $series->publicUrl() }}" class="link inline-flex min-h-11 items-center gap-1">← Retour à la série « {{ $series->title }} »</a>
    @else
        <nav aria-label="Fil d'Ariane" class="text-sm text-muted"><a class="hover:underline" href="{{ url('/works') }}">Travaux</a> / <a class="hover:underline" href="{{ url('/works').'?categorie='.$content->type->value }}">{{ $content->type->plural() }}</a></nav>
    @endif
    <header class="mt-4">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-accent-ink dark:text-accent">
            {{ $content->type->label() }}@if (! $isSeries && $content->episode_number) · Épisode {{ $content->episode_number }}@endif
            @if ($isSeries && ! empty($meta['theme_label'])) · {{ $meta['theme_label'] }}@endif
        </p>
        <h1 class="mt-2 text-4xl font-bold leading-tight sm:text-5xl">{{ $content->title }}</h1>
        <p class="mt-3 text-sm text-muted"><time datetime="{{ $content->displayDate()->toDateString() }}">{{ $content->displayDate()->translatedFormat('j F Y') }}</time> · {{ number_format($content->views_count, 0, ',', ' ') }} vues</p>
        <div class="mt-5 flex flex-wrap gap-2 no-print">
            @if ($content->downloadUrl())<a href="{{ $content->downloadUrl() }}" class="btn-primary"><x-icon name="download" class="size-4" /> Télécharger</a>@endif
            <span x-data="shareNative" data-title="{{ $content->title }}" data-url="{{ $content->publicUrl() }}">
                <button type="button" class="btn-secondary" x-on:click="share"><x-icon name="share" class="size-4" /> <span x-text="label">Partager</span></button>
            </span>
        </div>
    </header>

    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_20rem]">
        <div class="min-w-0 space-y-8">
            @if (\App\Support\VideoEmbed::parse($videoUrl))
                @include('site.works.partials.video', ['url' => $videoUrl, 'label' => $isSeries ? 'Bande-annonce : '.$content->title : $content->title])
            @elseif ($content->cover_path)
                <div class="aspect-video overflow-hidden card"><x-content-cover :content="$content" :eager="true" sizes="(min-width: 1024px) 66vw, 100vw" /></div>
            @endif
            @if ($content->summary)<p class="whitespace-pre-line font-serif text-xl leading-relaxed">{{ $content->summary }}</p>@endif
            @include('site.works.partials.sections')
            @include('site.works.partials.type-fields')
        </div>
        <aside class="space-y-6">
            @if (! $isSeries && ! empty($meta['chapters']))
                <nav class="card p-5" aria-labelledby="chapitres">
                    <h2 id="chapitres" class="text-xs font-bold uppercase tracking-wider text-muted">Chapitres</h2>
                    <ol class="mt-2 space-y-1 text-sm">
                        @foreach ($meta['chapters'] as $chapter)
                            <li class="flex gap-3 py-1"><span class="font-mono text-accent-2-ink">{{ $chapter['time'] }}</span><span>{{ $chapter['title'] }}</span></li>
                        @endforeach
                    </ol>
                </nav>
            @endif
            @if ($isSeries)
                <div class="card p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-muted">La série</p>
                    <p class="mt-2 font-serif text-3xl font-bold">{{ $episodes->count() }}</p>
                    <p class="text-sm text-muted">épisode{{ $episodes->count() > 1 ? 's' : '' }} publié{{ $episodes->count() > 1 ? 's' : '' }}</p>
                </div>
            @endif
        </aside>
    </div>

    @if ($isSeries)
        <section class="mt-14" aria-labelledby="episodes">
            <h2 id="episodes" class="text-3xl font-bold">Épisodes</h2>
            @if ($episodes->isEmpty())
                <p class="mt-3 text-muted">Les épisodes de cette série arrivent bientôt.</p>
            @else
                <ol class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($episodes as $episode)
                        @php($ev = \App\Support\VideoEmbed::parse($episode->meta['video_url'] ?? null))
                        <li class="group relative overflow-hidden card">
                            <div class="relative aspect-video bg-surface-2">
                                @if ($ev && $ev['thumb'])
                                    <img src="{{ $ev['thumb'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <x-content-cover :content="$episode" />
                                @endif
                                <span class="absolute left-3 top-3 badge bg-navy/85 text-white">Épisode {{ $episode->episode_number }}</span>
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold leading-snug"><a href="{{ $episode->publicUrl() }}" class="after:absolute after:inset-0">{{ $episode->title }}</a></h3>
                                <p class="mt-1 text-sm text-muted">{{ $episode->excerpt(100) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    @endif
    @include('site.works.partials.share-cite')
</article>
@include('site.works.partials.associated')
