<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/** Pages éditoriales : Meet, mentions légales, confidentialité, 404 (cahier § 2.5, § 8.5, § 10). */
class PageController extends Controller
{
    public function meet(SiteSettings $site): View
    {
        return view('site.meet', ['meet' => $site->page('meet')]);
    }

    public function legal(SiteSettings $site): View
    {
        return view('site.legal', ['siteName' => $site->siteName()]);
    }

    public function privacy(SiteSettings $site): View
    {
        return view('site.privacy', ['siteName' => $site->siteName()]);
    }

    /** Vraie page 404 (bug n° 14) : passe par le groupe web (CSP, session, thème). */
    public function notFound(): Response
    {
        return response()->view('errors.404', [], 404);
    }

    /** @return Collection<int, Content> */
    public static function latestForNotFound(): Collection
    {
        return Content::query()->published()->latestFirst()->limit(4)->get(['id', 'type', 'slug', 'title', 'published_at', 'created_at']);
    }
}
