{{--
    Champs structurés propres à chaque type (cahier § 3.2) : enfin affichés
    parce qu'enfin saisissables (bug n° 8). Tous les liens ont été validés
    http/https à l'enregistrement et sont revérifiés ici (HttpUrl::isSafe).
--}}
@php($meta = $content->meta)
@php($safe = fn ($u) => \App\Rules\HttpUrl::isSafe($u))

@if ($content->type === \App\Enums\ContentType::ResearchNote)
    <div class="mt-10 grid gap-6 sm:grid-cols-2">
        @if (! empty($meta['progress']))
            <div class="card p-5">
                <p class="text-xs font-bold uppercase tracking-wider text-muted">Avancement</p>
                <ol class="mt-3 flex gap-2" aria-label="Avancement de la note">
                    @foreach (\App\Support\Taxonomy::NOTE_PROGRESS as $key => $label)
                        <li class="flex-1 rounded-lg px-2 py-2 text-center text-xs font-semibold {{ $meta['progress'] === $key ? 'bg-accent-2-ink text-white dark:bg-accent-2 dark:text-navy' : 'bg-surface-2 text-muted' }}" @if ($meta['progress'] === $key) aria-current="step" @endif>{{ $label }}</li>
                    @endforeach
                </ol>
            </div>
        @endif
        @if (! empty($meta['links']))
            <div class="card p-5">
                <p class="text-xs font-bold uppercase tracking-wider text-muted">Liens utiles</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($meta['links'] as $link)
                        @if ($safe($link['url'] ?? null))<li><a class="link inline-flex min-h-11 items-center gap-1" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }} <x-icon name="external" class="size-4" /></a></li>@endif
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif

@if ($content->type === \App\Enums\ContentType::Experiment && (! empty($meta['source_code_url']) || ! empty($meta['downloads'])))
    <div class="mt-10 card p-5">
        <h2 class="text-xl font-bold">Code et fichiers</h2>
        <ul class="mt-3 space-y-2">
            @if ($safe($meta['source_code_url'] ?? null))
                <li><a class="btn-secondary" href="{{ $meta['source_code_url'] }}" target="_blank" rel="noopener noreferrer"><x-icon name="external" class="size-4" /> Code source</a></li>
            @endif
            @foreach ((array) ($meta['downloads'] ?? []) as $i => $file)
                <li><a class="btn-secondary" href="{{ route('works.file', [$content->type->value, $content->slug, $i]) }}"><x-icon name="download" class="size-4" /> {{ $file['label'] }} @if (! empty($file['size']))<span class="text-xs text-muted">({{ \App\Support\Format::fileSize((int) $file['size']) }})</span>@endif</a></li>
            @endforeach
        </ul>
    </div>
@endif

@if ($content->type === \App\Enums\ContentType::DataVisualization)
    <div class="mt-10 space-y-6">
        @foreach ((array) ($meta['blocks'] ?? []) as $block)
            @include('site.works.partials.chart', ['block' => $block])
        @endforeach
        @if (! empty($meta['legend']))<div class="card p-5"><h2 class="text-xl font-bold">Légende</h2><p class="mt-2 whitespace-pre-line text-muted">{{ $meta['legend'] }}</p></div>@endif
        @if (! empty($meta['analysis']))<div class="card p-5"><h2 class="text-xl font-bold">Analyse</h2><p class="mt-2 whitespace-pre-line">{{ $meta['analysis'] }}</p></div>@endif
        <p class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
            @if (! empty($meta['data_source']))<span><strong class="text-ink">Source des données :</strong> {{ $meta['data_source'] }}</span>@endif
            @if ($safe($meta['source_code_url'] ?? null))<a class="link" href="{{ $meta['source_code_url'] }}" target="_blank" rel="noopener noreferrer">Voir le code</a>@endif
        </p>
    </div>
@endif

@if ($content->references)
    <section class="mt-10" aria-labelledby="references">
        <h2 id="references" class="text-xl font-bold">Références</h2>
        <ol class="mt-3 list-decimal space-y-2 pl-6 text-sm">
            @foreach ($content->references as $ref)
                <li>
                    @if ($safe($ref['url'] ?? null))
                        <a class="link break-words" href="{{ $ref['url'] }}" target="_blank" rel="noopener noreferrer">{{ $ref['title'] ?: $ref['url'] }}</a>
                    @else
                        {{ $ref['title'] }}
                    @endif
                </li>
            @endforeach
        </ol>
    </section>
@endif
