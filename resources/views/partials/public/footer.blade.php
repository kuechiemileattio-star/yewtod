{{-- Pied de page de la référence (D78) : bandeau d'appel à la discussion, puis base bleu nuit en quatre colonnes. --}}
<footer class="mt-20 text-on-navy">
    <section class="relative overflow-hidden bg-gradient-to-r from-navy via-navy-2 to-[#1F5A72] dark:to-[#173447]">
        <span class="pointer-events-none absolute -right-24 -top-40 hidden size-[420px] rounded-full border border-amber/40 shadow-[0_0_0_26px_rgb(245_185_77_/_0.08),0_0_0_52px_rgb(245_185_77_/_0.05)] md:block" aria-hidden="true"></span>
        <div class="relative mx-auto grid max-w-[1120px] gap-6 px-6 py-8 md:grid-cols-[1fr_auto] md:items-end">
            <div>
                <p class="font-mono text-[11px] uppercase tracking-[0.14em] text-amber">Une idée à partager ?</p>
                <h2 class="mt-2 max-w-xl text-[clamp(1.6rem,3vw,2rem)] leading-tight text-white">{{ $footer['cta_title'] }}</h2>
                <p class="mt-2 text-sm text-on-navy-muted">{{ $footer['cta_text'] }}</p>
            </div>
            <a href="{{ url('/collaborations') }}" class="btn shrink-0 justify-self-start bg-amber px-5 font-mono text-xs font-normal text-[#172238] hover:brightness-105 md:justify-self-end">
                <x-icon name="mail" class="size-4" /> Démarrer une conversation <x-icon name="arrow-up-right" class="size-4" />
            </a>
        </div>
    </section>
    <div class="bg-navy">
        <div class="mx-auto grid max-w-[1120px] grid-cols-2 gap-x-6 gap-y-10 px-6 pb-8 pt-10 lg:grid-cols-[1.3fr_1fr_1fr_1.2fr]">
            <div class="col-span-2 lg:col-span-1">
                <p class="font-serif text-[30px] font-medium leading-none tracking-[-0.03em] text-white">{{ $siteName }}</p>
                <p class="mt-4 max-w-[300px] text-[13.5px] leading-relaxed text-on-navy-muted">{{ $footer['about'] }}</p>
            </div>
            <nav aria-label="Explorer le site">
                <h2 class="mb-3 font-mono text-[11px] font-normal uppercase tracking-[0.14em] text-teal-on-navy">Explorer</h2>
                <ul class="text-sm">
                    @foreach (['/' => 'Accueil', '/works' => 'Travaux', '/meet' => 'Meet Yewtod', '/books' => 'Livres', '/collaborations' => 'Collaborations'] as $href => $label)
                        <li><a class="inline-flex min-h-9 items-center text-on-navy/90 transition hover:translate-x-1 hover:text-amber" href="{{ url($href) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>
            <nav aria-label="Types de publications">
                <h2 class="mb-3 font-mono text-[11px] font-normal uppercase tracking-[0.14em] text-teal-on-navy">Travaux</h2>
                <ul class="text-sm">
                    @foreach (\App\Enums\ContentType::cases() as $type)
                        <li><a class="inline-flex min-h-9 items-center text-on-navy/90 transition hover:translate-x-1 hover:text-amber" href="{{ url('/works').'?categorie='.$type->value }}">{{ $type->plural() }}</a></li>
                    @endforeach
                </ul>
            </nav>
            <div class="col-span-2 lg:col-span-1">
                <h2 class="mb-3 font-mono text-[11px] font-normal uppercase tracking-[0.14em] text-teal-on-navy">Rester informé</h2>
                @if (\Illuminate\Support\Facades\Route::has('newsletter.subscribe'))
                    @if (session('newsletter_status'))
                        <p class="rounded-md bg-white/10 p-3 text-sm text-white" role="status">{{ session('newsletter_status') }}</p>
                    @else
                        <form method="POST" action="{{ route('newsletter.subscribe') }}" class="relative">
                            @csrf
                            <p class="mb-3 text-[12.5px] leading-normal text-on-navy-muted">Un e-mail occasionnel quand un nouveau travail est publié. Désinscription en un clic.</p>
                            <label for="nl-email" class="sr-only">Adresse email</label>
                            <input id="nl-email" type="email" name="email" required maxlength="254" autocomplete="email" placeholder="vous@exemple.com" value="{{ old('email') }}"
                                   class="block min-h-11 w-full rounded-md border border-white/20 bg-white/5 px-4 text-sm text-white placeholder:text-on-navy-muted focus:border-amber focus:ring-amber/30">
                            <button class="btn-primary mt-2.5 w-full rounded-md"><x-icon name="send" class="size-4" /> S'inscrire</button>
                            @include('partials.public.antispam', ['id' => 'nl'])
                            @foreach ($errors->getBag('newsletter')->all() as $message)<p class="mt-2 text-sm font-medium text-[#F7B8A8]">{{ $message }}</p>@endforeach
                        </form>
                    @endif
                @endif
                {{-- Liens revérifiés à l'affichage (défense en profondeur : seuls http/https). --}}
                @php($socials = $socials->filter(fn ($s) => \App\Rules\HttpUrl::isSafe($s->url)))
                @if ($socials->isNotEmpty())
                    <ul class="mt-5 flex flex-wrap gap-1" aria-label="Réseaux sociaux">
                        @foreach ($socials as $social)
                            <li><a href="{{ $social->url }}" target="_blank" rel="noopener noreferrer" class="btn-on-navy border border-white/15" aria-label="{{ $social->platform }} (nouvelle fenêtre)"><x-social-icon :name="$social->icon()" /></a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        <div class="mx-auto flex max-w-[1120px] flex-col gap-3 border-t border-white/10 px-6 py-5 text-xs text-on-navy-muted sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} {{ $siteName }} · Aucun cookie de suivi.</p>
            <ul class="flex flex-wrap gap-x-5 gap-y-1">
                <li><a class="inline-flex min-h-9 items-center hover:text-white" href="{{ url('/mentions-legales') }}">Mentions légales</a></li>
                <li><a class="inline-flex min-h-9 items-center hover:text-white" href="{{ url('/confidentialite') }}">Politique de confidentialité</a></li>
                <li><button type="button" x-data="backToTop" x-on:click="up" class="inline-flex min-h-9 items-center gap-1.5 hover:text-white"><x-icon name="arrow-up" class="size-3.5" /> Retour en haut</button></li>
            </ul>
        </div>
    </div>
</footer>
