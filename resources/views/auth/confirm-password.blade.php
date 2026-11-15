<x-layouts.guest title="Confirmez votre mot de passe">
    <p class="mb-5 text-sm text-muted">Cette action est sensible : confirmez votre mot de passe pour continuer (demandé au plus toutes les 15 minutes).</p>
    <form method="POST" action="{{ route('password.confirm.store') }}">
        @csrf
        <x-form.password name="password" label="Mot de passe" autocomplete="current-password" autofocus />
        <button type="submit" class="btn-primary w-full">Confirmer</button>
    </form>
</x-layouts.guest>
