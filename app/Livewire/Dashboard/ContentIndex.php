<?php

namespace App\Livewire\Dashboard;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Livewire\Concerns\Toasts;
use App\Models\Content;
use App\Models\User;
use App\Services\ContentWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listes de contenus du dashboard (cahier § 4.2, § 10 P2) : grille de cartes,
 * filtres, recherche, tri, pagination, actions groupées, aperçu.
 * Filtres et tri passent par des listes blanches (cahier § 8.6).
 */
#[Layout('layouts::dashboard')]
class ContentIndex extends Component
{
    use Toasts, WithPagination;

    private const SORTS = ['recent', 'ancien', 'titre'];

    /** Type fixé par la route (null = « Autres publications »). */
    #[Locked]
    public ?string $type = null;

    #[Url(as: 'statut')]
    public string $status = '';

    #[Url(as: 'genre')]
    public string $filterType = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'tri')]
    public string $sort = 'recent';

    /** @var list<int> */
    public array $selected = [];

    public ?int $previewId = null;

    public string $newType = '';

    public function mount(?string $type = null): void
    {
        abort_unless($this->me()->hasPermission('manage_articles'), 403);
        if ($type !== null) {
            abort_unless(ContentType::tryFrom($type) !== null, 404);
        }
        $this->type = $type;
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['status', 'filterType', 'search', 'sort'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function create(): void
    {
        $type = $this->type ?? (ContentType::tryFrom($this->newType)?->value);
        if ($type === null || ($this->type === null && ! in_array(ContentType::from($type), ContentType::others(), true))) {
            $this->toast('Choisissez d\'abord le type de publication.', 'error');

            return;
        }
        $this->redirectRoute('dashboard.contents.create', ['type' => $type]);
    }

    public function delete(int $id): void
    {
        $content = $this->scoped()->findOrFail($id);
        $this->attempt(fn () => app(ContentWorkflow::class)->delete($this->me(), $content), "« {$content->title} » supprimé.");
    }

    public function bulk(string $action): void
    {
        $status = match ($action) {
            'publish' => ContentStatus::Published,
            'unpublish' => ContentStatus::Draft,
            'archive' => ContentStatus::Archived,
            'delete' => null,
            default => false,
        };
        if ($status === false) {
            return;
        }
        $done = 0;
        foreach ($this->scoped()->whereIn('id', $this->selected)->get() as $content) {
            $ok = $this->attempt(fn () => $status === null
                ? app(ContentWorkflow::class)->delete($this->me(), $content)
                : app(ContentWorkflow::class)->setStatus($this->me(), $content, $status), '');
            $done += (int) $ok;
        }
        $this->selected = [];
        $this->toast("{$done} contenu(s) traité(s).");
    }

    public function preview(int $id): void
    {
        $this->previewId = $this->scoped()->whereKey($id)->value('id');
    }

    public function closeModal(): void
    {
        $this->previewId = null;
    }

    public function render(): View
    {
        $statusFilter = ContentStatus::tryFrom($this->status);
        $typeFilter = $this->type === null ? ContentType::tryFrom($this->filterType) : null;
        $sort = in_array($this->sort, self::SORTS, true) ? $this->sort : 'recent';
        $term = trim(mb_substr($this->search, 0, 100));

        $query = $this->scoped()->with(['author', 'parent'])
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter->value))
            ->when($typeFilter && in_array($typeFilter, ContentType::others(), true), fn ($q) => $q->where('type', $typeFilter->value))
            ->when($term !== '', fn ($q) => $q->where('title', 'like', '%'.addcslashes($term, '%_\\').'%'));

        $query = match ($sort) {
            'ancien' => $query->orderBy('created_at')->orderBy('id'),
            'titre' => $query->orderBy('title'),
            default => $query->orderByDesc('updated_at')->orderByDesc('id'),
        };

        $title = $this->type ? ContentType::from($this->type)->plural() : 'Autres publications';

        return view('livewire.dashboard.content-index', [
            'contents' => $query->paginate(24),
            'statuses' => ContentStatus::cases(),
            'otherTypes' => ContentType::others(),
            'canPublish' => app(ContentWorkflow::class)->canPublish($this->me()),
            'previewed' => $this->previewId ? Content::query()->with('author')->find($this->previewId) : null,
            'heading' => $title,
        ])->title($title);
    }

    /** @return Builder<Content> */
    private function scoped(): Builder
    {
        return Content::query()->when(
            $this->type !== null,
            fn ($q) => $q->where('type', $this->type),
            fn ($q) => $q->whereIn('type', array_map(fn (ContentType $t) => $t->value, ContentType::others())),
        );
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
