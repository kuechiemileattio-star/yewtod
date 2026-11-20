<?php

namespace App\Livewire\Dashboard;

use App\Enums\ContentType;
use App\Livewire\Concerns\Toasts;
use App\Models\Content;
use App\Models\User;
use App\Services\ContentWorkflow;
use App\Services\UploadService;
use App\Support\Taxonomy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Éditeur de publication unique pour les 8 types (cahier § 4.3).
 * Cette classe ne fait que collecter le formulaire : la validation, le
 * nettoyage HTML, les droits et le circuit éditorial sont dans
 * ContentWorkflow (côté serveur, testés).
 */
#[Layout('layouts::dashboard')]
class ContentEditor extends Component
{
    use Toasts, WithFileUploads;

    #[Locked]
    public string $type;

    #[Locked]
    public ?int $contentId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    /** @var TemporaryUploadedFile|null */
    public $document = null;

    /** @var array<int, TemporaryUploadedFile|null> */
    public array $csv = [];

    /** @var TemporaryUploadedFile|null */
    public $download = null;

    public string $downloadLabel = '';

    public string $relatedSearch = '';

    public function mount(string $type, ?Content $content = null): void
    {
        $contentType = ContentType::tryFrom($type);
        abort_if($contentType === null, 404);
        abort_unless($this->me()->hasPermission('manage_articles'), 403);

        $this->type = $contentType->value;
        if ($content !== null && $content->exists) {
            abort_unless($content->type === $contentType, 404);
            if (! app(ContentWorkflow::class)->canEdit($this->me(), $content)) {
                abort(403, 'Vous ne pouvez pas modifier ce contenu.');
            }
            $this->contentId = $content->id;
        }

        $c = $content ?? new Content;
        $this->form = [
            'title' => $c->title ?? '', 'slug' => $c->slug ?? '', 'summary' => $c->summary ?? '',
            'cover_alt' => $c->cover_alt ?? '', 'is_featured' => (bool) $c->is_featured, 'language' => $c->language ?? 'fr',
            'seo_title' => $c->seo_title ?? '', 'seo_description' => $c->seo_description ?? '',
            'tags' => implode(', ', $c->tags ?? []), 'themes' => $c->themes ?? [],
            'sections' => $c->sections ?: [['title' => '', 'content' => '']],
            'references' => $c->references ?? [], 'meta' => $c->meta ?? [],
            'related' => $c->exists ? $c->related()->pluck('contents.id')->all() : [],
            'parent_id' => $c->parent_id, 'episode_number' => $c->episode_number,
            'scheduled_date' => $c->scheduled_at?->setTimezone(config('app.display_timezone'))->format('Y-m-d') ?? '',
            'scheduled_time' => $c->scheduled_at?->setTimezone(config('app.display_timezone'))->format('H:i') ?? '',
        ];
        foreach (['authors', 'coauthors'] as $list) {
            if (isset($this->form['meta'][$list]) && is_array($this->form['meta'][$list])) {
                $this->form['meta'][$list] = implode(', ', $this->form['meta'][$list]);
            }
        }
    }

    /* ---------- Constructeurs de blocs (ajouter, monter, descendre, supprimer) ---------- */

    public function addRow(string $list): void
    {
        $template = match ($list) {
            'sections' => ['title' => '', 'content' => ''],
            'references' => ['title' => '', 'url' => ''],
            'meta.blocks' => ['title' => '', 'description' => '', 'chart_type' => 'bar', 'data' => null],
            'meta.chapters' => ['time' => '', 'title' => ''],
            'meta.links' => ['label' => '', 'url' => ''],
            'meta.toc' => ['title' => '', 'page' => null, 'summary' => ''],
            default => null,
        };
        if ($template === null) {
            return;
        }
        $rows = data_get($this->form, $list, []);
        $rows[] = $template;
        data_set($this->form, $list, $rows);
    }

    public function moveRow(string $list, int $index, int $direction): void
    {
        $rows = array_values((array) data_get($this->form, $list, []));
        $target = $index + ($direction < 0 ? -1 : 1);
        if (! isset($rows[$index], $rows[$target])) {
            return;
        }
        [$rows[$index], $rows[$target]] = [$rows[$target], $rows[$index]];
        data_set($this->form, $list, $rows);
    }

    public function removeRow(string $list, int $index): void
    {
        $rows = array_values((array) data_get($this->form, $list, []));
        unset($rows[$index]);
        data_set($this->form, $list, array_values($rows));
    }

    public function addRelated(int $id): void
    {
        if (! in_array($id, $this->form['related'], true) && $id !== $this->contentId && count($this->form['related']) < 12) {
            $this->form['related'][] = $id;
        }
        $this->relatedSearch = '';
    }

    public function removeRelated(int $id): void
    {
        $this->form['related'] = array_values(array_diff($this->form['related'], [$id]));
    }

    /**
     * Résultat de l'analyse pdf.js faite dans le navigateur (pages + signets).
     *
     * @param  list<array{title?: mixed, page?: mixed}>  $outline
     */
    public function applyPdfInfo(int $pages, array $outline = []): void
    {
        $this->form['meta']['pages'] = max(1, min(5000, $pages));
        if ($outline !== [] && empty($this->form['meta']['toc'])) {
            $this->form['meta']['toc'] = collect($outline)->take(200)
                ->map(fn ($o) => ['title' => mb_substr(strip_tags((string) ($o['title'] ?? '')), 0, 300), 'page' => is_int($o['page'] ?? null) ? $o['page'] : null, 'summary' => ''])
                ->filter(fn ($o) => $o['title'] !== '')->values()->all();
        }
        $this->toast('PDF analysé : '.$this->form['meta']['pages'].' page(s).');
    }

    /* ---------- Enregistrement ---------- */

    public function save(string $action): void
    {
        $workflow = app(ContentWorkflow::class);
        $type = ContentType::from($this->type);
        $content = $this->contentId ? Content::query()->findOrFail($this->contentId) : null;

        try {
            $files = $this->storeUploads();
            $data = $this->form;
            $data['tags'] = (string) $data['tags'];
            $data['scheduled_at'] = $data['scheduled_date'] ? trim($data['scheduled_date'].' '.($data['scheduled_time'] ?: '')) : null;
            if ($action === 'schedule' && (! $data['scheduled_date'] || ! $data['scheduled_time'])) {
                throw ValidationException::withMessages(['scheduled_at' => 'Programmer exige une date ET une heure.']);
            }
            $saved = $workflow->save($this->me(), $content, $type, $data, $action, $files);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator?->errors() ?? $e->errors());
            $this->toast('Enregistrement impossible : vérifiez les champs signalés.', 'error');

            return;
        } catch (AuthorizationException $e) {
            $this->toast($e->getMessage(), 'error');

            return;
        }

        $this->resetErrorBag();
        $this->cover = $this->document = $this->download = null;
        $this->csv = [];
        $this->dispatch('content-saved');
        $messages = ['draft' => 'Brouillon enregistré.', 'review' => 'Contenu soumis en relecture.', 'publish' => 'Contenu publié.', 'schedule' => 'Publication programmée.'];
        session()->flash('toast', ['type' => 'success', 'message' => $messages[$action] ?? 'Enregistré.']);
        $this->redirectRoute('dashboard.contents.edit', ['type' => $this->type, 'content' => $saved->id]);
    }

    /** Sauvegarde automatique silencieuse d'un brouillon (cahier § 10 P1). */
    public function autosave(): void
    {
        if ($this->contentId === null || trim((string) $this->form['title']) === '') {
            return;
        }
        $content = Content::query()->find($this->contentId);
        if ($content === null || ! in_array($content->status->value, ['draft', 'review'], true)) {
            return;
        }
        try {
            $data = $this->form + ['scheduled_at' => null];
            app(ContentWorkflow::class)->save($this->me(), $content, ContentType::from($this->type), $data, $content->status->value === 'review' ? 'review' : 'draft');
            $this->dispatch('content-saved');
        } catch (ValidationException|AuthorizationException) {
            // Pas de bruit pendant la saisie : les erreurs s'afficheront au prochain enregistrement manuel.
        }
    }

    public function render(): View
    {
        $type = ContentType::from($this->type);
        $term = trim(mb_substr($this->relatedSearch, 0, 100));

        return view('livewire.dashboard.content-editor', [
            'contentType' => $type,
            'content' => $this->contentId ? Content::query()->find($this->contentId) : null,
            'canPublish' => app(ContentWorkflow::class)->canPublish($this->me()),
            'themes' => Taxonomy::THEMES,
            'series' => $type === ContentType::DocumentaryEpisode ? Content::query()->ofType(ContentType::DocumentarySeries)->orderBy('title')->get(['id', 'title']) : collect(),
            'relatedItems' => Content::query()->whereIn('id', $this->form['related'])->get(['id', 'title', 'type']),
            'relatedResults' => $term === '' ? collect() : Content::query()->where('title', 'like', '%'.addcslashes($term, '%_\\').'%')
                ->when($this->contentId, fn ($q) => $q->whereKeyNot($this->contentId))->limit(8)->get(['id', 'title', 'type']),
        ])->title(($this->contentId ? 'Modifier' : 'Nouveau').' · '.$type->label());
    }

    /**
     * Stocke les fichiers envoyés (validation par contenu dans UploadService).
     *
     * @return array<string, mixed>
     */
    private function storeUploads(): array
    {
        $uploads = app(UploadService::class);
        $files = [];
        if ($this->cover) {
            $files['cover_path'] = $uploads->cover($this->cover)['path'];
        }
        if ($this->document) {
            $doc = $uploads->pdf($this->document);
            $files += ['file_path' => $doc['path'], 'file_name' => $doc['name'], 'file_size' => $doc['size']];
        }
        foreach ($this->csv as $i => $file) {
            if ($file && isset($this->form['meta']['blocks'][$i])) {
                $this->form['meta']['blocks'][$i]['data'] = $uploads->csv($file);
            }
        }
        if ($this->download) {
            $stored = $uploads->download($this->download);
            $this->form['meta']['downloads'][] = ['label' => $this->downloadLabel ?: $stored['name'], 'path' => $stored['path'], 'size' => $stored['size']];
            $this->downloadLabel = '';
        }

        return $files;
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
