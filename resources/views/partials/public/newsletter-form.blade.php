{{-- Inscription newsletter (cahier § 2.8, § 13.1) : même réponse que l'adresse soit connue ou non. --}}
@if (session('newsletter_status'))
    <p class="rounded-lg bg-success/10 p-3 text-sm text-ink" role="status">{{ session('newsletter_status') }}</p>
@else
    <form method="POST" action="{{ route('newsletter.subscribe') }}" class="relative">
        @csrf
        <p class="mb-2 text-sm text-muted">Une lettre quand il y a du neuf. Désinscription en un clic.</p>
        <label for="nl-email" class="sr-only">Adresse email</label>
        <div class="flex flex-col gap-2 xs:flex-row">
            <input id="nl-email" type="email" name="email" required maxlength="254" autocomplete="email" placeholder="vous@exemple.fr" value="{{ old('email') }}" class="field-input min-w-0 flex-1">
            <button class="btn-primary shrink-0">S'inscrire</button>
        </div>
        @include('partials.public.antispam', ['id' => 'nl'])
        @foreach ($errors->getBag('newsletter')->all() as $message)<p class="field-error">{{ $message }}</p>@endforeach
    </form>
@endif
