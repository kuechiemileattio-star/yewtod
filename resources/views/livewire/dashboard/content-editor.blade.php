@php
    $t = $contentType->value;
    $meta = $form['meta'] ?? [];
    $sanitizer = app(\App\Services\HtmlSanitizer::class);
@endphp
<div x-data="unsavedGuard" x-on:input="markDirty" x-on:change="markDirty" wire:poll.60s="autosave">
    <x-page-header :title="($contentId ? 'Modifier' : 'Nouveau').' · '.$contentType->label()" :subtitle="$content ? 'Statut : '.$content->status->label() : 'Pas encore enregistré'">
        <x-slot:actions>
            <a href="{{ in_array($contentType, \App\Enums\ContentType::others(), true) ? route('dashboard.contents.others') : route('dashboard.contents.'.$t) }}" class="btn-ghost">Retour à la liste</a>
            @if ($content && \Illuminate\Support\Facades\Route::has('content.preview'))
                <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('content.preview', now()->addMinutes(30), ['content' => $content->id]) }}" target="_blank" rel="noopener" class="btn-secondary"><x-icon name="eye" class="size-4" /> Aperçu réel</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <p x-show="dirty" x-cloak class="mb-4 rounded-lg border border-warning bg-warning/15 p-3 text-sm" role="status">Modifications non enregistrées. Un brouillon est sauvegardé automatiquement chaque minute.</p>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <form wire:submit="save('draft')" class="min-w-0 space-y-6" novalidate>
            {{-- 1. Identité --}}
            <section class="card p-4 sm:p-6" aria-labelledby="s1">
                <h2 id="s1" class="mb-4 text-lg font-bold"><span class="text-accent-ink dark:text-accent">1.</span> Identité</h2>
                <x-editor.field model="form.title" label="Titre" maxlength="300" required :live="true" />
                <x-editor.field model="form.slug" label="Identifiant d'URL (facultatif)" help="Généré depuis le titre si vide ; suffixe -2, -3… en cas de doublon." maxlength="300" />
                <x-editor.field model="form.summary" :label="$contentType->excerptLabel()" :textarea="true" :rows="5" :maxlength="$t === 'articles' ? 2900 : 5000" :live="true" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="f-lang" class="field-label">Langue</label>
                        <select id="f-lang" wire:model="form.language" class="field-input">
                            @foreach (\App\Support\Taxonomy::LANGUAGES as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <label class="flex min-h-11 items-center gap-2 self-end text-sm font-semibold"><input type="checkbox" wire:model="form.is_featured" class="size-5 rounded border-line-strong"> Mettre en avant sur l'accueil</label>
                </div>
                @if ($t === 'episodes-documentaires')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="f-parent" class="field-label">Série (obligatoire)</label>
                            <select id="f-parent" wire:model="form.parent_id" class="field-input" required>
                                <option value="">Choisir une série…</option>
                                @foreach ($series as $s)<option value="{{ $s->id }}">{{ $s->title }}</option>@endforeach
                            </select>
                            @error('parent_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <x-editor.field model="form.episode_number" label="Numéro d'épisode" type="number" min="1" help="Laissez vide : dernier numéro + 1." />
                    </div>
                @endif
            </section>

            {{-- 2. Classification --}}
            <section class="card p-4 sm:p-6" aria-labelledby="s2">
                <h2 id="s2" class="mb-4 text-lg font-bold"><span class="text-accent-ink dark:text-accent">2.</span> Classification</h2>
                <fieldset class="mb-4">
                    <legend class="field-label">Axes thématiques</legend>
                    <div class="grid gap-1 sm:grid-cols-2">
                        @foreach ($themes as $key => $label)
                            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" value="{{ $key }}" wire:model="form.themes" class="size-5 rounded border-line-strong"> {{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <x-editor.field model="form.tags" label="Tags (séparés par des virgules)" help="20 au maximum." maxlength="900" />
                @if ($t === 'articles')
                    <x-editor.field model="form.meta.subtitle" label="Sous-titre" maxlength="300" />
                    <div class="mb-4">
                        <label for="f-kind" class="field-label">Type de contenu</label>
                        <select id="f-kind" wire:model="form.meta.content_kind" class="field-input">
                            <option value="">—</option>
                            @foreach (\App\Support\Taxonomy::ARTICLE_KINDS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <x-editor.field model="form.meta.coauthors" label="Co-auteurs (séparés par des virgules)" />
                @endif
                @if (in_array($t, ['rapports', 'etudes']))
                    <x-editor.field model="form.meta.authors" label="Auteurs (séparés par des virgules)" />
                    <x-editor.field model="form.meta.doi" label="DOI" help="Format 10.xxxx/identifiant" maxlength="200" />
                @endif
                @if ($t === 'rapports')
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-editor.field model="form.meta.version" label="Version" maxlength="20" />
                        <x-editor.field model="form.meta.volume" label="Volume" maxlength="20" />
                        <x-editor.field model="form.meta.issue" label="Numéro" maxlength="20" />
                    </div>
                    <x-editor.field model="form.meta.affiliation" label="Affiliation" maxlength="200" />
                @endif
                @if ($t === 'etudes')
                    <x-editor.field model="form.meta.research_question" label="Question de recherche" :textarea="true" :rows="3" maxlength="1000" />
                @endif
                @if ($t === 'notes-de-recherche')
                    <div class="mb-4">
                        <label for="f-progress" class="field-label">Avancement</label>
                        <select id="f-progress" wire:model="form.meta.progress" class="field-input">
                            <option value="">—</option>
                            @foreach (\App\Support\Taxonomy::NOTE_PROGRESS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                        </select>
                    </div>
                @endif
                @if ($t === 'series-documentaires')
                    <x-editor.field model="form.meta.theme_label" label="Thématique" maxlength="120" />
                    <x-editor.field model="form.meta.trailer_url" label="Bande-annonce (lien YouTube)" type="url" help="http(s) uniquement." />
                @endif
                @if ($t === 'episodes-documentaires')
                    <x-editor.field model="form.meta.video_url" label="Vidéo (YouTube, Vimeo, MP4 ou WebM)" type="url" />
                @endif
                @if (in_array($t, ['experimentations', 'visualisations-de-donnees']))
                    <x-editor.field model="form.meta.source_code_url" label="Code source (lien http/https)" type="url" />
                @endif
                @if ($t === 'visualisations-de-donnees')
                    <x-editor.field model="form.meta.data_source" label="Source des données" maxlength="300" />
                @endif
            </section>

            {{-- 3. Contenu --}}
            <section class="card p-4 sm:p-6" aria-labelledby="s3">
                <h2 id="s3" class="mb-4 text-lg font-bold"><span class="text-accent-ink dark:text-accent">3.</span> Contenu</h2>
                <p class="mb-3 text-sm text-muted">Sections libres : sous-titre + texte riche. Les sections vides sont ignorées. Le HTML est nettoyé côté serveur.</p>
                @foreach ($form['sections'] as $i => $section)
                    <div class="mb-4 rounded-xl border border-line p-3" wire:key="sec-{{ $i }}-{{ md5(($section['title'] ?? '').($section['content'] ?? '')) }}">
                        <div class="mb-2 flex flex-wrap items-start gap-2">
                            <label class="sr-only" for="sec-title-{{ $i }}">Titre de la section {{ $i + 1 }}</label>
                            <input id="sec-title-{{ $i }}" wire:model="form.sections.{{ $i }}.title" placeholder="Titre de la section {{ $i + 1 }}" class="field-input min-w-0 flex-1 basis-48" maxlength="300">
                            <x-editor.row-tools list="sections" :index="$i" :count="count($form['sections'])" />
                        </div>
                        <div x-data="richText" data-model="form.sections.{{ $i }}.content" wire:ignore class="rounded-lg border border-line-strong">
                            <div class="flex flex-wrap gap-1 border-b border-line p-1" role="toolbar" aria-label="Mise en forme">
                                @foreach (['bold' => 'G', 'italic' => 'I', 'h3' => 'Titre', 'ul' => '• Liste', 'ol' => '1. Liste', 'quote' => '« Citation »', 'link' => 'Lien', 'clear' => 'Effacer'] as $cmd => $label)
                                    <button type="button" class="btn-ghost min-h-10 px-2 text-xs" data-cmd="{{ $cmd }}" x-on:click="exec">{{ $label }}</button>
                                @endforeach
                            </div>
                            <div x-ref="area" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Texte de la section {{ $i + 1 }}" x-on:input="sync" x-on:blur="sync"
                                 class="prose-yewtod min-h-40 px-3 py-2 focus:outline-none">{!! $sanitizer->clean($section['content'] ?? '') !!}</div>
                        </div>
                    </div>
                @endforeach
                <button type="button" wire:click="addRow('sections')" class="btn-secondary"><x-icon name="plus" class="size-4" /> Ajouter une section</button>

                @if ($t === 'visualisations-de-donnees')
                    <h3 class="mb-2 mt-6 text-base font-bold">Blocs de graphiques</h3>
                    @foreach ($meta['blocks'] ?? [] as $i => $block)
                        <div class="mb-4 rounded-xl border border-line p-3" wire:key="blk-{{ $i }}">
                            <div class="mb-2 flex flex-wrap items-start gap-2">
                                <input wire:model="form.meta.blocks.{{ $i }}.title" placeholder="Titre du graphique" class="field-input min-w-0 flex-1 basis-48" aria-label="Titre du graphique {{ $i + 1 }}" maxlength="300">
                                <x-editor.row-tools list="meta.blocks" :index="$i" :count="count($meta['blocks'])" />
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <select wire:model="form.meta.blocks.{{ $i }}.chart_type" class="field-input" aria-label="Type de graphique">
                                    @foreach (\App\Support\Taxonomy::CHART_TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                                </select>
                                <div>
                                    <input type="file" wire:model="csv.{{ $i }}" accept=".csv,text/csv" class="field-input py-2" aria-label="Fichier CSV du graphique {{ $i + 1 }}">
                                    <p class="field-help">@if (!empty($block['data']['labels'])) {{ count($block['data']['labels']) }} ligne(s), {{ count($block['data']['series'] ?? []) }} série(s). @endif 1re colonne = catégories, colonnes suivantes = séries. 2 Mo max.</p>
                                </div>
                            </div>
                            <textarea wire:model="form.meta.blocks.{{ $i }}.description" rows="2" class="field-input mt-3" placeholder="Description" aria-label="Description du graphique" maxlength="2000"></textarea>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addRow('meta.blocks')" class="btn-secondary"><x-icon name="plus" class="size-4" /> Ajouter un graphique</button>
                    <x-editor.field model="form.meta.legend" label="Légende" :textarea="true" :rows="2" />
                    <x-editor.field model="form.meta.analysis" label="Analyse" :textarea="true" :rows="4" />
                @endif

                @if ($t === 'episodes-documentaires')
                    <h3 class="mb-2 mt-6 text-base font-bold">Chapitres horodatés</h3>
                    @foreach ($meta['chapters'] ?? [] as $i => $ch)
                        <div class="mb-2 flex flex-wrap items-start gap-2" wire:key="ch-{{ $i }}">
                            <input wire:model="form.meta.chapters.{{ $i }}.time" placeholder="00:00" class="field-input w-28" aria-label="Horodatage">
                            <input wire:model="form.meta.chapters.{{ $i }}.title" placeholder="Titre du chapitre" class="field-input min-w-0 flex-1" aria-label="Titre du chapitre" maxlength="200">
                            <x-editor.row-tools list="meta.chapters" :index="$i" :count="count($meta['chapters'])" />
                        </div>
                    @endforeach
                    <button type="button" wire:click="addRow('meta.chapters')" class="btn-secondary"><x-icon name="plus" class="size-4" /> Ajouter un chapitre</button>
                @endif

                @if ($t === 'notes-de-recherche')
                    <h3 class="mb-2 mt-6 text-base font-bold">Liens utiles</h3>
                    @foreach ($meta['links'] ?? [] as $i => $l)
                        <div class="mb-2 flex flex-wrap items-start gap-2" wire:key="ln-{{ $i }}">
                            <input wire:model="form.meta.links.{{ $i }}.label" placeholder="Libellé" class="field-input min-w-0 flex-1" aria-label="Libellé du lien">
                            <input wire:model="form.meta.links.{{ $i }}.url" placeholder="https://" class="field-input min-w-0 flex-1" aria-label="Adresse du lien">
                            <x-editor.row-tools list="meta.links" :index="$i" :count="count($meta['links'])" />
                        </div>
                    @endforeach
                    <button type="button" wire:click="addRow('meta.links')" class="btn-secondary"><x-icon name="plus" class="size-4" /> Ajouter un lien</button>
                @endif

                @if (in_array($t, ['articles', 'rapports', 'etudes']))
                    <h3 class="mb-2 mt-6 text-base font-bold">Sommaire du PDF</h3>
                    @foreach ($meta['toc'] ?? [] as $i => $entry)
                        <div class="mb-2 flex flex-wrap items-start gap-2" wire:key="toc-{{ $i }}">
                            <input wire:model="form.meta.toc.{{ $i }}.title" placeholder="Titre" class="field-input min-w-0 flex-1" aria-label="Titre de l'entrée">
                            <input wire:model="form.meta.toc.{{ $i }}.page" type="number" min="1" placeholder="Page" class="field-input w-24" aria-label="Page">
                            <x-editor.row-tools list="meta.toc" :index="$i" :count="count($meta['toc'])" />
                        </div>
                    @endforeach
                    <button type="button" wire:click="addRow('meta.toc')" class="btn-secondary"><x-icon name="plus" class="size-4" /> Ajouter une entrée</button>
                @endif

                <h3 class="mb-2 mt-6 text-base font-bold">Références</h3>
                @foreach ($form['references'] as $i => $ref)
                    <div class="mb-2 flex flex-wrap items-start gap-2" wire:key="ref-{{ $i }}">
                        <input wire:model="form.references.{{ $i }}.title" placeholder="Référence" class="field-input min-w-0 flex-[2]" aria-label="Référence {{ $i + 1 }}" maxlength="500">
                        <input wire:model="form.references.{{ $i }}.url" placeholder="https:// (facultatif)" class="field-input min-w-0 flex-1" aria-label="Lien de la référence {{ $i + 1 }}">
                        <x-editor.row-tools list="references" :index="$i" :count="count($form['references'])" />
                    </div>
                    @error('references.'.$i.'.url')<p class="field-error">{{ $message }}</p>@enderror
                @endforeach
                <button type="button" wire:click="addRow('references')" class="btn-secondary"><x-icon name="plus" class="size-4" /> Ajouter une référence</button>
            </section>

            {{-- 4. Médias --}}
            <section class="card p-4 sm:p-6" aria-labelledby="s4">
                <h2 id="s4" class="mb-4 text-lg font-bold"><span class="text-accent-ink dark:text-accent">4.</span> Médias</h2>
                <div class="mb-4">
                    <p class="field-label">Couverture</p>
                    <label class="flex min-h-28 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-line-strong p-4 text-center text-sm hover:bg-surface-2">
                        <x-icon name="upload" class="mb-2 size-6 text-muted" />
                        <span>Glisser-déposer ou choisir une image (JPEG, PNG, WebP, GIF, AVIF — 5 Mo, pas de SVG)</span>
                        <input type="file" wire:model="cover" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" class="sr-only">
                    </label>
                    <p wire:loading wire:target="cover" class="field-help">Envoi de l'image…</p>
                    @if ($content?->cover_path && ! $cover)<p class="field-help">Couverture actuelle conservée.</p>@endif
                    @error('file')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <x-editor.field model="form.cover_alt" label="Texte alternatif de la couverture" maxlength="300" help="Décrit l'image pour les lecteurs d'écran." />

                @if (in_array($t, ['articles', 'rapports', 'etudes', 'notes-de-recherche', 'experimentations']))
                    <div class="mb-4" x-data="pdfInspector">
                        <p class="field-label">Document PDF (50 Mo max.)</p>
                        <input type="file" wire:model="document" accept="application/pdf" x-on:change="inspect" class="field-input py-2" aria-label="Document PDF">
                        <p class="field-help" x-text="progress" aria-live="polite"></p>
                        <p wire:loading wire:target="document" class="field-help">Envoi du PDF…</p>
                        @if ($content?->file_path)<p class="field-help">Fichier actuel : {{ $content->file_name }} ({{ number_format(($content->file_size ?? 0) / 1048576, 1, ',', ' ') }} Mo).</p>@endif
                        @if (! empty($meta['pages']))<p class="field-help">{{ $meta['pages'] }} page(s).</p>@endif
                    </div>
                @endif

                @if ($t === 'experimentations')
                    <p class="field-label">Fichiers téléchargeables</p>
                    <ul class="mb-2 space-y-1 text-sm">
                        @foreach ($meta['downloads'] ?? [] as $i => $d)
                            <li class="flex items-center gap-2" wire:key="dl-{{ $i }}">{{ $d['label'] }} <button type="button" wire:click="removeRow('meta.downloads', {{ $i }})" class="btn-ghost min-w-11 px-2 text-danger" aria-label="Retirer"><x-icon name="trash" class="size-4" /></button></li>
                        @endforeach
                    </ul>
                    <div class="flex flex-wrap gap-2">
                        <input wire:model="downloadLabel" placeholder="Libellé" class="field-input min-w-0 flex-1" aria-label="Libellé du fichier">
                        <input type="file" wire:model="download" class="field-input min-w-0 flex-1 py-2" aria-label="Fichier téléchargeable">
                    </div>
                    <p class="field-help">PDF, ZIP, CSV, TXT, JSON, DOCX, XLSX, ODT, ODS — ajouté à l'enregistrement.</p>
                @endif
            </section>

            {{-- 5. Référencement et liens --}}
            <section class="card p-4 sm:p-6" aria-labelledby="s5">
                <h2 id="s5" class="mb-4 text-lg font-bold"><span class="text-accent-ink dark:text-accent">5.</span> Référencement et contenus similaires</h2>
                <x-editor.field model="form.seo_title" label="Titre SEO (facultatif)" maxlength="200" />
                <x-editor.field model="form.seo_description" label="Description SEO (facultatif)" :textarea="true" :rows="2" maxlength="320" />
                <label for="related-search" class="field-label">Contenus similaires</label>
                <input id="related-search" type="search" wire:model.live.debounce.300ms="relatedSearch" placeholder="Rechercher un titre…" class="field-input" maxlength="100">
                @if ($relatedResults->isNotEmpty())
                    <ul class="card mt-1 divide-y divide-line" role="listbox" aria-label="Résultats">
                        @foreach ($relatedResults as $r)
                            <li><button type="button" wire:click="addRelated({{ $r->id }})" class="flex min-h-11 w-full items-center gap-2 px-3 text-left text-sm hover:bg-surface-2">{{ $r->title }} <span class="text-xs text-muted">· {{ $r->type->label() }}</span></button></li>
                        @endforeach
                    </ul>
                @endif
                <ul class="mt-2 flex flex-wrap gap-2" aria-label="Contenus similaires choisis">
                    @foreach ($relatedItems as $r)
                        <li class="badge gap-1 bg-surface-2 py-1 text-ink">{{ \Illuminate\Support\Str::limit($r->title, 40) }}
                            <button type="button" wire:click="removeRelated({{ $r->id }})" class="-my-2 min-h-11 min-w-8 text-muted hover:text-danger" aria-label="Retirer {{ $r->title }}">×</button></li>
                    @endforeach
                </ul>
            </section>
        </form>

        {{-- Colonne latérale : actions + aperçu en direct --}}
        <aside class="space-y-4 xl:sticky xl:top-20 xl:self-start" aria-label="Publication et aperçu">
            <div class="card p-4">
                <h2 class="mb-3 text-base font-bold">Publication</h2>
                <div class="grid gap-2">
                    <button type="button" wire:click="save('draft')" class="btn-secondary" wire:loading.attr="disabled" wire:target="save,cover,document,csv,download">Enregistrer le brouillon</button>
                    @if ($canPublish)
                        <button type="button" wire:click="save('publish')" class="btn-primary" wire:loading.attr="disabled" wire:target="save,cover,document,csv,download">Publier</button>
                        <fieldset class="mt-2 rounded-xl border border-line p-3">
                            <legend class="px-1 text-sm font-semibold">Programmer</legend>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="text-xs">Date<input type="date" wire:model="form.scheduled_date" class="field-input mt-1"></label>
                                <label class="text-xs">Heure<input type="time" wire:model="form.scheduled_time" class="field-input mt-1"></label>
                            </div>
                            @error('scheduled_at')<p class="field-error">{{ $message }}</p>@enderror
                            <button type="button" wire:click="save('schedule')" class="btn-secondary mt-2 w-full" wire:loading.attr="disabled" wire:target="save,cover,document,csv,download">Programmer</button>
                        </fieldset>
                    @else
                        <button type="button" wire:click="save('review')" class="btn-primary" wire:loading.attr="disabled" wire:target="save,cover,document,csv,download">Soumettre en relecture</button>
                        <p class="text-xs text-muted">Votre rôle ne publie pas directement : un éditeur relira et publiera.</p>
                    @endif
                    <p wire:loading wire:target="cover,document,csv,download" class="text-xs font-semibold text-warning-ink">Envoi de fichier en cours : enregistrement bloqué.</p>
                </div>
            </div>

            <div class="card overflow-hidden" aria-label="Aperçu de la carte publique">
                <p class="border-b border-line px-4 py-2 text-xs font-bold uppercase tracking-wider text-muted">Aperçu en direct</p>
                <div class="aspect-[16/9] bg-surface-2">
                    @if ($cover)
                        <img src="{{ $cover->isPreviewable() ? $cover->temporaryUrl() : '' }}" alt="" class="h-full w-full object-cover">
                    @elseif ($content)
                        <x-content-cover :content="$content" />
                    @endif
                </div>
                <div class="p-4">
                    <p class="text-xs font-semibold uppercase text-accent-2-ink">{{ collect($form['themes'])->map(fn ($k) => $themes[$k] ?? null)->filter()->first() ?? $contentType->label() }}</p>
                    <p class="mt-1 font-serif text-lg font-bold">{{ $form['title'] ?: 'Titre de la publication' }}</p>
                    <p class="mt-1 text-sm text-muted">{{ \Illuminate\Support\Str::limit((string) $form['summary'], 140) }}</p>
                </div>
            </div>
        </aside>
    </div>
</div>
