<div class="max-w-3xl">
    <x-tabs :tabs="['profil' => 'Profil', 'mot-de-passe' => 'Mot de passe', 'sessions' => 'Sessions actives', 'securite' => 'Sécurité', 'apparence' => 'Apparence']" :current="$tab" />

    @if ($tab === 'profil')
        <div class="card p-4 sm:p-6">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="Votre photo" class="size-20 rounded-full object-cover">
                @else
                    <span class="flex size-20 items-center justify-center rounded-full bg-accent-2-ink text-2xl font-bold text-white dark:bg-accent-2 dark:text-page" aria-hidden="true">{{ $user->initial() }}</span>
                @endif
                <div>
                    <label for="avatar" class="btn-secondary cursor-pointer">Changer la photo</label>
                    <input id="avatar" type="file" wire:model="avatar" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" class="sr-only">
                    <p class="field-help">JPEG, PNG, WebP, GIF ou AVIF, 2 Mo maximum. Enregistrée immédiatement.</p>
                    <p wire:loading wire:target="avatar" class="field-help">Envoi en cours…</p>
                    @error('avatar')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <form wire:submit="saveProfile">
                <div class="mb-4">
                    <label for="name" class="field-label">Nom affiché</label>
                    <input id="name" wire:model="name" class="field-input" maxlength="120" required>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="mb-4">
                    <label for="bio" class="field-label">Biographie</label>
                    <textarea id="bio" wire:model="bio" rows="4" class="field-input" maxlength="2000"></textarea>
                    @error('bio')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <dl class="mb-4 grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-muted">Email</dt><dd class="break-all font-medium">{{ $user->email }}</dd></div>
                    <div><dt class="text-muted">Rôle</dt><dd class="font-medium">{{ $user->role->name }}</dd></div>
                    <div><dt class="text-muted">Statut</dt><dd class="font-medium">{{ $user->status->label() }}</dd></div>
                </dl>
                <button class="btn-primary" wire:loading.attr="disabled">Enregistrer</button>
            </form>
        </div>
    @endif

    @if ($tab === 'mot-de-passe')
        <form wire:submit="changePassword" class="card p-4 sm:p-6">
            <x-form.password name="current_password" label="Mot de passe actuel" wire:model="current_password" />
            <x-form.password name="password" label="Nouveau mot de passe" autocomplete="new-password" :strength="true" :personal="$user->name.' '.$user->email" wire:model="password" />
            <x-form.password name="password_confirmation" id="password_confirmation" label="Confirmer le nouveau mot de passe" autocomplete="new-password" wire:model="password_confirmation" />
            <p class="mb-4 text-xs text-muted">Changer votre mot de passe déconnecte vos autres appareils.</p>
            <button class="btn-primary" wire:loading.attr="disabled">Changer le mot de passe</button>
        </form>
    @endif

    @if ($tab === 'sessions')
        <ul class="mb-6 space-y-3" role="list">
            @foreach ($sessions as $s)
                <li class="card flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold">{{ $s->device }} @if ($s->current)<span class="badge ml-1 bg-success/15 text-success">cet appareil</span>@endif</p>
                        <p class="text-sm text-muted">IP {{ $s->ip ?? 'inconnue' }}</p>
                    </div>
                    <p class="text-sm text-muted">Active {{ $s->last_active->diffForHumans() }}</p>
                </li>
            @endforeach
        </ul>
        <form wire:submit="logoutOtherDevices" class="card p-4 sm:p-6">
            <h2 class="mb-2 text-lg font-bold">Déconnecter les autres appareils</h2>
            <x-form.password name="sessionPassword" id="sessionPassword" label="Votre mot de passe" wire:model="sessionPassword" />
            <button class="btn-secondary">Déconnecter les autres appareils</button>
        </form>
    @endif

    @if ($tab === 'securite')
        <div class="card p-4 sm:p-6">
            <h2 class="mb-2 text-lg font-bold">Double authentification</h2>
            <p class="mb-4 text-sm text-muted">Activée le {{ $user->two_factor_confirmed_at?->setTimezone(config('app.display_timezone'))->translatedFormat('j F Y') }}. Elle est obligatoire et ne peut pas être supprimée, seulement transférée sur un nouvel appareil.</p>
            <a href="{{ route('security.two-factor') }}" class="btn-secondary">Gérer la double authentification et les codes de secours</a>
        </div>
    @endif

    @if ($tab === 'apparence')
        <div class="card p-4 sm:p-6">
            <h2 class="mb-1 text-lg font-bold">Thème</h2>
            <p class="mb-4 text-sm text-muted">Votre choix est enregistré sur votre compte et vous suit d'un appareil à l'autre.</p>
            <div class="grid gap-2 sm:grid-cols-3" role="radiogroup" aria-label="Thème">
                @foreach (['light' => ['Clair', 'sun'], 'dark' => ['Sombre', 'moon'], 'system' => ['Système', 'monitor']] as $value => [$label, $icon])
                    <button type="button" role="radio" aria-checked="{{ $user->theme_preference === $value ? 'true' : 'false' }}" wire:click="setTheme('{{ $value }}')"
                        @class(['flex min-h-14 items-center justify-center gap-2 rounded-xl border-2 text-sm font-semibold', 'border-accent' => $user->theme_preference === $value, 'border-line' => $user->theme_preference !== $value])>
                        <x-icon :name="$icon" /> {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
