<?php

namespace App\Http\Controllers\Site;

use App\Enums\ContentType;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\User;
use App\Services\EventCounter;
use App\Services\WorksQuery;
use App\Support\Taxonomy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Publications : catalogue, page détail, téléchargement, aperçu réel.
 *
 * Toute lecture publique passe par le scope published() : un brouillon, une
 * relecture ou un programmé non échu renvoie une vraie 404 (pas de 403 qui
 * confirmerait l'existence du contenu).
 */
class WorkController extends Controller
{
    public function index(Request $request): View
    {
        $works = new WorksQuery($request);

        return view('site.works.index', [
            'items' => $works->paginate(),
            'filters' => $works->filters,
            'total' => $works->total(),
            'typeCounts' => $works->typeCounts(),
            'themeCounts' => $works->themeCounts(),
            'series' => $works->seriesOptions(),
            'authors' => $works->authorOptions(),
            'hasFilters' => $works->hasFilters(),
            'sorts' => WorksQuery::SORTS,
            'themes' => Taxonomy::THEMES,
        ]);
    }

    public function show(string $workType, string $workSlug, EventCounter $counter): View
    {
        $content = $this->findPublished($workType, $workSlug);
        $counter->record($content, 'view');

        return $this->render($content);
    }

    /** Aperçu réel d'un brouillon (cahier § 10 P1) : URL signée 30 min + membre connecté. */
    public function preview(Request $request, Content $content): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasPermission(Permission::ManageArticles), 403, 'Vous n\'avez pas la permission de prévisualiser ce contenu.');

        return response($this->render($content, preview: true))->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** Téléchargement réel du document principal, avec comptage (cahier § 2.4). */
    public function download(string $workType, string $workSlug, EventCounter $counter): StreamedResponse
    {
        $content = $this->findPublished($workType, $workSlug);
        abort_if($content->file_path === null || ! Storage::disk('local')->exists($content->file_path), 404);
        $counter->record($content, 'download');

        $ext = pathinfo($content->file_path, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk('local')->download($content->file_path, Str::slug($content->title).'.'.$ext, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Fichiers téléchargeables d'une expérimentation (meta.downloads[n]). */
    public function file(string $workType, string $workSlug, int $index, EventCounter $counter): StreamedResponse
    {
        $content = $this->findPublished($workType, $workSlug);
        $file = ((array) ($content->meta['downloads'] ?? []))[$index] ?? null;
        $path = is_array($file) ? (string) ($file['path'] ?? '') : '';
        // Le chemin vient de la base : on refuse tout de même ce qui sortirait du dossier prévu.
        abort_unless(str_starts_with($path, 'downloads/') && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);
        $counter->record($content, 'download');

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($path, Str::slug((string) ($file['label'] ?? 'fichier')).($ext ? '.'.$ext : ''), [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function findPublished(string $type, string $slug): Content
    {
        $kind = ContentType::tryFrom($type);
        abort_if($kind === null || strlen($slug) > 320, 404);

        return Content::query()->published()->ofType($kind)->where('slug', $slug)->with(['author', 'parent'])->firstOrFail();
    }

    private function render(Content $content, bool $preview = false): View
    {
        $associated = Content::query()->published()->ofType($content->type)->whereKeyNot($content->id)
            ->with('author')->latestFirst()->limit(3)->get();

        $alsoRead = new Collection;
        if ($content->type === ContentType::Article) {
            // « À lire aussi » : sélection manuelle (par identifiant, bug n° 9), sinon d'autres articles.
            $alsoRead = $content->related()->published()->with('author')->limit(4)->get();
            if ($alsoRead->isEmpty()) {
                $alsoRead = Content::query()->published()->ofType(ContentType::Article)->whereKeyNot($content->id)
                    ->whereNotIn('id', $associated->pluck('id'))->with('author')->latestFirst()->limit(4)->get();
            }
        }

        $episodes = $content->type === ContentType::DocumentarySeries
            ? $content->episodes()->published()->get()
            : new Collection;

        $series = $content->type === ContentType::DocumentaryEpisode && $content->parent?->isPubliclyVisible() ? $content->parent : null;

        return view('site.works.show', [
            'content' => $content,
            'associated' => $associated,
            'alsoRead' => $alsoRead,
            'episodes' => $episodes,
            'series' => $series,
            'preview' => $preview,
        ]);
    }
}
