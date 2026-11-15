@props(['title' => 'Espace membres', 'wide' => false])
<!DOCTYPE html>
<html lang="fr" class="{{ \App\Support\Theme::htmlClass() }}">
<head>
    @include('partials.head')
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="min-h-dvh">
    <a href="#contenu" class="skip-link">Aller au contenu</a>
    <div class="flex min-h-dvh flex-col items-center justify-center px-4 py-10 sm:px-6">
        <a href="{{ url('/') }}" class="mb-8 flex min-h-11 items-center gap-2 font-serif text-2xl font-bold text-ink">
            <span class="inline-block size-3 rounded-full bg-accent" aria-hidden="true"></span>{{ config('app.name') }}
        </a>
        <main id="contenu" @class(['card w-full p-6 shadow-sm sm:p-8', 'max-w-md' => ! $wide, 'max-w-2xl' => $wide])>
            <h1 class="mb-6 text-2xl font-bold sm:text-3xl">{{ $title }}</h1>
            @if (session('status'))
                <div role="status" class="mb-5 rounded-lg border border-success/40 bg-success/10 p-3 text-sm text-ink">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div role="alert" class="mb-5 rounded-lg border border-warning bg-warning/15 p-3 text-sm text-ink">{{ session('warning') }}</div>
            @endif
            {{ $slot }}
        </main>
        <p class="mt-8 text-center text-xs text-muted">Accès réservé aux membres invités · <a class="link" href="{{ url('/') }}">Retour au site</a></p>
    </div>
    @livewireScriptConfig
</body>
</html>
