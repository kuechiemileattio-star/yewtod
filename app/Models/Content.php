<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Services\ImageProcessor;
use Carbon\CarbonImmutable;
use Database\Factories\ContentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Publication (8 types, modèle unifié — cahier § 3.2).
 *
 * Affectation de masse : seuls les champs éditoriaux simples sont
 * « fillable ». Statut, dates de publication, auteur, série, numéro
 * d'épisode, fichiers et compteurs sont écrits par ContentWorkflow.
 *
 * @property int $id
 * @property ContentType $type
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string|null $cover_path
 * @property string|null $cover_alt
 * @property string|null $file_path
 * @property string|null $file_name
 * @property int|null $file_size
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $scheduled_at
 * @property int|null $author_id
 * @property int|null $parent_id
 * @property int|null $episode_number
 * @property bool $is_featured
 * @property string $language
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property int $reading_time
 * @property int $views_count
 * @property int $downloads_count
 * @property list<array{title: string, content: string}> $sections
 * @property array<string, mixed> $meta
 * @property list<array{title: string, url: string|null}> $references
 * @property list<string> $tags
 * @property list<string> $themes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read User|null $author
 * @property-read Content|null $parent
 */
class Content extends Model
{
    /** @use HasFactory<ContentFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'summary', 'cover_alt', 'is_featured', 'language', 'seo_title', 'seo_description',
        'sections', 'meta', 'references', 'tags', 'themes',
    ];

    protected $attributes = [
        'status' => 'draft', 'sections' => '[]', 'meta' => '{}', 'references' => '[]', 'tags' => '[]', 'themes' => '[]',
        'is_featured' => false, 'language' => 'fr', 'reading_time' => 1, 'views_count' => 0, 'downloads_count' => 0,
        'summary' => null, 'cover_path' => null, 'cover_alt' => null, 'file_path' => null, 'file_name' => null, 'file_size' => null,
        'published_at' => null, 'scheduled_at' => null, 'author_id' => null, 'parent_id' => null, 'episode_number' => null,
        'seo_title' => null, 'seo_description' => null,
    ];

    protected function casts(): array
    {
        return [
            'type' => ContentType::class,
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'is_featured' => 'boolean',
            'sections' => 'array',
            'meta' => 'array',
            'references' => 'array',
            'tags' => 'array',
            'themes' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withoutGlobalScopes();
    }

    /** @return BelongsTo<Content, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'parent_id');
    }

    /** @return HasMany<Content, $this> */
    public function episodes(): HasMany
    {
        return $this->hasMany(Content::class, 'parent_id')->orderBy('episode_number');
    }

    /** @return BelongsToMany<Content, $this> */
    public function related(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_related', 'content_id', 'related_id')->withPivot('position')->orderByPivot('position');
    }

    /** @return MorphMany<AiAnalysis, $this> */
    public function analyses(): MorphMany
    {
        return $this->morphMany(AiAnalysis::class, 'analyzable');
    }

    /**
     * Visibilité publique (cahier § 5.1.2) : publié, ou programmé échu.
     * TOUTE lecture publique passe par ce scope.
     *
     * @param  Builder<Content>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('status', ContentStatus::Published->value)
            ->orWhere(fn (Builder $s) => $s->where('status', ContentStatus::Scheduled->value)->where('scheduled_at', '<=', now())));
    }

    /** @param Builder<Content> $query */
    public function scopeOfType(Builder $query, ContentType $type): void
    {
        $query->where('type', $type->value);
    }

    /** @param Builder<Content> $query */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByRaw('COALESCE(published_at, created_at) DESC')->orderByDesc('id');
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === ContentStatus::Published
            || ($this->status === ContentStatus::Scheduled && $this->scheduled_at !== null && $this->scheduled_at->isPast());
    }

    /** Date affichée = publication, sinon création (cahier § 5.1.4). */
    public function displayDate(): CarbonImmutable
    {
        return ($this->published_at ?? $this->created_at)->setTimezone(config('app.display_timezone'));
    }

    /** Auteur affiché (cahier § 5.2). */
    public function displayAuthor(): string
    {
        $meta = $this->meta;

        return match ($this->type) {
            ContentType::Article => $this->author?->name
                ?: (! empty($meta['coauthors']) ? implode(', ', (array) $meta['coauthors']) : 'Yewtod SS'),
            ContentType::Report, ContentType::Study => ! empty($meta['authors']) ? implode(', ', (array) $meta['authors']) : 'Yewtod SS',
            default => 'Yewtod SS',
        };
    }

    /** Extrait affiché : le champ propre à chaque type (cahier § 3). */
    public function excerpt(int $limit = 140): string
    {
        $text = match ($this->type) {
            ContentType::Study => $this->meta['research_question'] ?? $this->summary,
            default => $this->summary,
        };

        return Str::limit(trim(strip_tags((string) $text)), $limit);
    }

    /** Référence de citation : Auteur (année). Titre. Type, Yewtod SS. */
    public function citation(): string
    {
        return sprintf('%s (%s). %s. %s, Yewtod SS.', $this->displayAuthor(), $this->displayDate()->year, $this->title, $this->type->label());
    }

    public function bibtex(): string
    {
        $key = Str::slug($this->displayAuthor(), '').$this->displayDate()->year;

        return "@misc{{$key},\n  author = {{$this->displayAuthor()}},\n  title = {{$this->title}},\n  year = {{$this->displayDate()->year}},\n  howpublished = {Yewtod SS, {$this->type->label()}},\n  url = {".$this->publicUrl()."}\n}";
    }

    /** Couleur de couverture par défaut, stable selon l'identifiant (3 teintes). */
    public function coverTone(): string
    {
        return ['accent', 'accent-2', 'warning'][$this->id % 3];
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function coverSrcset(): string
    {
        return ImageProcessor::srcset($this->cover_path);
    }

    public function publicUrl(): string
    {
        return url('/works/'.$this->type->value.'/'.$this->slug);
    }
}
