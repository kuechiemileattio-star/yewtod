<?php

namespace App\Services;

use App\Enums\ContentType;
use App\Models\Content;
use App\Models\User;
use App\Support\Taxonomy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Catalogue public filtrable (cahier § 2.3, § 8.6).
 *
 * Sécurité : chaque paramètre de l'URL est confronté à une LISTE BLANCHE
 * (types, axes, tris) ou converti en type sûr (entier, date) ; la recherche
 * passe uniquement par des requêtes liées (bindings) avec les jokers LIKE
 * échappés. Toute valeur inconnue est ignorée, jamais injectée.
 */
class WorksQuery
{
    public const PER_PAGE = 12;

    public const SORTS = ['recent' => 'Plus récent', 'ancien' => 'Plus ancien', 'titre' => 'Titre A–Z'];

    public const MAX_QUERY = 100;

    /** @var array{q: string, types: list<string>, axe: string|null, serie: int|null, auteur: int|null, du: string|null, au: string|null, tri: string} */
    public array $filters;

    public function __construct(Request $request)
    {
        $types = array_merge((array) $request->query('types', []), [$request->query('categorie')]);
        $types = array_values(array_unique(array_filter($types, fn ($t) => is_string($t) && ContentType::tryFrom($t) !== null)));

        $axe = $request->query('axe');
        $tri = $request->query('tri');

        $this->filters = [
            'q' => mb_substr(trim((string) (is_string($request->query('q')) ? $request->query('q') : '')), 0, self::MAX_QUERY),
            'types' => $types,
            'axe' => is_string($axe) && array_key_exists($axe, Taxonomy::THEMES) ? $axe : null,
            'serie' => self::positiveInt($request->query('serie')),
            'auteur' => self::positiveInt($request->query('auteur')),
            'du' => self::date($request->query('du')),
            'au' => self::date($request->query('au')),
            'tri' => is_string($tri) && array_key_exists($tri, self::SORTS) ? $tri : 'recent',
        ];
    }

    /** @return LengthAwarePaginator<int, Content> */
    public function paginate(): LengthAwarePaginator
    {
        $query = $this->apply(Content::query()->published()->with(['author', 'parent']));

        $query = match ($this->filters['tri']) {
            'ancien' => $query->orderByRaw('COALESCE(published_at, created_at) ASC')->orderBy('id'),
            'titre' => $query->orderBy('title')->orderBy('id'),
            default => $query->latestFirst(),
        };

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * @param  Builder<Content>  $query
     * @return Builder<Content>
     */
    public function apply(Builder $query): Builder
    {
        $f = $this->filters;

        if ($f['q'] !== '') {
            $like = '%'.self::escapeLike($f['q']).'%';
            $tag = '%'.self::escapeLike(mb_strtolower($f['q'])).'%';
            $query->where(fn (Builder $q) => $q->where('title', 'like', $like)
                ->orWhere('summary', 'like', $like)
                // Tags en JSON : JSON_SEARCH compare les valeurs décodées (paramètre lié).
                ->orWhereRaw("JSON_SEARCH(LOWER(tags), 'one', ?) IS NOT NULL", [$tag]));
        }
        if ($f['types'] !== []) {
            $query->whereIn('type', $f['types']);
        }
        if ($f['axe'] !== null) {
            $query->whereJsonContains('themes', $f['axe']);
        }
        if ($f['serie'] !== null) {
            $query->where(fn (Builder $q) => $q->where('parent_id', $f['serie'])->orWhere('id', $f['serie']));
        }
        if ($f['auteur'] !== null) {
            $query->where('author_id', $f['auteur']);
        }
        if ($f['du'] !== null) {
            $query->whereRaw('COALESCE(published_at, created_at) >= ?', [$f['du'].' 00:00:00']);
        }
        if ($f['au'] !== null) {
            $query->whereRaw('COALESCE(published_at, created_at) <= ?', [$f['au'].' 23:59:59']);
        }

        return $query;
    }

    /** Nombre total de publications visibles (« N résultats sur M »). */
    public function total(): int
    {
        return Content::query()->published()->count();
    }

    /** @return array<string, int> Compteur par type (pastilles et cases). */
    public function typeCounts(): array
    {
        $rows = Content::query()->published()->select('type', DB::raw('COUNT(*) AS aggregate'))->groupBy('type')->pluck('aggregate', 'type')->all();
        $counts = [];
        foreach (ContentType::cases() as $type) {
            $counts[$type->value] = (int) ($rows[$type->value] ?? 0);
        }

        return $counts;
    }

    /** @return array<string, int> Compteur par axe thématique. */
    public function themeCounts(): array
    {
        $counts = [];
        foreach (array_keys(Taxonomy::THEMES) as $key) {
            $counts[$key] = Content::query()->published()->whereJsonContains('themes', $key)->count();
        }

        return $counts;
    }

    /** @return array<int, string> Séries documentaires publiées (id => titre). */
    public function seriesOptions(): array
    {
        return Content::query()->published()->ofType(ContentType::DocumentarySeries)->orderBy('title')->limit(200)->pluck('title', 'id')->all();
    }

    /** @return array<int, string> Auteurs (membres) ayant au moins une publication visible. */
    public function authorOptions(): array
    {
        $ids = Content::query()->published()->whereNotNull('author_id')->distinct()->limit(200)->pluck('author_id');

        return User::query()->withoutGlobalScopes()->whereIn('id', $ids)->orderBy('name')->pluck('name', 'id')->all();
    }

    public function hasFilters(): bool
    {
        $f = $this->filters;

        return $f['q'] !== '' || $f['types'] !== [] || $f['axe'] || $f['serie'] || $f['auteur'] || $f['du'] || $f['au'];
    }

    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private static function positiveInt(mixed $value): ?int
    {
        return is_string($value) && ctype_digit($value) && (int) $value > 0 && strlen($value) < 10 ? (int) $value : null;
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }
}
