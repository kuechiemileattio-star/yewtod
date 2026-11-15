<?php

namespace App\Services;

use App\Enums\ContentType;
use App\Rules\HttpUrl;
use App\Support\Taxonomy;
use Illuminate\Validation\Rule;

/**
 * Règles de validation par type (cahier § 3.2 : « colonne JSON meta validée
 * par une règle par type »). Toute clé de `meta` absente de ces règles est
 * supprimée avant l'enregistrement (ContentWorkflow::filterMeta).
 */
class ContentRules
{
    /** @return array<string, mixed> */
    public static function rules(ContentType $type): array
    {
        $base = [
            'title' => ['required', 'string', 'min:2', 'max:300'],
            'slug' => ['nullable', 'string', 'max:300', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'summary' => ['nullable', 'string', 'max:'.($type === ContentType::Article ? 2900 : 5000)],
            'cover_alt' => ['nullable', 'string', 'max:300'],
            'is_featured' => ['boolean'],
            'language' => ['required', Rule::in(array_keys(Taxonomy::LANGUAGES))],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'sections' => ['array', 'max:100'],
            'sections.*.title' => ['nullable', 'string', 'max:300'],
            'sections.*.content' => ['nullable', 'string', 'max:100000'],
            'references' => ['array', 'max:200'],
            'references.*.title' => ['nullable', 'string', 'max:500'],
            'references.*.url' => ['nullable', new HttpUrl],
            'tags' => ['array', 'max:20'],
            'tags.*' => ['string', 'max:40'],
            'themes' => ['array', 'max:8'],
            'themes.*' => [Rule::in(array_keys(Taxonomy::THEMES))],
            'related' => ['array', 'max:12'],
            'related.*' => ['integer', 'distinct'],
            'scheduled_at' => ['nullable', 'date'],
            'meta' => ['array'],
        ];

        return array_merge($base, self::metaRules($type));
    }

    /** @return array<string, mixed> */
    public static function metaRules(ContentType $type): array
    {
        $authors = ['meta.authors' => ['array', 'max:50'], 'meta.authors.*' => ['string', 'max:120']];
        $toc = [
            'meta.pages' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'meta.toc' => ['array', 'max:200'],
            'meta.toc.*.title' => ['required', 'string', 'max:300'],
            'meta.toc.*.page' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'meta.toc.*.summary' => ['nullable', 'string', 'max:2000'],
        ];

        return match ($type) {
            ContentType::Article => [
                'meta.subtitle' => ['nullable', 'string', 'max:300'],
                'meta.content_kind' => ['nullable', Rule::in(array_keys(Taxonomy::ARTICLE_KINDS))],
                'meta.coauthors' => ['array', 'max:10'],
                'meta.coauthors.*' => ['string', 'max:120'],
            ] + $toc,
            ContentType::Report => $authors + $toc + [
                'meta.version' => ['nullable', 'string', 'max:20'],
                'meta.doi' => ['nullable', 'string', 'max:200', 'regex:/^10\.\d{4,9}\/\S+$/'],
                'meta.volume' => ['nullable', 'string', 'max:20'],
                'meta.issue' => ['nullable', 'string', 'max:20'],
                'meta.affiliation' => ['nullable', 'string', 'max:200'],
            ],
            ContentType::Study => $authors + $toc + [
                'meta.research_question' => ['nullable', 'string', 'max:1000'],
                'meta.doi' => ['nullable', 'string', 'max:200', 'regex:/^10\.\d{4,9}\/\S+$/'],
            ],
            ContentType::ResearchNote => [
                'meta.progress' => ['nullable', Rule::in(array_keys(Taxonomy::NOTE_PROGRESS))],
                'meta.links' => ['array', 'max:20'],
                'meta.links.*.label' => ['required', 'string', 'max:200'],
                'meta.links.*.url' => ['required', new HttpUrl],
            ],
            ContentType::DocumentarySeries => [
                'meta.trailer_url' => ['nullable', new HttpUrl],
                'meta.theme_label' => ['nullable', 'string', 'max:120'],
            ],
            ContentType::DocumentaryEpisode => [
                'parent_id' => ['required', 'integer'],
                'episode_number' => ['nullable', 'integer', 'min:1', 'max:9999'],
                'meta.video_url' => ['nullable', new HttpUrl],
                'meta.chapters' => ['array', 'max:100'],
                'meta.chapters.*.time' => ['required', 'string', 'regex:/^(\d{1,2}:)?[0-5]?\d:[0-5]\d$/'],
                'meta.chapters.*.title' => ['required', 'string', 'max:200'],
            ],
            ContentType::Experiment => [
                'meta.source_code_url' => ['nullable', new HttpUrl],
                'meta.downloads' => ['array', 'max:10'],
                'meta.downloads.*.label' => ['required', 'string', 'max:200'],
                'meta.downloads.*.path' => ['required', 'string', 'max:255'],
                'meta.downloads.*.size' => ['nullable', 'integer'],
            ],
            ContentType::DataVisualization => [
                'meta.data_source' => ['nullable', 'string', 'max:300'],
                'meta.source_code_url' => ['nullable', new HttpUrl],
                'meta.legend' => ['nullable', 'string', 'max:2000'],
                'meta.analysis' => ['nullable', 'string', 'max:10000'],
                'meta.blocks' => ['array', 'max:20'],
                'meta.blocks.*.title' => ['nullable', 'string', 'max:300'],
                'meta.blocks.*.description' => ['nullable', 'string', 'max:2000'],
                'meta.blocks.*.chart_type' => ['required', Rule::in(array_keys(Taxonomy::CHART_TYPES))],
                'meta.blocks.*.data' => ['nullable', 'array'],
                'meta.blocks.*.data.labels' => ['array', 'max:500'],
                'meta.blocks.*.data.labels.*' => ['nullable', 'string', 'max:120'],
                'meta.blocks.*.data.series' => ['array', 'max:20'],
                'meta.blocks.*.data.series.*.name' => ['nullable', 'string', 'max:120'],
                'meta.blocks.*.data.series.*.values' => ['array', 'max:500'],
                'meta.blocks.*.data.series.*.values.*' => ['nullable', 'numeric'],
            ],
        };
    }

    /**
     * Clés autorisées dans `meta` pour un type (les autres sont jetées).
     *
     * @return list<string>
     */
    public static function metaKeys(ContentType $type): array
    {
        return collect(array_keys(self::metaRules($type)))
            ->filter(fn (string $k) => str_starts_with($k, 'meta.'))
            ->map(fn (string $k) => explode('.', $k)[1])
            ->unique()->values()->all();
    }
}
