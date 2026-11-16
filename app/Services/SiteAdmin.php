<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Page;
use App\Models\SocialLink;
use App\Models\User;
use App\Rules\HttpUrl;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Paramètres du site et pages éditables (cahier § 4.8, § 4.9, § 5.9,
 * bugs n° 2 et n° 7). Toute écriture vide le cache de SiteSettings : les
 * pages publiques reflètent immédiatement le changement.
 */
class SiteAdmin
{
    public const MAX_SOCIAL = 12;

    public function __construct(private readonly SiteSettings $settings, private readonly ImageProcessor $images) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveSettings(User $user, array $data, ?UploadedFile $logo = null, ?UploadedFile $favicon = null, bool $removeLogo = false, bool $removeFavicon = false): void
    {
        $this->authorize($user, Permission::ManageSettings);
        $valid = Validator::make($data, [
            'site_name' => ['required', 'string', 'min:2', 'max:80'],
            'seo_description' => ['required', 'string', 'max:300'],
            'reflection_text' => ['nullable', 'string', 'max:600'],
            'reflection_author' => ['nullable', 'string', 'max:120'],
        ], [], ['site_name' => 'nom du site', 'seo_description' => 'description SEO', 'reflection_text' => 'réflexion', 'reflection_author' => 'auteur'])->validate();

        DB::transaction(function () use ($valid, $logo, $favicon, $removeLogo, $removeFavicon) {
            $this->settings->set('site_name', trim($valid['site_name']));
            $this->settings->set('seo_description', trim($valid['seo_description']));

            $previous = (array) $this->settings->get('reflection');
            $text = trim((string) ($valid['reflection_text'] ?? ''));
            $author = trim((string) ($valid['reflection_author'] ?? ''));
            // Date fixée automatiquement à l'enregistrement, seulement si le texte change (cahier § 5.9).
            $changed = $text !== ($previous['text'] ?? '') || $author !== ($previous['author'] ?? '');
            $this->settings->set('reflection', ['text' => $text, 'author' => $author, 'date' => $changed ? now()->toDateString() : ($previous['date'] ?? null)]);

            foreach (['logo_path' => [$logo, $removeLogo, [480]], 'favicon_path' => [$favicon, $removeFavicon, [192]]] as $key => [$file, $remove, $widths]) {
                $old = $this->settings->get($key);
                if ($file instanceof UploadedFile) {
                    $this->settings->set($key, $this->images->store($file, 'site', $widths)['path']);
                } elseif ($remove) {
                    $this->settings->set($key, null);
                } else {
                    continue;
                }
                if (is_string($old)) {
                    $this->images->delete($old);
                }
            }
        });

        Audit::record('settings.updated', null, ['keys' => ['site_name', 'seo_description', 'reflection']]);
    }

    /**
     * Réseaux sociaux : liste ordonnée remplacée en une transaction.
     *
     * @param  list<array{platform?: mixed, url?: mixed}>  $links
     */
    public function saveSocialLinks(User $user, array $links): void
    {
        $this->authorize($user, Permission::ManageSettings);
        $links = array_values(array_filter($links, fn ($l) => trim((string) ($l['platform'] ?? '')) !== '' || trim((string) ($l['url'] ?? '')) !== ''));
        Validator::make(['links' => $links], [
            'links' => ['array', 'max:'.self::MAX_SOCIAL],
            'links.*.platform' => ['required', 'string', 'max:40'],
            'links.*.url' => ['required', 'max:500', new HttpUrl(httpsOnly: true)],
        ], [], ['links.*.platform' => 'plateforme', 'links.*.url' => 'lien'])->validate();

        DB::transaction(function () use ($links) {
            SocialLink::query()->delete();
            foreach ($links as $i => $l) {
                (new SocialLink(['platform' => trim((string) $l['platform']), 'url' => trim((string) $l['url'])]))->forceFill(['position' => $i])->save();
            }
        });
        $this->settings->flush();
        Audit::record('settings.social_links_updated', null, ['count' => count($links)]);
    }

    /**
     * Pages éditables (Meet, Accueil, pied de page). Le schéma de chaque page
     * est celui de ses valeurs par défaut : aucune clé inconnue n'est stockée.
     *
     * @param  array<string, mixed>  $content
     */
    public function savePage(User $user, string $key, array $content, ?UploadedFile $portrait = null, bool $removePortrait = false): void
    {
        $this->authorize($user, Permission::ManagePages);
        if (! in_array($key, Page::KEYS, true)) {
            throw new \InvalidArgumentException('Page inconnue.');
        }
        $rules = self::pageRules($key);
        $content = $this->normalizePage($key, $content);
        $valid = Validator::make($content, $rules)->validate();
        $valid = array_intersect_key($valid, PageDefaults::for($key));

        DB::transaction(function () use ($user, $key, $valid, $portrait, $removePortrait) {
            $page = Page::query()->where('key', $key)->lockForUpdate()->first() ?? (new Page)->forceFill(['key' => $key]);
            $old = (array) ($page->content ?? []);
            if ($key === 'meet') {
                $valid['portrait_path'] = $old['portrait_path'] ?? null;
                if ($portrait instanceof UploadedFile || $removePortrait) {
                    if (is_string($valid['portrait_path'])) {
                        $this->images->delete($valid['portrait_path']);
                    }
                    $valid['portrait_path'] = $portrait instanceof UploadedFile ? $this->images->store($portrait, 'pages', [480, 960])['path'] : null;
                }
            }
            $page->content = $valid;
            $page->forceFill(['updated_by' => $user->id])->save();
        });
        $this->settings->flush();
        Audit::record('page.updated', null, ['page' => $key]);
    }

    /** @return array<string, mixed> */
    public static function pageRules(string $key): array
    {
        $card = fn (string $list, int $max) => [
            $list => ['array', 'max:'.$max],
            $list.'.*.title' => ['required', 'string', 'max:120'],
            $list.'.*.text' => ['required', 'string', 'max:600'],
        ];

        return match ($key) {
            'home' => ['hero_title' => ['required', 'string', 'max:160'], 'hero_text' => ['required', 'string', 'max:500']] + $card('pillars', 3),
            'meet' => [
                'hero_title' => ['required', 'string', 'max:160'], 'hero_text' => ['required', 'string', 'max:800'],
                'founder_name' => ['required', 'string', 'max:120'], 'bio_1' => ['required', 'string', 'max:2000'], 'bio_2' => ['nullable', 'string', 'max:2000'],
                'expertise' => ['array', 'max:20'], 'expertise.*' => ['string', 'max:80'],
                'faq' => ['array', 'max:20'], 'faq.*.q' => ['required', 'string', 'max:200'], 'faq.*.a' => ['required', 'string', 'max:1500'],
            ] + $card('how_i_work', 3),
            'footer' => ['cta_title' => ['required', 'string', 'max:120'], 'cta_text' => ['required', 'string', 'max:300'], 'about' => ['required', 'string', 'max:300']],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function normalizePage(string $key, array $content): array
    {
        foreach ($content as $k => $v) {
            if (is_string($v)) {
                $content[$k] = trim($v);
            }
        }
        if ($key === 'meet') {
            $exp = $content['expertise'] ?? [];
            $content['expertise'] = collect(is_string($exp) ? preg_split('/\R/u', $exp) : (array) $exp)->map(fn ($e) => trim((string) $e))->filter()->values()->all();
            $content['faq'] = collect((array) ($content['faq'] ?? []))->filter(fn ($f) => trim((string) ($f['q'] ?? '')) !== '')->values()->all();
        }

        return $content;
    }

    private function authorize(User $user, Permission $permission): void
    {
        if (! $user->isActive() || ! $user->hasPermission($permission)) {
            throw new AuthorizationException('Vous n\'avez pas la permission « '.$permission->label().' ».');
        }
    }
}
