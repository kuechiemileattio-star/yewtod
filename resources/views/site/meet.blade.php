{{-- Meet Yewtod (cahier § 2.5, § 5.9 ; design de référence D78) : contenus éditables, valeurs par défaut sinon. --}}
@php($icons = ['book', 'network', 'lightbulb'])
<x-layouts.public title="Meet Yewtod" :description="$meet['hero_text']">
    <div class="mx-auto max-w-[1060px] px-6 pb-[110px] pt-12 sm:pt-[72px]">
        <header class="grid items-center gap-[30px] pb-[70px] md:min-h-[430px] md:grid-cols-[1.25fr_.75fr] md:gap-[60px]">
            <div class="page-enter">
                <p class="kicker mb-5 text-xs">Le projet</p>
                <h1 class="mb-5 text-[clamp(2.4rem,5vw,4rem)] leading-[1.02]">{{ $meet['hero_title'] }}</h1>
                <p class="max-w-[600px] text-[17px] leading-[1.7] text-muted">{{ $meet['hero_text'] }}</p>
            </div>
            <div class="meet-mark" aria-hidden="true">
                <svg width="57" height="22" viewBox="0 0 26 10"><circle cx="3" cy="5" r="2.6" fill="currentColor"/><line x1="5.5" y1="5" x2="20.5" y2="5" stroke="currentColor" stroke-width="1" opacity=".55"/><circle cx="23" cy="5" r="2.6" fill="none" stroke="var(--teal-on-navy)" stroke-width="1.3"/></svg>
                <span>Recherche<br>appliquée</span>
                <x-icon name="arrow-up-right" class="size-[22px] rotate-90" />
            </div>
        </header>

        <section class="pb-[86px]" aria-labelledby="methode">
            <h2 id="methode" class="section-label">Comment je travaille</h2>
            <ol class="grid gap-3.5 md:grid-cols-3">
                @foreach ($meet['how_i_work'] as $i => $card)
                    <li class="meet-card reveal-delay-{{ min($i, 3) }}" x-data="reveal" x-bind:class="revealClass">
                        <div class="meet-card-icon"><x-icon :name="$icons[$i % 3]" class="size-5" /><span class="sr-only">Étape {{ $i + 1 }}</span></div>
                        <h3 class="mb-2 mt-7 text-2xl font-medium">{{ $card['title'] }}</h3>
                        <p class="text-[13px] leading-[1.65] text-muted">{{ $card['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="pt-2.5" aria-labelledby="faq">
            <h2 id="faq" class="section-label">Le projet en questions</h2>
            <dl class="mb-20 flex flex-col gap-9">
                @foreach ($meet['faq'] as $item)
                    <div class="qa-row" x-data="reveal" x-bind:class="revealClass">
                        <dt class="font-serif text-[21px] font-semibold">{{ $item['q'] }}</dt>
                        <dd class="text-[15.5px] leading-[1.65] text-muted">{{ $item['a'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section aria-labelledby="fondateur" x-data="reveal" x-bind:class="revealClass">
            <p class="section-label" aria-hidden="true">Le fondateur</p>
            <div class="founder-panel grid gap-8 md:grid-cols-[260px_1fr] md:gap-12">
                <div class="founder-portrait mx-auto md:mx-0">
                    @if (! empty($meet['portrait_path']))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($meet['portrait_path']) }}" alt="Portrait de {{ $meet['founder_name'] }}" class="size-full object-cover" loading="lazy">
                    @else
                        <span aria-hidden="true">{{ mb_strtoupper(mb_substr($meet['founder_name'], 0, 1)) }}</span>
                    @endif
                </div>
                <div>
                    <h2 id="fondateur" class="mb-4 text-2xl font-semibold"><span class="sr-only">Le fondateur : </span>{{ $meet['founder_name'] }}</h2>
                    <p class="mb-4 text-[15.5px] leading-[1.7] text-muted">{{ $meet['bio_1'] }}</p>
                    <p class="mb-5 text-[15.5px] leading-[1.7] text-muted">{{ $meet['bio_2'] }}</p>
                    @if (! empty($meet['expertise']))
                        <h3 class="sr-only">Domaines d'expertise</h3>
                        <ul class="flex flex-wrap gap-2">
                            @foreach ((array) $meet['expertise'] as $domain)<li class="pill">{{ $domain }}</li>@endforeach
                        </ul>
                    @endif
                    <a href="{{ url('/collaborations') }}" class="btn-primary mt-7">Proposer une collaboration <x-icon name="arrow-right" class="size-4" /></a>
                </div>
            </div>
        </section>
    </div>
</x-layouts.public>
