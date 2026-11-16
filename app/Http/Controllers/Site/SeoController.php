<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\SiteSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * sitemap.xml et flux RSS (cahier § 10 P1, bug n° 15). Mis en cache 1 h :
 * deux URL publiques qu'un robot peut appeler en boucle (anti-DoS).
 * Seuls les contenus visibles (scope published) y figurent.
 */
class SeoController extends Controller
{
    public const SITEMAP_LIMIT = 5000;

    public function sitemap(): Response
    {
        $xml = Cache::remember('seo.sitemap', 3600, function () {
            $urls = [];
            foreach (['/', '/works', '/meet', '/books', '/collaborations', '/mentions-legales', '/confidentialite'] as $path) {
                $urls[] = ['loc' => url($path), 'lastmod' => null];
            }
            Content::query()->published()->latestFirst()->limit(self::SITEMAP_LIMIT)
                ->get(['id', 'type', 'slug', 'updated_at'])
                ->each(function (Content $c) use (&$urls) {
                    $urls[] = ['loc' => $c->publicUrl(), 'lastmod' => $c->updated_at->toAtomString()];
                });
            foreach (self::extraUrls() as $extra) {
                $urls[] = $extra;
            }

            return view('site.seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function feed(SiteSettings $site): Response
    {
        $xml = Cache::remember('seo.feed', 3600, fn () => view('site.seo.feed', [
            'siteName' => $site->siteName(),
            'description' => (string) $site->get('seo_description'),
            'items' => Content::query()->published()->with('author')->latestFirst()->limit(30)->get(),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nDisallow: /dashboard\nDisallow: /apercu\n\nSitemap: ".route('sitemap')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * URL ajoutées par d'autres modules (livres au lot 06), via le conteneur.
     *
     * @return list<array{loc: string, lastmod: string|null}>
     */
    private static function extraUrls(): array
    {
        $urls = [];
        foreach (app()->tagged('sitemap.sources') as $source) {
            /** @var callable(): list<array{loc: string, lastmod: string|null}> $source */
            $urls = array_merge($urls, $source());
        }

        return $urls;
    }

    public static function flush(): void
    {
        Cache::forget('seo.sitemap');
        Cache::forget('seo.feed');
    }
}
