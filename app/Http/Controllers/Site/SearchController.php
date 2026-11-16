<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\WorksQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recherche globale (Ctrl K, cahier § 10 P2) : réponse JSON courte,
 * uniquement des contenus visibles, requête liée, 8 résultats maximum,
 * limitée par IP (route throttle).
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = mb_substr(trim((string) (is_string($request->query('q')) ? $request->query('q') : '')), 0, WorksQuery::MAX_QUERY);
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }
        $like = '%'.WorksQuery::escapeLike($q).'%';

        $results = Content::query()->published()->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('summary', 'like', $like))
            ->latestFirst()->limit(8)->get(['id', 'type', 'slug', 'title', 'published_at', 'created_at'])
            ->map(fn (Content $c) => ['title' => $c->title, 'kind' => $c->type->label(), 'url' => $c->publicUrl()]);

        foreach (app()->tagged('search.sources') as $source) {
            /** @var callable(string): iterable<array{title: string, kind: string, url: string}> $source */
            $results = $results->concat($source($like));
        }

        return response()->json(['results' => $results->take(10)->values()]);
    }
}
