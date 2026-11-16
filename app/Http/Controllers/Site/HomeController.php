<?php

namespace App\Http\Controllers\Site;

use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\SiteSettings;
use Illuminate\Contracts\View\View;

/** Accueil (cahier § 2.2, § 5.2, bug n° 4). */
class HomeController extends Controller
{
    public function __invoke(SiteSettings $site): View
    {
        $latest = Content::query()->published()->with('author')->latestFirst()->limit(12)->get();
        $now = $latest->first();

        // « Travaux mis en avant » : le drapeau is_featured pilote enfin le bloc
        // (bug n° 4) ; s'il y en a moins de 4, on complète par les plus récentes.
        $featured = Content::query()->published()->with('author')->where('is_featured', true)
            ->when($now, fn ($q) => $q->whereKeyNot($now->id))->latestFirst()->limit(4)->get();
        if ($featured->count() < 4) {
            $exclude = $featured->pluck('id')->push($now?->id)->filter()->all();
            $featured = $featured->concat($latest->whereNotIn('id', $exclude)->take(4 - $featured->count()))->values();
        }

        $reflection = (array) $site->get('reflection');

        return view('site.home', [
            'home' => $site->page('home'),
            'now' => $now,
            'lastReport' => Content::query()->published()->ofType(ContentType::Report)->with('author')->latestFirst()->first(),
            'lastSeries' => Content::query()->published()->ofType(ContentType::DocumentarySeries)->latestFirst()->first(),
            'featured' => $featured,
            'recent' => $latest->take(6),
            'reflection' => trim((string) ($reflection['text'] ?? '')) !== '' ? $reflection : null,
        ]);
    }
}
