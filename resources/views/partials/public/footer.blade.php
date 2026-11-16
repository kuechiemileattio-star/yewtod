<footer class="mt-16 border-t border-line bg-surface">
    <section class="bg-ink text-page dark:bg-surface-2 dark:text-ink">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-12 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <div class="max-w-xl">
                <h2 class="text-3xl font-bold">{{ $footer['cta_title'] }}</h2>
                <p class="mt-2 opacity-85">{{ $footer['cta_text'] }}</p>
            </div>
            <a href="{{ url('/collaborations') }}" class="btn shrink-0 bg-accent px-6 text-navy hover:brightness-110">Proposer une collaboration <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </section>
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div>
            <p class="font-serif text-xl font-bold">{{ $siteName }}</p>
            <p class="mt-2 text-sm text-muted">{{ $footer['about'] }}</p>
            {{-- Liens revérifiés à l'affichage (défense en profondeur : seuls http/https). --}}
            @php($socials = $socials->filter(fn ($s) => \App\Rules\HttpUrl::isSafe($s->url)))
            @if ($socials->isNotEmpty())
                <ul class="mt-4 flex flex-wrap gap-1" aria-label="Réseaux sociaux">
                    @foreach ($socials as $social)
                        <li><a href="{{ $social->url }}" target="_blank" rel="noopener noreferrer" class="btn-ghost min-w-11 px-2" aria-label="{{ $social->platform }} (nouvelle fenêtre)"><x-social-icon :name="$social->icon()" /></a></li>
                    @endforeach
                </ul>
            @endif
        </div>
        <nav aria-label="Liens du site">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-muted">Le site</h2>
            <ul class="grid grid-cols-1 gap-x-4 text-sm xs:grid-cols-2 md:grid-cols-1">
                @foreach (['/works' => 'Travaux', '/meet' => 'Meet Yewtod', '/books' => 'Livres', '/collaborations' => 'Collaborations', '/mentions-legales' => 'Mentions légales', '/confidentialite' => 'Politique de confidentialité'] as $href => $label)
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ url($href) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </nav>
        <nav aria-label="Types de publications">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-muted">Publications</h2>
            <ul class="grid grid-cols-1 gap-x-4 text-sm xs:grid-cols-2 md:grid-cols-1">
                @foreach (\App\Enums\ContentType::cases() as $type)
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ url('/works').'?categorie='.$type->value }}">{{ $type->plural() }}</a></li>
                @endforeach
            </ul>
        </nav>
        <div>
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-muted">Newsletter</h2>
            @if (\Illuminate\Support\Facades\Route::has('newsletter.subscribe'))
                @include('partials.public.newsletter-form')
            @endif
            <button type="button" x-data="backToTop" x-on:click="up" class="btn-secondary mt-6">
                <x-icon name="arrow-up" class="size-4" /> Retour en haut
            </button>
        </div>
    </div>
    <p class="border-t border-line px-4 py-6 text-center text-xs text-muted">© {{ now()->year }} {{ $siteName }} · Aucun cookie de suivi.</p>
</footer>
