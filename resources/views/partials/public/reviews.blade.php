{{-- Avis des lecteurs (cahier § 2.8, § 5.4) : approuvés uniquement + formulaire pré-modéré. --}}
@php
    $isBook = $reviewable instanceof \App\Models\Book;
    $open = \App\Services\ReviewService::accepts($reviewable);
    $approved = \App\Models\Review::query()->approved()->where('reviewable_type', $reviewable->getMorphClass())->where('reviewable_id', $reviewable->getKey())->latest()->limit(50)->get();
    $bag = $errors->getBag('review');
@endphp
@if ($open)
    <section id="avis" class="mt-12 scroll-mt-24 no-print" aria-labelledby="titre-avis">
        <h2 id="titre-avis" class="text-2xl font-bold">Avis des lecteurs <span class="text-base font-normal text-muted">({{ $approved->count() }})</span></h2>
        @if ($approved->isEmpty())
            <p class="mt-2 text-muted">Aucun avis publié pour l'instant. Soyez le premier à partager le vôtre.</p>
        @else
            <ul class="mt-4 space-y-4">
                @foreach ($approved as $review)
                    <li class="card p-5">
                        <p class="flex flex-wrap items-baseline justify-between gap-2"><strong>{{ $review->name }}</strong><time class="text-xs text-muted" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->setTimezone(config('app.display_timezone'))->translatedFormat('j F Y') }}</time></p>
                        <p class="mt-2 whitespace-pre-line">{{ $review->body }}</p>
                    </li>
                @endforeach
            </ul>
        @endif

        @if (session('review_sent'))
            <p class="mt-6 rounded-lg bg-success/10 p-4 text-sm" role="status">Merci ! Votre avis a bien été reçu : il sera publié après relecture.</p>
        @else
            <form method="POST" action="{{ $isBook ? route('reviews.book', $reviewable->slug) : route('reviews.work', [$reviewable->type->value, $reviewable->slug]) }}" class="card relative mt-6 p-5">
                @csrf
                <h3 class="text-lg font-bold">Laisser un avis</h3>
                <p class="mb-4 text-sm text-muted">Votre avis sera publié après relecture. Seul le nom indiqué est affiché.</p>
                <div class="mb-4">
                    <label for="rv-name" class="field-label">Nom ou pseudonyme</label>
                    <input id="rv-name" name="name" required maxlength="80" value="{{ old('name') }}" class="field-input" autocomplete="name">
                    @if ($bag->has('name'))<p class="field-error">{{ $bag->first('name') }}</p>@endif
                </div>
                <div class="mb-4">
                    <label for="rv-body" class="field-label">Votre avis <span class="font-normal text-muted">(2 000 caractères maximum)</span></label>
                    <textarea id="rv-body" name="body" required maxlength="2000" rows="5" class="field-input">{{ old('body') }}</textarea>
                    @if ($bag->has('body'))<p class="field-error">{{ $bag->first('body') }}</p>@endif
                </div>
                @include('partials.public.antispam', ['id' => 'rv'])
                @if ($bag->has('form'))<p class="field-error">{{ $bag->first('form') }}</p>@endif
                <button class="btn-primary mt-4">Envoyer mon avis</button>
            </form>
        @endif
    </section>
@endif
