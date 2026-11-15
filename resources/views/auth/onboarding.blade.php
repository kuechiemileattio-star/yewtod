<x-layouts.guest title="Activer mon compte">
    @if (! $invitation)
        <p class="text-sm">Ce lien d'invitation n'est plus valide : il a expiré, a été révoqué ou a déjà été utilisé. Demandez une nouvelle invitation à la personne qui vous a invité.</p>
    @else
        <p class="mb-5 text-sm text-muted">Bienvenue ! Vous rejoignez l'équipe avec le rôle <strong class="text-ink">{{ $invitation->role->name }}</strong> ({{ $invitation->email }}).</p>
        <form method="POST" action="{{ route('onboarding.store', ['token' => $token]) }}">
            @csrf
            <x-form.input name="name" label="Nom affiché" required maxlength="120" autocomplete="name" />
            <div class="mb-4">
                <label for="bio" class="field-label">Courte biographie <span class="font-normal text-muted">(facultatif)</span></label>
                <textarea id="bio" name="bio" rows="3" maxlength="2000" class="field-input">{{ old('bio') }}</textarea>
                @error('bio')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <x-form.password name="password" label="Mot de passe" autocomplete="new-password" :strength="true" :personal="$invitation->email" />
            <x-form.password name="password_confirmation" id="password_confirmation" label="Confirmer le mot de passe" autocomplete="new-password" />
            @error('token')<p class="field-error mb-3">{{ $message }}</p>@enderror
            <button type="submit" class="btn-primary w-full">Activer mon compte</button>
        </form>
    @endif
</x-layouts.guest>
