<?php

namespace App\Models;

use App\Contracts\Trackable;
use App\Enums\ContentStatus;
use App\Http\Controllers\Site\SeoController;
use App\Services\ImageProcessor;
use App\Services\SiteSettings;
use Carbon\CarbonImmutable;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Livre de la bibliothèque commentée (cahier § 3.1).
 *
 * Affectation de masse : seuls les champs éditoriaux sont « fillable ».
 * Statut, dates, créateur, fichiers privés et compteurs sont écrits par
 * BookService (forceFill), jamais depuis une requête.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $author
 * @property string|null $publisher
 * @property int|null $year
 * @property int|null $month
 * @property string $category
 * @property string|null $difficulty
 * @property int|null $pages
 * @property string|null $doi
 * @property string|null $cover_path
 * @property string|null $cover_alt
 * @property string|null $summary
 * @property string $review
 * @property list<string> $quotes
 * @property string|null $external_url
 * @property string|null $isbn_print
 * @property string|null $isbn_ebook
 * @property string|null $price_print
 * @property string|null $price_ebook
 * @property string|null $buy_print_url
 * @property string|null $buy_ebook_url
 * @property string|null $ebook_path
 * @property int|null $ebook_size
 * @property string|null $ebook_description
 * @property list<array{title: string, page: int|null}> $composition
 * @property list<array{label: string, path: string, size: int|null}> $excerpts
 * @property string|null $description
 * @property string|null $author_bio
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $scheduled_at
 * @property int|null $created_by
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property int $views_count
 * @property int $downloads_count
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class Book extends Model implements Trackable
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    public const CATEGORIES = [
        'ia' => 'Intelligence artificielle', 'economie' => 'Économie', 'politique' => 'Politique', 'physique' => 'Physique',
        'mathematiques' => 'Mathématiques', 'philosophie' => 'Philosophie', 'entrepreneuriat' => 'Entrepreneuriat',
        'histoire' => 'Histoire', 'sciences-sociales' => 'Sciences sociales',
    ];

    public const DIFFICULTIES = ['accessible' => 'Accessible', 'intermediaire' => 'Intermédiaire', 'exigeant' => 'Exigeant'];

    protected $fillable = [
        'title', 'author', 'publisher', 'year', 'month', 'category', 'difficulty', 'pages', 'doi', 'cover_alt',
        'summary', 'review', 'quotes', 'external_url', 'isbn_print', 'isbn_ebook', 'price_print', 'price_ebook',
        'buy_print_url', 'buy_ebook_url', 'ebook_description', 'composition', 'description', 'author_bio',
        'seo_title', 'seo_description',
    ];

    protected $attributes = [
        'status' => 'draft', 'quotes' => '[]', 'composition' => '[]', 'excerpts' => '[]', 'review' => '',
        'publisher' => null, 'year' => null, 'month' => null, 'difficulty' => null, 'pages' => null, 'doi' => null,
        'cover_path' => null, 'cover_alt' => null, 'summary' => null, 'external_url' => null, 'isbn_print' => null,
        'isbn_ebook' => null, 'price_print' => null, 'price_ebook' => null, 'buy_print_url' => null, 'buy_ebook_url' => null,
        'ebook_path' => null, 'ebook_size' => null, 'ebook_description' => null, 'description' => null, 'author_bio' => null,
        'published_at' => null, 'scheduled_at' => null, 'created_by' => null, 'seo_title' => null, 'seo_description' => null,
        'views_count' => 0, 'downloads_count' => 0,
    ];

    protected static function booted(): void
    {
        static::saved(fn () => SeoController::flush());
        static::deleted(fn () => SeoController::flush());
    }

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'quotes' => 'array',
            'composition' => 'array',
            'excerpts' => 'array',
            'price_print' => 'decimal:2',
            'price_ebook' => 'decimal:2',
        ];
    }

    /** @return BelongsToMany<Book, $this> */
    public function related(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_related', 'book_id', 'related_id')->withPivot('position')->orderByPivot('position');
    }

    /**
     * Même règle de visibilité que les publications (cahier § 5.1.2).
     *
     * @param  Builder<Book>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('status', ContentStatus::Published->value)
            ->orWhere(fn (Builder $s) => $s->where('status', ContentStatus::Scheduled->value)->where('scheduled_at', '<=', now())));
    }

    /** @param Builder<Book> $query */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByRaw('COALESCE(published_at, created_at) DESC')->orderByDesc('id');
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === ContentStatus::Published
            || ($this->status === ContentStatus::Scheduled && $this->scheduled_at !== null && $this->scheduled_at->isPast());
    }

    public function displayDate(): CarbonImmutable
    {
        return ($this->published_at ?? $this->created_at)->setTimezone(config('app.display_timezone'));
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /** « mars 2024 », « 2024 » ou chaîne vide. */
    public function releaseLabel(): string
    {
        if ($this->year === null) {
            return '';
        }

        return $this->month ? CarbonImmutable::create($this->year, $this->month, 1)->translatedFormat('F Y') : (string) $this->year;
    }

    /** Prix en euros, format français (« 19,90 € »). */
    public static function euros(?string $price): ?string
    {
        return $price === null ? null : number_format((float) $price, 2, ',', "\u{202F}")."\u{202F}€";
    }

    public function publicUrl(): string
    {
        return url('/books/'.$this->slug);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function coverSrcset(): string
    {
        return ImageProcessor::srcset($this->cover_path);
    }

    public function excerpt(int $limit = 160): string
    {
        return Str::limit(trim(strip_tags((string) ($this->summary ?: $this->review))), $limit);
    }

    public function citation(): string
    {
        return sprintf('%s (%s). %s.%s', $this->author, $this->year ?? 's. d.', $this->title, $this->publisher ? ' '.$this->publisher.'.' : '');
    }

    /** @return array<string, mixed> */
    public function jsonLd(): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Book',
            'name' => $this->title,
            'author' => ['@type' => 'Person', 'name' => $this->author],
            'url' => $this->publicUrl(),
            'inLanguage' => 'fr',
            'description' => $this->excerpt(300),
            'review' => ['@type' => 'Review', 'reviewBody' => Str::limit($this->review, 500), 'author' => ['@type' => 'Organization', 'name' => app(SiteSettings::class)->siteName()]],
        ];
        foreach (['publisher' => $this->publisher ? ['@type' => 'Organization', 'name' => $this->publisher] : null, 'numberOfPages' => $this->pages,
            'isbn' => $this->isbn_print ?: $this->isbn_ebook, 'image' => $this->coverUrl(), 'datePublished' => $this->year ? (string) $this->year : null] as $k => $v) {
            if ($v !== null) {
                $data[$k] = $v;
            }
        }
        $offers = [];
        foreach ([['price_print', 'buy_print_url'], ['price_ebook', 'buy_ebook_url']] as [$price, $link]) {
            if ($this->{$price} !== null && $this->{$link}) {
                $offers[] = ['@type' => 'Offer', 'price' => $this->{$price}, 'priceCurrency' => 'EUR', 'url' => $this->{$link}];
            }
        }
        if ($offers) {
            $data['offers'] = $offers;
        }

        return $data;
    }
}
