<div>
    <x-page-header title="Paramètres" subtitle="Identité du site, référencement, réflexion de la semaine et réseaux sociaux." />
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <form wire:submit="save" class="card p-4 sm:p-6" novalidate>
            <h2 class="mb-4 text-lg font-bold">Identité et référencement</h2>
            <x-editor.field model="form.site_name" label="Nom du site" maxlength="80" required />
            <x-editor.field model="form.seo_description" label="Description pour les moteurs de recherche" :textarea="true" :rows="3" maxlength="300" help="Utilisée quand une page n'a pas sa propre description (160 caractères affichés)." />
            <div class="mb-4 grid gap-4 sm:grid-cols-2">
                @foreach ([['logo', 'Logo', $logoUrl, 'removeLogo'], ['favicon', 'Favicon', $faviconUrl, 'removeFavicon']] as [$field, $label, $current, $remove])
                    <div>
                        <label for="f-{{ $field }}" class="field-label">{{ $label }}</label>
                        @if ($current)<img src="{{ $current }}" alt="{{ $label }} actuel" class="mb-2 h-12 w-auto rounded bg-surface-2 p-1">@endif
                        <input id="f-{{ $field }}" type="file" wire:model="{{ $field }}" accept="image/png,image/jpeg,image/webp,image/gif,image/avif" class="field-input py-2">
                        <p class="field-help">PNG, JPEG, WebP, GIF ou AVIF (pas de SVG), 5 Mo.</p>
                        @if ($current)<label class="mt-1 flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="{{ $remove }}" class="size-5 rounded border-line-strong"> Retirer</label>@endif
                    </div>
                @endforeach
            </div>
            @error('file')<p class="field-error">{{ $message }}</p>@enderror
            <h2 class="mb-1 mt-6 text-lg font-bold">Réflexion de la semaine</h2>
            <p class="mb-4 text-sm text-muted">Affichée sur l'accueil ; masquée si le texte est vide. @if ($reflectionDate) Dernière modification : {{ \Carbon\CarbonImmutable::parse($reflectionDate)->translatedFormat('j F Y') }}.@endif</p>
            <x-editor.field model="form.reflection_text" label="Texte" :textarea="true" :rows="3" maxlength="600" />
            <x-editor.field model="form.reflection_author" label="Auteur" maxlength="120" />
            <button class="btn-primary" wire:loading.attr="disabled" wire:target="logo,favicon,save">Enregistrer</button>
        </form>

        <form wire:submit="saveSocials" class="card p-4 sm:p-6" novalidate>
            <h2 class="mb-1 text-lg font-bold">Réseaux sociaux</h2>
            <p class="mb-4 text-sm text-muted">Affichés dans le pied de page, dans cet ordre. L'icône est déduite du nom de la plateforme. Liens https uniquement.</p>
            <ol class="space-y-3">
                @foreach ($socials as $i => $s)
                    <li class="rounded-xl border border-line p-3" wire:key="s-{{ $i }}">
                        <div class="grid gap-2 sm:grid-cols-[10rem_1fr]">
                            <div>
                                <label for="sp-{{ $i }}" class="sr-only">Plateforme {{ $i + 1 }}</label>
                                <input id="sp-{{ $i }}" wire:model="socials.{{ $i }}.platform" maxlength="40" placeholder="LinkedIn" class="field-input">
                                @error('links.'.$i.'.platform')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="su-{{ $i }}" class="sr-only">Lien {{ $i + 1 }}</label>
                                <input id="su-{{ $i }}" wire:model="socials.{{ $i }}.url" type="url" maxlength="500" placeholder="https://" class="field-input">
                                @error('links.'.$i.'.url')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="mt-2 flex justify-end gap-1">
                            <button type="button" wire:click="moveSocial({{ $i }}, -1)" class="btn-ghost min-w-11 px-2" aria-label="Monter" @disabled($i === 0)><x-icon name="arrow-up" class="size-4" /></button>
                            <button type="button" wire:click="moveSocial({{ $i }}, 1)" class="btn-ghost min-w-11 px-2" aria-label="Descendre" @disabled($i === count($socials) - 1)><x-icon name="arrow-down" class="size-4" /></button>
                            <button type="button" wire:click="removeSocial({{ $i }})" class="btn-ghost min-w-11 px-2 text-danger" aria-label="Supprimer"><x-icon name="trash" class="size-4" /></button>
                        </div>
                    </li>
                @endforeach
            </ol>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" wire:click="addSocial" class="btn-secondary"><x-icon name="plus" class="size-4" /> Ajouter un réseau</button>
                <button class="btn-primary">Enregistrer les réseaux</button>
            </div>
        </form>
    </div>
</div>
