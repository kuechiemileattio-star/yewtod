{{-- Vraie page 404 (cahier § 10 P1, bug n° 14) : recherche + dernières publications. --}}
@php($latest = \App\Http\Controllers\Site\PageController::latestForNotFound())
<x-layouts.public title="Page introuvable" :noindex="true">
    <section class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 lg:py-24">
        <p class="font-serif text-7xl font-bold text-accent-ink dark:text-accent sm:text-8xl" aria-hidden="true">404</p>
        <h1 class="mt-4 text-3xl font-bold sm:text-4xl">Cette page est introuvable</h1>
        <p class="mt-3 text-muted">Le lien est peut-être ancien, ou la publication a été retirée. Cherchez parmi nos travaux :</p>
        <form method="GET" action="{{ url('/works') }}" role="search" class="mx-auto mt-8 flex max-w-xl flex-col gap-2 sm:flex-row">
            <label for="q404" class="sr-only">Rechercher une publication</label>
            <input id="q404" name="q" type="search" maxlength="100" placeholder="Rechercher une publication…" class="field-input flex-1">
            <button class="btn-primary">Rechercher</button>
        </form>
        <a href="{{ url('/') }}" class="link mt-6 inline-flex min-h-11 items-center">Retour à l'accueil</a>
    </section>
    @if ($latest->isNotEmpty())
        <section class="mx-auto max-w-3xl px-4 pb-16 sm:px-6" aria-labelledby="recentes404">
            <h2 id="recentes404" class="text-xl font-bold">Dernières publications</h2>
            <ul class="mt-3 divide-y divide-line border-y border-line">
                @foreach ($latest as $item)
                    <li><a href="{{ $item->publicUrl() }}" class="flex min-h-11 items-baseline justify-between gap-4 py-3 hover:underline"><span class="font-serif font-bold">{{ $item->title }}</span><span class="shrink-0 text-xs text-muted">{{ $item->type->label() }}</span></a></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.public>
