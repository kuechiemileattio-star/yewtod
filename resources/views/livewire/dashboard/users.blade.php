<div>
    @php
        $tabs = [];
        if ($me->hasPermission('manage_users')) { $tabs['membres'] = 'Membres'; }
        $tabs['invitations'] = 'Invitations';
        if ($me->hasPermission('manage_users')) { $tabs['roles'] = 'Rôles et permissions'; }
    @endphp
    <x-tabs :tabs="$tabs" :current="$tab" />

    @if ($tab === 'membres')
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <label class="relative block w-full sm:max-w-xs">
                <span class="sr-only">Rechercher un membre</span>
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nom ou email" class="field-input pl-9" maxlength="100">
            </label>
            <p class="text-sm text-muted">{{ $members->total() }} membre(s)</p>
        </div>

        {{-- Liste en cartes sur mobile, en lignes alignées dès la tablette (décision 14). --}}
        <ul class="space-y-3" role="list">
            @foreach ($members as $member)
                @php($self = $member->is($me))
                @php($manageable = ! $self && $canManage($member))
                <li class="card p-4" wire:key="u-{{ $member->id }}">
                    <div class="grid gap-3 md:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_auto_auto] md:items-center">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $member->name }} @if ($self)<span class="badge ml-1 bg-surface-2 text-muted">vous</span>@endif</p>
                            <p class="truncate text-sm text-muted">{{ $member->email }}</p>
                        </div>
                        <div>
                            <label class="sr-only" for="role-{{ $member->id }}">Rôle de {{ $member->name }}</label>
                            @if ($manageable)
                                <select id="role-{{ $member->id }}" class="field-input" x-data="wireCall" data-method="changeRole" data-arg="{{ $member->id }}" x-on:change="withValue">
                                    @foreach ($assignable as $r)
                                        <option value="{{ $r->id }}" @selected($r->id === $member->role_id)>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <p class="text-sm" id="role-{{ $member->id }}">{{ $member->role->name }} <span class="text-xs text-muted">(verrouillé)</span></p>
                            @endif
                        </div>
                        <div>
                            @if ($manageable)
                                <button type="button" wire:click="toggleStatus({{ $member->id }})" role="switch" aria-checked="{{ $member->isActive() ? 'true' : 'false' }}"
                                        class="flex min-h-11 items-center gap-2 text-sm" aria-label="Statut de {{ $member->name }}">
                                    <span @class(['relative inline-flex h-6 w-11 rounded-full transition', 'bg-success' => $member->isActive(), 'bg-line' => ! $member->isActive()])>
                                        <span @class(['absolute top-0.5 size-5 rounded-full bg-white shadow transition', 'left-5' => $member->isActive(), 'left-0.5' => ! $member->isActive()])></span>
                                    </span>
                                    {{ $member->status->label() }}
                                </button>
                            @else
                                <span class="badge bg-surface-2">{{ $member->status->label() }}</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-1 md:justify-end">
                            @if ($manageable && $me->isSuperAdmin() && $member->hasTwoFactorEnabled())
                                <x-confirm-action method="resetTwoFactor" :arg="$member->id" label="Réinitialiser la 2FA" button-class="btn-ghost text-sm"
                                    title="Réinitialiser la double authentification ?" confirm="Réinitialiser">
                                    {{ $member->name }} devra configurer un nouvel appareil à sa prochaine connexion. Un email l'en informera.
                                </x-confirm-action>
                            @endif
                            @if ($manageable)
                                <x-confirm-action method="deleteUser" :arg="$member->id" label="Supprimer" title="Supprimer ce compte ?" confirm="Supprimer définitivement">
                                    Le compte de {{ $member->name }} sera supprimé. Ses contenus seront conservés sans auteur.
                                </x-confirm-action>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $members->links() }}</div>
    @endif

    @if ($tab === 'invitations')
        @if ($me->hasPermission('invite_users'))
            <form wire:submit="invite" class="card mb-6 grid gap-3 p-4 sm:grid-cols-[1fr_minmax(0,14rem)_auto] sm:items-end">
                <div>
                    <label for="inviteEmail" class="field-label">Adresse email</label>
                    <input id="inviteEmail" type="email" wire:model="inviteEmail" class="field-input" maxlength="254" required>
                    @error('inviteEmail')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="inviteRole" class="field-label">Rôle</label>
                    <select id="inviteRole" wire:model="inviteRole" class="field-input" required>
                        <option value="">Choisir…</option>
                        @foreach ($assignable as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
                    </select>
                    @error('inviteRole')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <button class="btn-primary" wire:loading.attr="disabled">Inviter</button>
            </form>
        @endif
        <ul class="space-y-3" role="list">
            @forelse ($invitations as $inv)
                @php($status = $inv->displayStatus())
                <li class="card flex flex-col gap-2 p-4 sm:flex-row sm:items-center" wire:key="i-{{ $inv->id }}">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $inv->email }}</p>
                        <p class="text-sm text-muted">{{ $inv->role->name }} · par {{ $inv->inviter?->name ?? 'membre supprimé' }} · expire le {{ $inv->expires_at->setTimezone(config('app.display_timezone'))->format('d/m/Y') }}</p>
                    </div>
                    <span @class(['badge', 'bg-warning/20 text-warning-ink' => $status->value === 'pending', 'bg-success/15 text-success' => $status->value === 'accepted', 'bg-surface-2 text-muted' => in_array($status->value, ['expired', 'revoked'])])>{{ $status->label() }}</span>
                    @if ($status->value === 'pending' && $me->can('revoke', $inv))
                        <x-confirm-action method="revoke" :arg="$inv->id" label="Révoquer" title="Révoquer cette invitation ?" confirm="Révoquer">Le lien envoyé à {{ $inv->email }} ne fonctionnera plus.</x-confirm-action>
                    @endif
                </li>
            @empty
                <li class="card p-6 text-center text-sm text-muted">Aucune invitation pour le moment.</li>
            @endforelse
        </ul>
    @endif

    @if ($tab === 'roles')
        <form wire:submit="createRole" class="card mb-6 p-4">
            <h2 class="mb-3 text-lg font-bold">Nouveau rôle</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="roleName" class="field-label">Nom</label>
                    <input id="roleName" wire:model="roleName" class="field-input" maxlength="80" required>
                    @error('roleName')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="roleDescription" class="field-label">Description</label>
                    <input id="roleDescription" wire:model="roleDescription" class="field-input" maxlength="255">
                </div>
            </div>
            <fieldset class="mt-3">
                <legend class="field-label">Permissions (vous ne pouvez accorder que celles que vous possédez)</legend>
                <div class="grid gap-1 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($permissions as $p)
                        <label class="flex min-h-11 items-center gap-2 text-sm">
                            <input type="checkbox" value="{{ $p->value }}" wire:model="rolePermissions" class="size-5 rounded border-line" @disabled(! $me->hasPermission($p))>
                            {{ $p->label() }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <button class="btn-primary mt-3">Créer le rôle</button>
        </form>

        <div class="space-y-4">
            @foreach ($roles as $role)
                @php($editable = $me->can('update', $role) && ! $role->isSuperAdmin())
                <section class="card p-4" wire:key="r-{{ $role->id }}" aria-labelledby="role-titre-{{ $role->id }}">
                    <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h3 id="role-titre-{{ $role->id }}" class="text-lg font-bold">{{ $role->name }}
                                @if ($role->is_system)<span class="badge ml-1 bg-accent-2/15 text-accent-2-ink">système</span>@endif</h3>
                            <p class="text-sm text-muted">{{ $role->description }} · {{ $role->users_count }} membre(s)</p>
                        </div>
                        @if ($me->can('delete', $role))
                            <x-confirm-action method="deleteRole" :arg="$role->id" label="Supprimer" title="Supprimer le rôle « {{ $role->name }} » ?" confirm="Supprimer">Impossible si des membres portent encore ce rôle.</x-confirm-action>
                        @endif
                    </div>
                    <div class="grid gap-1 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($permissions as $p)
                            <label class="flex min-h-11 items-center gap-2 text-sm">
                                <input type="checkbox" value="{{ $p->value }}" wire:model="matrix.{{ $role->id }}" class="size-5 rounded border-line"
                                       @disabled(! $editable || ! $me->hasPermission($p)) @checked($role->isSuperAdmin())>
                                {{ $p->label() }}
                            </label>
                        @endforeach
                    </div>
                    @if ($editable)
                        <button type="button" wire:click="saveRole({{ $role->id }})" class="btn-secondary mt-3">Enregistrer les permissions</button>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
</div>
