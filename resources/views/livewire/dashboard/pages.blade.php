<div>
    <x-page-header title="Pages" subtitle="Textes éditables du site. Valeurs par défaut affichées tant que rien n'est enregistré.">
        <x-slot:actions><a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn-secondary"><x-icon name="eye" class="size-4" /> Voir sur le site</a></x-slot:actions>
    </x-page-header>
    <x-tabs :tabs="\App\Livewire\Dashboard\Pages::TABS" :current="$tab" />
    <form wire:submit="save" class="card max-w-4xl p-4 sm:p-6" novalidate wire:key="page-{{ $tab }}">
        @if (in_array($tab, ['meet', 'home'], true))
            <h2 class="mb-4 text-lg font-bold">Accroche</h2>
            <x-editor.field model="form.hero_title" label="Titre" maxlength="160" required />
            <x-editor.field model="form.hero_text" label="Texte" :textarea="true" :rows="3" maxlength="800" />
        @endif

        @if ($tab === 'home' || $tab === 'meet')
            @php($list = $tab === 'home' ? 'pillars' : 'how_i_work')
            <h2 class="mb-4 mt-6 text-lg font-bold">{{ $tab === 'home' ? 'Trois piliers éditoriaux' : 'Comment je travaille (3 cartes)' }}</h2>
            <div class="grid gap-4 md:grid-cols-3">
                @foreach (($form[$list] ?? []) as $i => $card)
                    <fieldset class="rounded-xl border border-line p-3" wire:key="{{ $list }}-{{ $i }}">
                        <legend class="px-1 text-sm font-semibold">Carte {{ $i + 1 }}</legend>
                        <x-editor.field model="form.{{ $list }}.{{ $i }}.title" label="Titre" maxlength="120" />
                        <x-editor.field model="form.{{ $list }}.{{ $i }}.text" label="Texte" :textarea="true" :rows="4" maxlength="600" />
                    </fieldset>
                @endforeach
            </div>
        @endif

        @if ($tab === 'meet')
            <h2 class="mb-4 mt-6 text-lg font-bold">Le projet en questions (FAQ)</h2>
            <ol class="space-y-3">
                @foreach (($form['faq'] ?? []) as $i => $item)
                    <li class="rounded-xl border border-line p-3" wire:key="faq-{{ $i }}">
                        <x-editor.field model="form.faq.{{ $i }}.q" label="Question {{ $i + 1 }}" maxlength="200" />
                        <x-editor.field model="form.faq.{{ $i }}.a" label="Réponse" :textarea="true" :rows="3" maxlength="1500" />
                        <button type="button" wire:click="removeFaq({{ $i }})" class="btn-ghost text-danger"><x-icon name="trash" class="size-4" /> Retirer</button>
                    </li>
                @endforeach
            </ol>
            <button type="button" wire:click="addFaq" class="btn-secondary mt-3"><x-icon name="plus" class="size-4" /> Ajouter une question</button>

            <h2 class="mb-4 mt-8 text-lg font-bold">Le fondateur</h2>
            <x-editor.field model="form.founder_name" label="Nom" maxlength="120" />
            <div class="mb-4">
                <label for="f-portrait" class="field-label">Portrait</label>
                @if ($portraitUrl)<img src="{{ $portraitUrl }}" alt="Portrait actuel" class="mb-2 h-24 w-20 rounded-lg object-cover">@endif
                <input id="f-portrait" type="file" wire:model="portrait" accept="image/jpeg,image/png,image/webp,image/avif" class="field-input py-2">
                @if ($portraitUrl)<label class="mt-1 flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="removePortrait" class="size-5 rounded border-line-strong"> Retirer le portrait</label>@endif
                @error('file')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <x-editor.field model="form.bio_1" label="Biographie — premier paragraphe" :textarea="true" :rows="4" maxlength="2000" />
            <x-editor.field model="form.bio_2" label="Biographie — second paragraphe" :textarea="true" :rows="4" maxlength="2000" />
            <x-editor.field model="form.expertise" label="Domaines d'expertise" :textarea="true" :rows="4" help="Un domaine par ligne." />
        @endif

        @if ($tab === 'footer')
            <h2 class="mb-4 text-lg font-bold">Bandeau « Démarrer une conversation »</h2>
            <x-editor.field model="form.cta_title" label="Titre" maxlength="120" />
            <x-editor.field model="form.cta_text" label="Texte" :textarea="true" :rows="2" maxlength="300" />
            <x-editor.field model="form.about" label="Présentation courte (sous le nom du site)" :textarea="true" :rows="2" maxlength="300" />
        @endif

        <button class="btn-primary mt-4" wire:loading.attr="disabled" wire:target="portrait,save">Enregistrer la page</button>
    </form>
</div>
