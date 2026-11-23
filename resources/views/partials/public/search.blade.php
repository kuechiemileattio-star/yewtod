{{--
    Recherche globale Ctrl K (cahier § 10 P2). Sans JavaScript, le bouton
    est un simple lien vers le catalogue filtrable.
--}}
<div x-data="globalSearch" data-url="{{ route('search') }}" data-works="{{ url('/works') }}">
    <a href="{{ url('/works') }}" x-on:click.prevent="show" class="{{ $buttonClass ?? 'btn-ghost min-w-11 px-2' }}" title="Rechercher (Ctrl K)">
        <x-icon name="search" /><span class="sr-only">Rechercher</span>
    </a>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-ink/50 p-4 pt-[10vh]" x-on:keydown.escape.window="close" x-on:click.self="close">
        <div role="dialog" aria-modal="true" aria-labelledby="titre-recherche" class="w-full max-w-xl overflow-hidden card shadow-2xl">
            <form x-on:submit.prevent="submitAll" class="flex items-center gap-2 border-b border-line px-4">
                <h2 id="titre-recherche" class="sr-only">Recherche globale</h2>
                <x-icon name="search" class="size-5 shrink-0 text-muted" />
                <label for="recherche-globale" class="sr-only">Rechercher une publication ou un livre</label>
                <input id="recherche-globale" x-ref="input" x-model="q" x-on:input="search" type="search" maxlength="100" autocomplete="off"
                       placeholder="Rechercher une publication, un livre…" class="min-h-14 w-full border-0 bg-transparent text-base text-ink placeholder:text-muted focus:ring-0">
                <button type="button" x-on:click="close" class="btn-ghost min-w-11 px-2" aria-label="Fermer la recherche"><x-icon name="close" /></button>
            </form>
            <div class="max-h-[60vh] overflow-y-auto p-2" aria-live="polite">
                <p x-show="hint" class="px-3 py-6 text-center text-sm text-muted">Tapez au moins deux caractères. Entrée : voir tous les résultats.</p>
                <p x-show="loading" x-cloak class="px-3 py-6 text-center text-sm text-muted">Recherche…</p>
                <p x-show="empty" x-cloak class="px-3 py-6 text-center text-sm text-muted">Aucun résultat.</p>
                <ul>
                    <template x-for="item in results" x-bind:key="item.url">
                        <li><a x-bind:href="item.url" class="flex min-h-11 flex-col justify-center rounded-lg px-3 py-2 hover:bg-surface-2 focus:bg-surface-2">
                            <span class="font-semibold" x-text="item.title"></span><span class="text-xs text-muted" x-text="item.kind"></span>
                        </a></li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</div>
