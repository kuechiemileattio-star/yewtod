{{-- Accueil provisoire (lot 03) : remplacé par la vraie page d'accueil au lot 05. --}}
<x-layouts.public>
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="max-w-3xl text-4xl font-bold sm:text-5xl">{{ app(\App\Services\SiteSettings::class)->page('home')['hero_title'] }}</h1>
        <p class="mt-4 max-w-2xl text-lg text-muted">{{ app(\App\Services\SiteSettings::class)->page('home')['hero_text'] }}</p>
    </section>
</x-layouts.public>
