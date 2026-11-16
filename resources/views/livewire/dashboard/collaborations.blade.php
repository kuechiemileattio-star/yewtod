<div>
    <x-page-header title="Collaborations" subtitle="Demandes reçues depuis le site. Supprimées automatiquement après {{ config('yewtod.retention.collaborations_months') }} mois.">
        <x-slot:actions>
            <a href="{{ route('dashboard.collaborations.export', array_filter(['statut' => $filter])) }}" class="btn-secondary"><x-icon name="download" class="size-4" /> Exporter en CSV</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid grid-cols-1 gap-3 xs:grid-cols-3">
        @foreach (\App\Models\Collaboration::STATUSES as $key => $label)
            <button type="button" wire:click="$set('status', '{{ $filter === $key ? '' : $key }}')" @class(['card min-h-11 p-4 text-left transition hover:border-line-strong', 'ring-2 ring-accent-2' => $filter === $key]) aria-pressed="{{ $filter === $key ? 'true' : 'false' }}">
                <span class="block text-sm text-muted">{{ $label === 'Nouveau' ? 'Nouvelles' : ($label === 'En cours' ? 'En cours' : 'Archivées') }}</span>
                <span class="font-serif text-3xl font-bold">{{ $counts[$key] ?? 0 }}</span>
            </button>
        @endforeach
    </div>

    @if ($items->isEmpty())
        <x-empty-state icon="mail" title="Aucune demande">Les demandes envoyées depuis la page Collaborations apparaîtront ici.</x-empty-state>
    @else
        {{-- Tableau sur grand écran, cartes sur mobile (décision 14). --}}
        <div class="card hidden overflow-hidden md:block">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-2 text-xs uppercase tracking-wider text-muted">
                    <tr><th class="px-4 py-3">Nom</th><th class="px-4 py-3">Organisation</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($items as $c)
                        <tr wire:key="t-{{ $c->id }}">
                            <td class="px-4 py-3 font-semibold">{{ $c->name }}@if ($c->files_count) <span class="text-xs text-muted">· {{ $c->files_count }} PJ</span>@endif</td>
                            <td class="px-4 py-3">{{ $c->organization ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $c->kindLabel() }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $c->created_at->setTimezone(config('app.display_timezone'))->format('d/m/Y') }}</td>
                            <td class="px-4 py-3"><span @class(['badge', 'bg-accent/15 text-accent-ink dark:text-accent' => $c->status === 'new', 'bg-accent-2/15 text-accent-2-ink' => $c->status === 'in_progress', 'bg-surface-2 text-muted' => $c->status === 'archived'])>{{ $c->statusLabel() }}</span></td>
                            <td class="px-4 py-3 text-right"><button type="button" wire:click="open({{ $c->id }})" class="btn-ghost px-3">Ouvrir</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <ul class="space-y-3 md:hidden">
            @foreach ($items as $c)
                <li class="card p-4" wire:key="m-{{ $c->id }}">
                    <p class="flex items-start justify-between gap-2"><strong class="min-w-0 break-words">{{ $c->name }}</strong><span class="badge shrink-0 bg-surface-2 text-muted">{{ $c->statusLabel() }}</span></p>
                    <p class="text-sm text-muted">{{ $c->kindLabel() }} · {{ $c->created_at->setTimezone(config('app.display_timezone'))->format('d/m/Y') }}</p>
                    @if ($c->organization)<p class="text-sm">{{ $c->organization }}</p>@endif
                    <button type="button" wire:click="open({{ $c->id }})" class="btn-secondary mt-3 w-full">Ouvrir la demande</button>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $items->links() }}</div>
    @endif

    <x-modal :show="$opened !== null" :title="$opened?->subject ?? ''" max-width="max-w-3xl">
        @if ($opened)
            <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                <dt class="text-muted">Nom</dt><dd class="font-semibold">{{ $opened->name }}</dd>
                <dt class="text-muted">Organisation</dt><dd>{{ $opened->organization ?? '—' }}</dd>
                <dt class="text-muted">Email</dt><dd class="break-all"><a class="link" href="mailto:{{ $opened->email }}">{{ $opened->email }}</a></dd>
                <dt class="text-muted">Type</dt><dd>{{ $opened->kindLabel() }}</dd>
                <dt class="text-muted">Reçue le</dt><dd>{{ $opened->created_at->setTimezone(config('app.display_timezone'))->translatedFormat('j F Y à H\hi') }}</dd>
            </dl>
            <h3 class="mt-6 text-sm font-bold uppercase tracking-wider text-muted">Description</h3>
            <p class="mt-2 whitespace-pre-line break-words">{{ $opened->description }}</p>
            @if ($opened->files->isNotEmpty())
                <h3 class="mt-6 text-sm font-bold uppercase tracking-wider text-muted">Pièces jointes <span class="font-normal normal-case">(liens valables 10 minutes)</span></h3>
                <ul class="mt-2 space-y-1">
                    @foreach ($opened->files as $f)
                        <li><a href="{{ $fileLinks[$f->id] }}" class="btn-ghost justify-start px-2"><x-icon name="download" class="size-4" /> {{ $f->original_name }} <span class="text-xs text-muted">{{ \App\Support\Format::fileSize($f->size) }}</span></a></li>
                    @endforeach
                </ul>
            @endif
            <fieldset class="mt-6">
                <legend class="field-label">Statut</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Models\Collaboration::STATUSES as $key => $label)
                        <button type="button" wire:click="changeStatus({{ $opened->id }}, '{{ $key }}')" @class(['btn', 'bg-ink text-page' => $opened->status === $key, 'btn-secondary' => $opened->status !== $key]) aria-pressed="{{ $opened->status === $key ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </fieldset>
        @endif
        <x-slot:footer>
            @if ($opened)
                <x-confirm-action method="delete" :arg="$opened->id" label="Supprimer la demande" title="Supprimer cette demande ?" confirm="Supprimer" button-class="btn-danger">La demande et ses pièces jointes seront supprimées (droit à l'effacement).</x-confirm-action>
            @endif
        </x-slot:footer>
    </x-modal>
</div>
