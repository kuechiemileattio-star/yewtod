<div>
    <x-page-header title="Newsletter" subtitle="Abonnés recueillis sur le site (double opt-in). Les campagnes se rédigent dans Brevo.">
        <x-slot:actions>
            <a href="{{ route('dashboard.newsletter.export', array_filter(['statut' => $status])) }}" class="btn-secondary"><x-icon name="download" class="size-4" /> Exporter en CSV</a>
        </x-slot:actions>
    </x-page-header>

    @unless ($brevo)
        <p class="mb-4 rounded-lg border border-warning bg-warning/15 p-3 text-sm" role="status">
            Synchronisation Brevo désactivée : renseignez <code>BREVO_API_KEY</code> et <code>BREVO_LIST_ID</code> dans le fichier <code>.env</code>. La collecte et la double confirmation fonctionnent quand même.
        </p>
    @endunless

    <div class="mb-6 grid grid-cols-1 gap-3 xs:grid-cols-3">
        @foreach (\App\Models\NewsletterSubscriber::STATUSES as $key => $label)
            <div class="card p-4"><span class="block text-sm text-muted">{{ $label }}</span><span class="font-serif text-3xl font-bold">{{ $counts[$key] ?? 0 }}</span></div>
        @endforeach
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <label class="relative block"><span class="sr-only">Rechercher une adresse</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher une adresse" class="field-input pl-9" maxlength="100">
        </label>
        <label><span class="sr-only">Statut</span>
            <select wire:model.live="status" class="field-input"><option value="">Tous les statuts</option>@foreach (\App\Models\NewsletterSubscriber::STATUSES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        </label>
    </div>

    @if ($items->isEmpty())
        <x-empty-state icon="send" title="Aucun abonné">Les inscriptions du pied de page apparaîtront ici.</x-empty-state>
    @else
        <ul class="card divide-y divide-line">
            @foreach ($items as $s)
                <li class="flex flex-wrap items-center gap-x-4 gap-y-1 p-3 sm:p-4" wire:key="n-{{ $s->id }}">
                    <span class="min-w-0 flex-1 break-all font-semibold">{{ $s->email }}</span>
                    <span class="badge bg-surface-2 text-muted">{{ $s->statusLabel() }}</span>
                    <span class="text-xs text-muted">inscrit le {{ $s->created_at->setTimezone(config('app.display_timezone'))->format('d/m/Y') }}@if ($s->brevo_synced_at) · synchronisé Brevo @endif</span>
                    <x-confirm-action method="delete" :arg="$s->id" label="Supprimer" title="Supprimer {{ $s->email }} ?" confirm="Supprimer" button-class="btn-ghost px-3 text-danger">Suppression définitive (demande RGPD), y compris dans la liste Brevo.</x-confirm-action>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $items->links() }}</div>
    @endif
</div>
