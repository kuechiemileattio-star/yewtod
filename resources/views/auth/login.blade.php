<x-layouts.guest title="Connexion">
    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf
        <x-form.input name="email" type="email" label="Adresse email" autocomplete="username" required autofocus maxlength="254" />
        <x-form.password name="password" label="Mot de passe" autocomplete="current-password" />
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <label class="flex min-h-11 items-center gap-2 text-sm">
                <input type="checkbox" name="remember" class="size-5 rounded border-line text-accent-2-ink"> Se souvenir de moi
            </label>
            <a class="link inline-flex min-h-11 items-center text-sm" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
        </div>
        <button type="submit" class="btn-primary w-full">Se connecter</button>
    </form>
</x-layouts.guest>
