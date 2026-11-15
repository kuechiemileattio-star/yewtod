<x-layouts.guest title="Nouveau mot de passe">
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-form.input name="email" type="email" label="Adresse email" autocomplete="username" :value="$request->query('email')" required maxlength="254" />
        <x-form.password name="password" label="Nouveau mot de passe" autocomplete="new-password" :strength="true" :personal="$request->query('email')" />
        <x-form.password name="password_confirmation" id="password_confirmation" label="Confirmer le mot de passe" autocomplete="new-password" />
        <button type="submit" class="btn-primary w-full">Enregistrer</button>
    </form>
    <p class="mt-4 text-xs text-muted">La double authentification vous sera demandée à la connexion.</p>
</x-layouts.guest>
