@if ($associated->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 no-print" aria-labelledby="associees">
        <x-site.section-title eyebrow="Dans la même rubrique" title="Publications associées" id="associees" :link="url('/works').'?categorie='.$content->type->value" link-label="Toute la rubrique" />
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($associated as $item)<x-site.work-card :content="$item" />@endforeach
        </div>
    </section>
@endif
