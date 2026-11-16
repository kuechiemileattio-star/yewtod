{{-- Meet Yewtod (cahier § 2.5, § 5.9) : contenus éditables, valeurs par défaut sinon. --}}
<x-layouts.public title="Meet Yewtod" :description="$meet['hero_text']">
    <section class="mx-auto max-w-5xl px-4 pb-10 pt-12 sm:px-6 lg:pt-20">
        <p class="text-xs font-bold uppercase tracking-[0.25em] text-accent-ink dark:text-accent">Le projet</p>
        <h1 class="mt-3 text-4xl font-bold leading-tight sm:text-6xl">{{ $meet['hero_title'] }}</h1>
        <p class="mt-6 max-w-3xl text-xl text-muted">{{ $meet['hero_text'] }}</p>
    </section>

    <section class="bg-surface py-14" aria-labelledby="methode">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-site.section-title eyebrow="Méthode" title="Comment je travaille" id="methode" />
            <ol class="grid gap-6 md:grid-cols-3">
                @foreach ($meet['how_i_work'] as $i => $card)
                    <li class="card bg-page p-6" x-data="reveal" x-bind:class="revealClass">
                        <span class="inline-flex size-11 items-center justify-center rounded-full bg-accent text-lg font-bold text-navy">{{ $i + 1 }}</span>
                        <h3 class="mt-4 text-xl font-bold">{{ $card['title'] }}</h3>
                        <p class="mt-2 text-muted">{{ $card['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-14 sm:px-6" aria-labelledby="faq">
        <x-site.section-title eyebrow="FAQ" title="Le projet en questions" id="faq" />
        <div class="divide-y divide-line border-y border-line">
            @foreach ($meet['faq'] as $item)
                <details class="group py-2">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 py-2 font-serif text-lg font-bold">
                        {{ $item['q'] }}
                        <x-icon name="plus" class="size-5 shrink-0 transition group-open:rotate-45" />
                    </summary>
                    <p class="pb-4 text-muted">{{ $item['a'] }}</p>
                </details>
            @endforeach
        </div>
    </section>

    <section class="bg-surface-2 py-14" aria-labelledby="fondateur">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 md:grid-cols-[18rem_1fr] md:items-start lg:px-8">
            <div class="mx-auto w-full max-w-xs overflow-hidden rounded-3xl bg-surface md:mx-0">
                @if (! empty($meet['portrait_path']))
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($meet['portrait_path']) }}" alt="Portrait de {{ $meet['founder_name'] }}" class="aspect-[4/5] w-full object-cover" loading="lazy">
                @else
                    <div class="flex aspect-[4/5] items-center justify-center bg-accent-2/20 font-serif text-7xl font-bold text-accent-2-ink" aria-hidden="true">{{ mb_strtoupper(mb_substr($meet['founder_name'], 0, 1)) }}</div>
                @endif
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-accent-ink dark:text-accent">Le fondateur</p>
                <h2 id="fondateur" class="mt-2 text-4xl font-bold">{{ $meet['founder_name'] }}</h2>
                <p class="mt-5 text-lg">{{ $meet['bio_1'] }}</p>
                <p class="mt-4 text-muted">{{ $meet['bio_2'] }}</p>
                @if (! empty($meet['expertise']))
                    <h3 class="mt-8 text-xs font-bold uppercase tracking-wider text-muted">Domaines d'expertise</h3>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ((array) $meet['expertise'] as $domain)<li class="badge min-h-9 bg-surface px-4 text-sm text-ink">{{ $domain }}</li>@endforeach
                    </ul>
                @endif
                <a href="{{ url('/collaborations') }}" class="btn-primary mt-8">Proposer une collaboration <x-icon name="arrow-right" class="size-4" /></a>
            </div>
        </div>
    </section>
</x-layouts.public>
