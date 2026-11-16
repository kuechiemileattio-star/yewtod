<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Book;
use App\Models\User;
use App\Rules\HttpUrl;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Enregistrement des livres (cahier § 3.1, § 4.4, § 5.1). Toutes les
 * règles sont ici, côté serveur : droits (`manage_books`), validation,
 * slug unique (manuel ou automatique), programmation, liens http(s).
 */
class BookService
{
    public const ACTIONS = ['draft', 'publish', 'schedule'];

    public function can(User $user): bool
    {
        return $user->isActive() && $user->hasPermission(Permission::ManageBooks);
    }

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        $url = ['nullable', new HttpUrl];
        $price = ['nullable', 'numeric', 'min:0', 'max:99999.99', 'decimal:0,2'];
        $isbn = ['nullable', 'string', 'max:20', 'regex:/^(97[89])?[\d\- ]{9,16}[\dXx]$/'];

        return [
            'title' => ['required', 'string', 'min:2', 'max:300'],
            'slug' => ['nullable', 'string', 'max:300', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'author' => ['required', 'string', 'max:200'],
            'publisher' => ['nullable', 'string', 'max:200'],
            'year' => ['nullable', 'integer', 'min:1400', 'max:'.(now()->year + 2)],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'category' => ['required', Rule::in(array_keys(Book::CATEGORIES))],
            'difficulty' => ['nullable', Rule::in(array_keys(Book::DIFFICULTIES))],
            'pages' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'doi' => ['nullable', 'string', 'max:200', 'regex:/^10\.\d{4,9}\/\S+$/'],
            'cover_alt' => ['nullable', 'string', 'max:300'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'review' => ['required', 'string', 'max:20000'],
            'quotes' => ['array', 'max:30'],
            'quotes.*' => ['string', 'max:1000'],
            'external_url' => $url,
            'isbn_print' => $isbn,
            'isbn_ebook' => $isbn,
            'price_print' => $price,
            'price_ebook' => $price,
            'buy_print_url' => $url,
            'buy_ebook_url' => $url,
            'ebook_description' => ['nullable', 'string', 'max:5000'],
            'composition' => ['array', 'max:300'],
            'composition.*.title' => ['required', 'string', 'max:300'],
            'composition.*.page' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'author_bio' => ['nullable', 'string', 'max:5000'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'related' => ['array', 'max:12'],
            'related.*' => ['integer', 'distinct'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data  Formulaire (non fiable).
     * @param  array<string, mixed>  $files  cover_path, ebook_path, ebook_size, excerpts (déjà stockés).
     */
    public function save(User $user, ?Book $book, array $data, string $action, array $files = []): Book
    {
        if (! in_array($action, self::ACTIONS, true)) {
            throw ValidationException::withMessages(['action' => 'Action inconnue.']);
        }
        if (! $this->can($user)) {
            throw new AuthorizationException('Vous n\'avez pas la permission de gérer les livres.');
        }

        $data = $this->normalize($data);
        $validated = Validator::make($data, self::rules(), [], [
            'review' => 'avis personnel', 'author' => 'auteur', 'category' => 'catégorie', 'price_print' => 'prix papier', 'price_ebook' => 'prix ebook',
            'isbn_print' => 'ISBN papier', 'isbn_ebook' => 'ISBN ebook', 'buy_print_url' => 'lien d\'achat papier', 'buy_ebook_url' => 'lien d\'achat ebook',
        ])->validate();

        $scheduledAt = null;
        if ($action === 'schedule') {
            if (empty($validated['scheduled_at'])) {
                throw ValidationException::withMessages(['scheduled_at' => 'Indiquez la date et l\'heure de publication.']);
            }
            $scheduledAt = Carbon::parse($validated['scheduled_at'], config('app.display_timezone'))->utc();
            if ($scheduledAt->isPast()) {
                throw ValidationException::withMessages(['scheduled_at' => 'La date de programmation doit être dans le futur.']);
            }
        }

        $oldFiles = $book ? array_filter(['ebook_path' => $book->ebook_path, 'cover_path' => $book->cover_path]) : [];

        $saved = DB::transaction(function () use ($user, $book, $validated, $action, $files, $scheduledAt) {
            $book = $book === null ? new Book : Book::query()->lockForUpdate()->findOrFail($book->id);
            if (! $book->exists) {
                $book->forceFill(['created_by' => $user->id]);
            }
            $book->fill(collect($validated)->except(['slug', 'related', 'scheduled_at'])->all());
            $book->forceFill(collect($files)->only(['cover_path', 'ebook_path', 'ebook_size', 'excerpts'])->all());
            $book->forceFill(['slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['title'], $book->id)]);

            match ($action) {
                'publish' => $book->forceFill(['status' => ContentStatus::Published, 'scheduled_at' => null, 'published_at' => $book->published_at ?? now()]),
                'schedule' => $book->forceFill(['status' => ContentStatus::Scheduled, 'scheduled_at' => $scheduledAt, 'published_at' => $scheduledAt]),
                default => $book->forceFill(['status' => ContentStatus::Draft, 'scheduled_at' => null]),
            };
            $book->save();

            $ids = Book::query()->whereIn('id', $validated['related'] ?? [])->whereKeyNot($book->id)->pluck('id')->all();
            $book->related()->sync(collect(array_values(array_intersect($validated['related'] ?? [], $ids)))->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->all());

            return $book;
        });

        // Fichiers remplacés : l'ancien ebook et l'ancienne couverture ne restent pas orphelins.
        if (isset($files['ebook_path'], $oldFiles['ebook_path']) && $files['ebook_path'] !== $oldFiles['ebook_path']) {
            Storage::disk('local')->delete($oldFiles['ebook_path']);
        }
        if (isset($files['cover_path'], $oldFiles['cover_path']) && $files['cover_path'] !== $oldFiles['cover_path']) {
            app(ImageProcessor::class)->delete($oldFiles['cover_path']);
        }

        Audit::record('book.'.($action === 'publish' ? 'published' : ($action === 'schedule' ? 'scheduled' : 'saved')), $saved, ['status' => $saved->status->value]);

        return $saved;
    }

    public function delete(User $user, Book $book): void
    {
        if (! $this->can($user)) {
            throw new AuthorizationException('Vous ne pouvez pas supprimer ce livre.');
        }
        DB::transaction(function () use ($book) {
            Storage::disk('local')->delete(array_values(array_filter([$book->ebook_path, ...array_column($book->excerpts, 'path')])));
            app(ImageProcessor::class)->delete($book->cover_path);
            $book->delete();
        });
        Audit::record('book.deleted', $book, ['title' => $book->title]);
    }

    /** Bascule des livres programmés échus (appelée avec celle des publications). */
    public static function publishDue(): int
    {
        return DB::transaction(function () {
            $due = Book::query()->where('status', ContentStatus::Scheduled->value)->where('scheduled_at', '<=', now())->lockForUpdate()->get();
            foreach ($due as $book) {
                $book->forceFill(['status' => ContentStatus::Published, 'published_at' => $book->scheduled_at])->save();
                Audit::record('book.auto_published', $book);
            }

            return $due->count();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $data[$k] = trim($v) === '' ? null : trim($v);
            }
        }
        $quotes = is_string($data['quotes'] ?? null) ? preg_split('/\R{2,}|\R—\R/u', $data['quotes']) : (array) ($data['quotes'] ?? []);
        $data['quotes'] = collect($quotes)->map(fn ($q) => trim((string) $q))->filter()->values()->all();
        $data['composition'] = collect((array) ($data['composition'] ?? []))
            ->filter(fn ($r) => is_array($r) && trim((string) ($r['title'] ?? '')) !== '')
            ->map(fn ($r) => ['title' => trim((string) $r['title']), 'page' => is_numeric($r['page'] ?? null) ? (int) $r['page'] : null])->values()->all();
        foreach (['price_print', 'price_ebook'] as $p) {
            if (is_string($data[$p] ?? null)) {
                $data[$p] = str_replace([',', ' ', '€'], ['.', '', ''], $data[$p]);
            }
        }
        $data['slug'] = filled($data['slug'] ?? null) ? Str::slug((string) $data['slug']) : null;
        $data['review'] ??= '';

        return $data;
    }

    private function uniqueSlug(?string $wanted, string $title, ?int $ignoreId): string
    {
        $base = Str::limit(Str::slug($wanted ?: $title), 280, '') ?: 'livre';
        $slug = $base;
        $i = 2;
        while (Book::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->lockForUpdate()->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
