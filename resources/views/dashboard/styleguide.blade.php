<x-layouts.dashboard title="Guide de style">
    <x-page-header title="Système de design" subtitle="Jetons sémantiques, composants et états, en clair comme en sombre." />
    <section class="mb-8">
        <h2 class="mb-3 text-xl font-bold">Couleurs (jetons)</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach (['page', 'surface', 'surface-2', 'ink', 'muted', 'line', 'accent', 'accent-ink', 'accent-2', 'accent-2-ink', 'warning', 'danger', 'success'] as $token)
                <div class="card overflow-hidden"><div class="h-14 bg-{{ $token }}"></div><p class="p-2 font-mono text-xs">{{ $token }}</p></div>
            @endforeach
        </div>
    </section>
    <section class="mb-8">
        <h2 class="mb-3 text-xl font-bold">Boutons</h2>
        <div class="flex flex-wrap gap-2">
            <button class="btn-primary">Principal</button><button class="btn-secondary">Secondaire</button>
            <button class="btn-ghost">Discret</button><button class="btn-danger">Danger</button><button class="btn-primary" disabled>Désactivé</button>
        </div>
    </section>
    <section class="mb-8">
        <h2 class="mb-3 text-xl font-bold">Statuts</h2>
        <div class="flex flex-wrap gap-2">
            @foreach (['draft' => 'Brouillon', 'review' => 'En relecture', 'scheduled' => 'Programmé', 'published' => 'Publié', 'archived' => 'Archivé', 'new' => 'Nouveau', 'rejected' => 'Rejeté'] as $s => $l)
                <x-status-badge :status="$s" :label="$l" />
            @endforeach
        </div>
    </section>
    <section class="mb-8 max-w-md">
        <h2 class="mb-3 text-xl font-bold">Champs</h2>
        <x-form.input name="exemple" label="Champ texte" help="Texte d'aide sous le champ." />
        <x-form.password name="exemple_mdp" label="Mot de passe avec jauge" :strength="true" autocomplete="new-password" />
    </section>
    <x-empty-state title="État vide" icon="article">Aucun élément pour l'instant.<x-slot:action><button class="btn-primary">Créer</button></x-slot:action></x-empty-state>
</x-layouts.dashboard>
