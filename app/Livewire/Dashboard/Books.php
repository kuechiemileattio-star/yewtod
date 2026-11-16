<?php

namespace App\Livewire\Dashboard;

use App\Enums\ContentStatus;
use App\Jobs\AnalyzePdfWithAi;
use App\Livewire\Concerns\Toasts;
use App\Models\AiAnalysis;
use App\Models\Book;
use App\Models\User;
use App\Services\BookService;
use App\Services\GeminiAnalyzer;
use App\Services\UploadService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Livres du dashboard (cahier § 4.4) : liste filtrable, aperçu modal,
 * éditeur en fenêtre modale avec tous les champs de la section 3.1,
 * analyse du PDF de l'ebook (navigateur puis IA). La validation, les droits
 * et la programmation sont dans BookService.
 */
#[Layout('layouts::dashboard')]
class Books extends Component
{
    use Toasts, WithFileUploads, WithPagination;

    public const TABS = ['identite' => 'Identité', 'avis' => 'Avis Yewtod', 'vente' => 'Vente', 'ebook' => 'Version électronique', 'fiche' => 'Fiche longue', 'publication' => 'Publication'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'categorie')]
    public string $category = '';

    #[Url(as: 'statut')]
    public string $status = '';

    public ?int $previewId = null;

    public bool $editing = false;

    public ?int $bookId = null;

    public string $tab = 'identite';

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    /** @var TemporaryUploadedFile|null */
    public $ebook = null;

    /** @var TemporaryUploadedFile|null */
    public $excerpt = null;

    public string $excerptLabel = '';

    public string $relatedSearch = '';

    public ?int $analysisId = null;

    public function mount(): void
    {
        abort_unless($this->me()->hasPermission('manage_books'), 403);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'category', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->open(new Book);
    }

    public function edit(int $id): void
    {
        $this->open(Book::query()->findOrFail($id));
    }

    public function preview(int $id): void
    {
        $this->previewId = Book::query()->whereKey($id)->value('id');
    }

    public function closeModal(): void
    {
        $this->previewId = null;
        $this->editing = false;
        $this->reset(['bookId', 'form', 'cover', 'ebook', 'excerpt', 'excerptLabel', 'analysisId', 'relatedSearch']);
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        $book = Book::query()->findOrFail($id);
        $this->attempt(fn () => app(BookService::class)->delete($this->me(), $book), "« {$book->title} » supprimé.");
    }

    /* ---------- Listes de l'éditeur ---------- */

    public function addComposition(): void
    {
        $this->form['composition'][] = ['title' => '', 'page' => null];
    }

    public function removeComposition(int $i): void
    {
        unset($this->form['composition'][$i]);
        $this->form['composition'] = array_values($this->form['composition']);
    }

    public function removeExcerpt(int $i): void
    {
        unset($this->form['excerpts'][$i]);
        $this->form['excerpts'] = array_values($this->form['excerpts']);
    }

    public function addRelated(int $id): void
    {
        if ($id !== $this->bookId && ! in_array($id, $this->form['related'], true) && count($this->form['related']) < 12) {
            $this->form['related'][] = $id;
        }
        $this->relatedSearch = '';
    }

    public function removeRelated(int $id): void
    {
        $this->form['related'] = array_values(array_diff($this->form['related'], [$id]));
    }

    /**
     * Signets du PDF (pdf.js dans le navigateur) → composition de l'ouvrage.
     *
     * @param  list<array{title?: mixed, page?: mixed}>  $outline
     */
    public function applyPdfInfo(int $pages, array $outline = []): void
    {
        $this->form['pages'] = max(1, min(20000, $pages));
        if ($outline !== [] && empty($this->form['composition'])) {
            $this->form['composition'] = collect($outline)->take(300)
                ->map(fn ($o) => ['title' => mb_substr(strip_tags((string) ($o['title'] ?? '')), 0, 300), 'page' => is_int($o['page'] ?? null) ? $o['page'] : null])
                ->filter(fn ($o) => $o['title'] !== '')->values()->all();
        }
        $this->toast('PDF analysé : '.$this->form['pages'].' page(s).');
    }

    /* ---------- Enregistrement ---------- */

    public function save(string $action): void
    {
        $book = $this->bookId ? Book::query()->findOrFail($this->bookId) : null;
        try {
            $files = $this->storeUploads();
            $data = $this->form;
            $data['scheduled_at'] = $data['scheduled_date'] ? trim($data['scheduled_date'].' '.($data['scheduled_time'] ?: '')) : null;
            if ($action === 'schedule' && (! $data['scheduled_date'] || ! $data['scheduled_time'])) {
                throw ValidationException::withMessages(['scheduled_at' => 'Programmer exige une date ET une heure.']);
            }
            $saved = app(BookService::class)->save($this->me(), $book, $data, $action, $files);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator?->errors() ?? $e->errors());
            $this->toast('Enregistrement impossible : vérifiez les champs signalés.', 'error');

            return;
        } catch (AuthorizationException $e) {
            $this->toast($e->getMessage(), 'error');

            return;
        }

        $messages = ['draft' => 'Brouillon enregistré.', 'publish' => 'Livre publié.', 'schedule' => 'Publication programmée.'];
        $this->toast($messages[$action]);
        $this->open($saved);
    }

    /* ---------- Analyse IA ---------- */

    public function analyzeWithAi(): void
    {
        if (! GeminiAnalyzer::enabled()) {
            $this->toast('Analyse IA indisponible : la clé Gemini n\'est pas configurée.', 'error');

            return;
        }
        $book = $this->bookId ? Book::query()->find($this->bookId) : null;
        if ($book?->ebook_path === null) {
            $this->toast('Enregistrez d\'abord le livre avec le PDF de l\'ebook : l\'IA n\'analyse que des fichiers stockés sur le site.', 'error');

            return;
        }
        $analysis = new AiAnalysis;
        $analysis->forceFill(['analyzable_type' => Book::class, 'analyzable_id' => $book->id, 'requested_by' => $this->me()->id])->save();
        AnalyzePdfWithAi::dispatch($analysis->id, $book->ebook_path, 'book');
        $this->analysisId = $analysis->id;
        $this->toast('Analyse lancée : le résultat s\'affichera ici d\'ici une minute.');
    }

    public function checkAnalysis(): void
    {
        $analysis = $this->analysisId ? AiAnalysis::query()->find($this->analysisId) : null;
        if ($analysis === null || in_array($analysis->status, ['pending', 'running'], true)) {
            return;
        }
        $this->analysisId = null;
        if ($analysis->status === 'failed') {
            $this->toast((string) $analysis->error, 'error');

            return;
        }
        $r = (array) $analysis->result;
        if (! empty($r['summary'])) {
            $this->form['description'] = $r['summary'];
        }
        if (! empty($r['author_bio'])) {
            $this->form['author_bio'] = $r['author_bio'];
        }
        if (! empty($r['pages'])) {
            $this->form['pages'] = $r['pages'];
        }
        if (! empty($r['sections'])) {
            $this->form['composition'] = array_map(fn ($s) => ['title' => $s['title'], 'page' => null], $r['sections']);
        }
        $this->toast('Analyse IA appliquée (description, biographie, composition) : vérifiez avant d\'enregistrer.');
    }

    public function render(): View
    {
        $term = trim(mb_substr($this->search, 0, 100));
        $like = '%'.addcslashes($term, '%_\\').'%';
        $books = Book::query()
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('author', 'like', $like)))
            ->when(array_key_exists($this->category, Book::CATEGORIES), fn ($q) => $q->where('category', $this->category))
            ->when(ContentStatus::tryFrom($this->status), fn ($q, $s) => $q->where('status', $s->value))
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(24);

        $rterm = trim(mb_substr($this->relatedSearch, 0, 100));

        return view('livewire.dashboard.books', [
            'books' => $books,
            'previewed' => $this->previewId ? Book::query()->find($this->previewId) : null,
            'current' => $this->bookId ? Book::query()->find($this->bookId) : null,
            'aiEnabled' => GeminiAnalyzer::enabled(),
            'relatedItems' => $this->editing ? Book::query()->whereIn('id', $this->form['related'] ?? [])->get(['id', 'title']) : collect(),
            'relatedResults' => $this->editing && $rterm !== '' ? Book::query()->where('title', 'like', '%'.addcslashes($rterm, '%_\\').'%')
                ->when($this->bookId, fn ($q) => $q->whereKeyNot($this->bookId))->limit(8)->get(['id', 'title']) : collect(),
        ])->title('Livres');
    }

    private function open(Book $b): void
    {
        $this->previewId = null;
        $this->editing = true;
        $this->bookId = $b->exists ? $b->id : null;
        $tz = config('app.display_timezone');
        $this->form = [
            'title' => $b->title ?? '', 'slug' => $b->slug ?? '', 'author' => $b->author ?? '', 'publisher' => $b->publisher ?? '',
            'year' => $b->year, 'month' => $b->month, 'category' => $b->category ?? 'sciences-sociales', 'difficulty' => $b->difficulty ?? '',
            'pages' => $b->pages, 'doi' => $b->doi ?? '', 'cover_alt' => $b->cover_alt ?? '',
            'summary' => $b->summary ?? '', 'review' => $b->review ?? '', 'quotes' => implode("\n\n", $b->quotes ?? []), 'external_url' => $b->external_url ?? '',
            'isbn_print' => $b->isbn_print ?? '', 'isbn_ebook' => $b->isbn_ebook ?? '',
            'price_print' => $b->price_print !== null ? str_replace('.', ',', $b->price_print) : '', 'price_ebook' => $b->price_ebook !== null ? str_replace('.', ',', $b->price_ebook) : '',
            'buy_print_url' => $b->buy_print_url ?? '', 'buy_ebook_url' => $b->buy_ebook_url ?? '',
            'ebook_description' => $b->ebook_description ?? '', 'composition' => $b->composition ?? [], 'excerpts' => $b->excerpts ?? [],
            'description' => $b->description ?? '', 'author_bio' => $b->author_bio ?? '',
            'seo_title' => $b->seo_title ?? '', 'seo_description' => $b->seo_description ?? '',
            'related' => $b->exists ? $b->related()->pluck('books.id')->all() : [],
            'scheduled_date' => $b->scheduled_at?->setTimezone($tz)->format('Y-m-d') ?? '',
            'scheduled_time' => $b->scheduled_at?->setTimezone($tz)->format('H:i') ?? '',
        ];
        $this->reset(['cover', 'ebook', 'excerpt', 'excerptLabel']);
        $this->resetErrorBag();
    }

    /** @return array<string, mixed> */
    private function storeUploads(): array
    {
        $uploads = app(UploadService::class);
        $files = [];
        if ($this->cover) {
            $files['cover_path'] = $uploads->cover($this->cover, 'books')['path'];
        }
        if ($this->ebook) {
            $pdf = $uploads->pdf($this->ebook, 'ebooks');
            $files += ['ebook_path' => $pdf['path'], 'ebook_size' => $pdf['size']];
        }
        // Les extraits conservés sont ceux déjà en base (jamais un chemin envoyé par le navigateur).
        $current = $this->bookId ? (Book::query()->find($this->bookId)->excerpts ?? []) : [];
        $kept = array_values(array_filter($current, fn ($e) => in_array($e['path'], array_column((array) $this->form['excerpts'], 'path'), true)));
        foreach (array_diff(array_column($current, 'path'), array_column($kept, 'path')) as $removed) {
            Storage::disk('local')->delete($removed);
        }
        if ($this->excerpt) {
            $pdf = $uploads->pdf($this->excerpt, 'excerpts', UploadService::MAX_ATTACHMENT_KB * 2);
            $kept[] = ['label' => mb_substr(trim($this->excerptLabel) ?: 'Extrait', 0, 200), 'path' => $pdf['path'], 'size' => $pdf['size']];
        }
        $files['excerpts'] = array_slice($kept, 0, 20);
        $this->cover = $this->ebook = $this->excerpt = null;
        $this->excerptLabel = '';

        return $files;
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
