<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Setting;
use App\Models\SocialLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Lecture des réglages du site avec cache (bug n° 2 : nom, logo et favicon
 * enfin lus côté serveur). Toute écriture vide le cache.
 */
class SiteSettings
{
    private const CACHE_KEY = 'site.settings.v1';

    /** @var array<string, mixed> */
    public const DEFAULTS = [
        'site_name' => 'Yewtod SS',
        'seo_description' => 'Yewtod SS, média de recherche en sciences sociales : analyses, terrain, lectures.',
        'logo_path' => null,
        'favicon_path' => null,
        'reflection' => ['text' => '', 'author' => '', 'date' => null],
    ];

    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->memo ??= Cache::rememberForever(self::CACHE_KEY, function () {
            if (! Schema::hasTable('settings')) {
                return self::DEFAULTS;
            }

            return array_merge(self::DEFAULTS, Setting::query()->pluck('value', 'key')->all());
        });
    }

    public function set(string $key, mixed $value): void
    {
        $setting = Setting::query()->where('key', $key)->first() ?? new Setting(['key' => $key]);
        $setting->value = $value;
        $setting->save();
        $this->flush();
    }

    public function flush(): void
    {
        $this->memo = null;
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.'.social');
        foreach (Page::KEYS as $key) {
            Cache::forget(self::CACHE_KEY.'.page.'.$key);
        }
    }

    public function siteName(): string
    {
        return (string) $this->get('site_name');
    }

    public function logoUrl(): ?string
    {
        $path = $this->get('logo_path');

        return is_string($path) ? Storage::disk('public')->url($path) : null;
    }

    public function faviconUrl(): ?string
    {
        $path = $this->get('favicon_path');

        return is_string($path) ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Le cache ne contient que des tableaux simples : depuis Laravel 13, le
     * cache refuse de désérialiser des objets arbitraires (protection contre
     * les attaques par désérialisation).
     *
     * @return Collection<int, SocialLink>
     */
    public function socialLinks(): Collection
    {
        /** @var list<array{id: int, platform: string, url: string, position: int}> $rows */
        $rows = Cache::rememberForever(self::CACHE_KEY.'.social', fn () => Schema::hasTable('social_links')
            ? SocialLink::query()->orderBy('position')->orderBy('id')->get(['id', 'platform', 'url', 'position'])->toArray()
            : []);

        return collect($rows)->map(fn (array $row) => (new SocialLink)->forceFill($row));
    }

    /**
     * Contenu d'une page éditable fusionné avec ses valeurs par défaut.
     *
     * @return array<string, mixed>
     */
    public function page(string $key): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY.'.page.'.$key, fn () => Schema::hasTable('pages')
            ? (Page::query()->where('key', $key)->value('content') ?? [])
            : []);
        $stored = is_string($stored) ? (json_decode($stored, true) ?: []) : (array) $stored;

        return array_replace(PageDefaults::for($key), array_filter($stored, fn ($v) => $v !== null && $v !== '' && $v !== []));
    }
}
