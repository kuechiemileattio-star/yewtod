<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\EventCounter;
use App\Services\WorksQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bibliothèque publique (cahier § 2.6, décision 5, bug n° 13) : fiche
 * 100 % en français, prix en euros, boutons d'achat masqués sans lien.
 */
class BookController extends Controller
{
    public const PER_PAGE = 16;

    public const SORTS = ['recent' => 'Plus récents', 'titre' => 'Titre A–Z'];

    public function index(Request $request): View
    {
        $q = mb_substr(trim(is_string($request->query('q')) ? $request->query('q') : ''), 0, WorksQuery::MAX_QUERY);
        $category = is_string($request->query('categorie')) && array_key_exists($request->query('categorie'), Book::CATEGORIES) ? $request->query('categorie') : null;
        $author = is_string($request->query('auteur')) ? mb_substr($request->query('auteur'), 0, 200) : null;
        $yearRaw = $request->query('annee');
        $year = is_string($yearRaw) && ctype_digit($yearRaw) && strlen($yearRaw) === 4 ? (int) $yearRaw : null;
        $sort = is_string($request->query('tri')) && array_key_exists($request->query('tri'), self::SORTS) ? $request->query('tri') : 'recent';

        $query = Book::query()->published()
            ->when($q !== '', function (Builder $b) use ($q) {
                $like = '%'.WorksQuery::escapeLike($q).'%';
                $b->where(fn (Builder $w) => $w->where('title', 'like', $like)->orWhere('author', 'like', $like));
            })
            ->when($category, fn (Builder $b) => $b->where('category', $category))
            // L'auteur est comparé à une valeur existante (liste fermée), jamais concaténé.
            ->when($author, fn (Builder $b) => $b->where('author', $author))
            ->when($year, fn (Builder $b) => $b->where('year', $year));
        $query = $sort === 'titre' ? $query->orderBy('title')->orderBy('id') : $query->latestFirst();

        $base = Book::query()->published();
        $filtered = $q !== '' || $category || $author || $year;

        return view('site.books.index', [
            'books' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'filters' => compact('q', 'category', 'author', 'year', 'sort'),
            'filtered' => $filtered,
            'total' => (clone $base)->count(),
            'authors' => (clone $base)->distinct()->orderBy('author')->limit(300)->pluck('author')->all(),
            'years' => (clone $base)->whereNotNull('year')->distinct()->orderByDesc('year')->limit(200)->pluck('year')->all(),
            'categoryCounts' => (clone $base)->selectRaw('category, COUNT(*) AS aggregate')->groupBy('category')->pluck('aggregate', 'category')->all(),
            'sorts' => self::SORTS,
        ]);
    }

    public function show(string $bookSlug, EventCounter $counter): View
    {
        $book = $this->findPublished($bookSlug);
        $counter->record($book, 'view');

        // « À lire aussi » : sélection manuelle, sinon 4 livres de la même catégorie (cahier § 5.2).
        $alsoRead = $book->related()->published()->limit(4)->get();
        if ($alsoRead->isEmpty()) {
            $alsoRead = Book::query()->published()->where('category', $book->category)->whereKeyNot($book->id)->latestFirst()->limit(4)->get();
        }

        return view('site.books.show', ['book' => $book, 'alsoRead' => $alsoRead]);
    }

    /** Extrait de chapitre téléchargeable (disque privé, visibilité vérifiée, comptage). */
    public function excerpt(string $bookSlug, int $index, EventCounter $counter): StreamedResponse
    {
        $book = $this->findPublished($bookSlug);
        $excerpt = $book->excerpts[$index] ?? null;
        $path = is_array($excerpt) ? (string) ($excerpt['path'] ?? '') : '';
        abort_unless(str_starts_with($path, 'excerpts/') && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);
        $counter->record($book, 'download');

        return Storage::disk('local')->download($path, Str::slug($book->title.' '.($excerpt['label'] ?? 'extrait')).'.pdf', ['Cache-Control' => 'private, no-store']);
    }

    /** Lecture du PDF de l'ebook hébergé (affiché dans le navigateur). */
    public function ebook(string $bookSlug, EventCounter $counter): StreamedResponse
    {
        $book = $this->findPublished($bookSlug);
        abort_unless($book->ebook_path !== null && str_starts_with($book->ebook_path, 'ebooks/') && Storage::disk('local')->exists($book->ebook_path), 404);
        $counter->record($book, 'download');

        return Storage::disk('local')->response($book->ebook_path, Str::slug($book->title).'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ], 'inline');
    }

    private function findPublished(string $slug): Book
    {
        abort_if(strlen($slug) > 320, 404);

        return Book::query()->published()->where('slug', $slug)->firstOrFail();
    }
}
