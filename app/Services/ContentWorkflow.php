<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\Permission;
use App\Models\Content;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Circuit éditorial et enregistrement des publications (cahier § 5.1, § 5.2,
 * décision 10). Toutes les règles sont ici, côté serveur.
 *
 * Actions : draft (brouillon), review (soumettre en relecture),
 * publish (publier), schedule (programmer). Seuls les membres ayant
 * `publish_content` publient ou programment.
 */
class ContentWorkflow
{
    public const ACTIONS = ['draft', 'review', 'publish', 'schedule'];

    public function __construct(private readonly HtmlSanitizer $html) {}

    public function canEdit(User $user, Content $content): bool
    {
        if (! $user->isActive() || ! $user->hasPermission(Permission::ManageArticles)) {
            return false;
        }
        if ($user->hasPermission(Permission::PublishContent)) {
            return true;
        }

        // Contributeur : seulement ses propres contenus non publiés.
        return $content->author_id === $user->id && in_array($content->status, [ContentStatus::Draft, ContentStatus::Review], true);
    }

    public function canPublish(User $user): bool
    {
        return $user->isActive() && $user->hasPermission(Permission::ManageArticles) && $user->hasPermission(Permission::PublishContent);
    }

    /**
     * @param  array<string, mixed>  $data  Données du formulaire (non fiables).
     * @param  array<string, mixed>  $files  Chemins déjà stockés : cover_path, file_path/name/size.
     */
    public function save(User $user, ?Content $content, ContentType $type, array $data, string $action, array $files = []): Content
    {
        if (! in_array($action, self::ACTIONS, true)) {
            throw ValidationException::withMessages(['action' => 'Action inconnue.']);
        }
        if (! $user->isActive() || ! $user->hasPermission(Permission::ManageArticles)) {
            throw new AuthorizationException('Vous n\'avez pas la permission de gérer les publications.');
        }
        if ($content !== null && ! $this->canEdit($user, $content)) {
            throw new AuthorizationException('Vous ne pouvez pas modifier ce contenu (un contenu publié ne se modifie qu\'avec le droit de publier).');
        }
        if (in_array($action, ['publish', 'schedule'], true) && ! $this->canPublish($user)) {
            throw new AuthorizationException('Votre rôle ne permet pas de publier : soumettez le contenu en relecture.');
        }

        $data = $this->normalize($type, $data);
        $validated = Validator::make($data, ContentRules::rules($type), [], [
            'meta.doi' => 'DOI', 'parent_id' => 'série', 'episode_number' => 'numéro d\'épisode',
        ])->validate();

        $scheduledAt = null;
        if ($action === 'schedule') {
            if (empty($validated['scheduled_at'])) {
                throw ValidationException::withMessages(['scheduled_at' => 'Indiquez la date et l\'heure de publication.']);
            }
            $scheduledAt = Carbon::parse($validated['scheduled_at'], config('app.display_timezone'))->utc();
            if ($scheduledAt->isPast()) {
                throw ValidationException::withMessages(['scheduled_at' => 'La date de programmation doit être dans le futur.']);
            }
        }

        $saved = DB::transaction(function () use ($user, $content, $type, $validated, $action, $files, $scheduledAt) {
            $isNew = $content === null;
            $content ??= new Content;
            if ($isNew) {
                $content->forceFill(['type' => $type, 'author_id' => $user->id]);
            } else {
                $content = Content::query()->lockForUpdate()->findOrFail($content->id);
            }

            $content->fill([
                'title' => $validated['title'],
                'summary' => $validated['summary'] ?? null,
                'cover_alt' => $validated['cover_alt'] ?? null,
                'is_featured' => (bool) ($validated['is_featured'] ?? false),
                'language' => $validated['language'],
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
                'sections' => $validated['sections'] ?? [],
                'references' => $validated['references'] ?? [],
                'tags' => array_values($validated['tags'] ?? []),
                'themes' => array_values(array_unique($validated['themes'] ?? [])),
                'meta' => Arr::only($validated['meta'] ?? [], ContentRules::metaKeys($type)),
            ]);

            $content->forceFill(Arr::only($files, ['cover_path', 'file_path', 'file_name', 'file_size']));
            $content->forceFill(['reading_time' => self::readingTime($content)]);
            $content->forceFill(['slug' => $this->uniqueSlug($type, $validated['slug'] ?? null, $validated['title'], $content->id)]);

            if ($type === ContentType::DocumentaryEpisode) {
                $this->assignEpisode($content, (int) $validated['parent_id'], $validated['episode_number'] ?? null);
            }

            $this->applyStatus($content, $action, $scheduledAt);
            $content->save();

            $this->syncRelated($content, $validated['related'] ?? []);

            return $content;
        });

        Audit::record('content.'.($action === 'publish' ? 'published' : ($action === 'schedule' ? 'scheduled' : 'saved')), $saved, ['type' => $type->value, 'status' => $saved->status->value]);

        return $saved;
    }

    public function delete(User $user, Content $content): void
    {
        if (! $this->canEdit($user, $content)) {
            throw new AuthorizationException('Vous ne pouvez pas supprimer ce contenu.');
        }

        DB::transaction(function () use ($content) {
            $downloads = array_column((array) ($content->meta['downloads'] ?? []), 'path');
            $files = array_values(array_filter([$content->file_path, ...$downloads]));
            Storage::disk('local')->delete($files);
            app(ImageProcessor::class)->delete($content->cover_path);
            $content->delete();
        });

        Audit::record('content.deleted', $content, ['type' => $content->type->value, 'title' => $content->title]);
    }

    /** Changement de statut groupé (cahier § 10 P2 : publier, dépublier). */
    public function setStatus(User $user, Content $content, ContentStatus $status): void
    {
        if (! $this->canPublish($user)) {
            throw new AuthorizationException('Votre rôle ne permet pas de publier ou dépublier.');
        }
        $content->forceFill(['status' => $status]);
        if ($status === ContentStatus::Published && $content->published_at === null) {
            $content->forceFill(['published_at' => now()]);
        }
        $content->save();
        Audit::record('content.status_changed', $content, ['status' => $status->value]);
    }

    /**
     * Bascule des contenus programmés échus en « publié » (cahier § 5.1.2,
     * bug n° 16). Exécutée chaque minute par le scheduler, sous verrou.
     */
    public static function publishDue(): int
    {
        return DB::transaction(function () {
            $due = Content::query()->where('status', ContentStatus::Scheduled->value)
                ->where('scheduled_at', '<=', now())->lockForUpdate()->get();
            foreach ($due as $content) {
                $content->forceFill(['status' => ContentStatus::Published, 'published_at' => $content->scheduled_at])->save();
                Audit::record('content.auto_published', $content, ['type' => $content->type->value]);
            }

            return $due->count();
        });
    }

    /** Temps de lecture : mots de TOUTES les sections + résumé / 200, arrondi au supérieur, minimum 1 (bug n° 3). */
    public static function readingTime(Content $content): int
    {
        $text = (string) $content->summary;
        foreach ($content->sections as $section) {
            $text .= ' '.($section['title'] ?? '').' '.strip_tags((string) ($section['content'] ?? ''));
        }
        $words = preg_match_all('/[\p{L}\p{N}]+(?:[\'’-][\p{L}\p{N}]+)*/u', html_entity_decode($text));

        return max(1, (int) ceil($words / 200));
    }

    /**
     * Normalisation avant validation : nettoyage HTML (Purifier), sections et
     * blocs vides ignorés (cahier § 5.1.6), tags dédoublonnés.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(ContentType $type, array $data): array
    {
        $data['language'] ??= 'fr';
        $data['sections'] = collect((array) ($data['sections'] ?? []))
            ->map(fn ($s) => ['title' => trim((string) ($s['title'] ?? '')), 'content' => $this->html->clean((string) ($s['content'] ?? ''))])
            ->filter(fn ($s) => $s['title'] !== '' || trim(strip_tags($s['content'])) !== '')
            ->values()->all();
        $data['references'] = collect((array) ($data['references'] ?? []))
            ->map(fn ($r) => ['title' => trim((string) ($r['title'] ?? '')), 'url' => ($u = trim((string) ($r['url'] ?? ''))) === '' ? null : $u])
            ->filter(fn ($r) => $r['title'] !== '' || $r['url'] !== null)->values()->all();
        $tags = is_string($data['tags'] ?? null) ? explode(',', $data['tags']) : (array) ($data['tags'] ?? []);
        $data['tags'] = collect($tags)->map(fn ($t) => Str::limit(trim((string) $t), 40, ''))->filter()->unique()->values()->all();
        $data['slug'] = filled($data['slug'] ?? null) ? Str::slug((string) $data['slug']) : null;

        $meta = (array) ($data['meta'] ?? []);
        if ($type === ContentType::DataVisualization) {
            $meta['blocks'] = collect((array) ($meta['blocks'] ?? []))
                ->filter(fn ($b) => ! empty($b['title']) || ! empty($b['data']['labels'] ?? []))->values()->all();
        }
        foreach (['authors', 'coauthors'] as $list) {
            if (isset($meta[$list])) {
                $values = is_string($meta[$list]) ? explode(',', $meta[$list]) : (array) $meta[$list];
                $meta[$list] = collect($values)->map(fn ($v) => trim((string) $v))->filter()->values()->all();
            }
        }
        foreach (['links', 'chapters', 'toc', 'downloads'] as $list) {
            if (isset($meta[$list])) {
                $meta[$list] = collect((array) $meta[$list])->filter(fn ($row) => is_array($row) && array_filter($row, fn ($v) => $v !== null && $v !== '') !== [])->values()->all();
            }
        }
        $data['meta'] = array_filter($meta, fn ($v) => $v !== null && $v !== '');

        return $data;
    }

    /** Slug sans accents, unique par type grâce à un suffixe -2, -3… (cahier § 5.1.5). */
    private function uniqueSlug(ContentType $type, ?string $wanted, string $title, ?int $ignoreId): string
    {
        $base = Str::limit(Str::slug($wanted ?: $title), 280, '') ?: 'publication';
        $slug = $base;
        $i = 2;
        while (Content::query()->where('type', $type->value)->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->lockForUpdate()->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * Épisode : série obligatoire, numéro unique dans la série, proposé
     * automatiquement (dernier + 1) sous verrou (bug n° 1).
     */
    private function assignEpisode(Content $content, int $seriesId, ?int $number): void
    {
        $series = Content::query()->where('type', ContentType::DocumentarySeries->value)->lockForUpdate()->find($seriesId);
        if ($series === null) {
            throw ValidationException::withMessages(['parent_id' => 'Choisissez une série documentaire existante.']);
        }

        $taken = Content::query()->where('parent_id', $series->id)->when($content->exists, fn ($q) => $q->whereKeyNot($content->id))
            ->lockForUpdate()->pluck('episode_number')->all();

        if ($number === null) {
            $number = $content->parent_id === $series->id && $content->episode_number ? $content->episode_number : ((int) max([0, ...$taken]) + 1);
        }
        if (in_array($number, $taken, true)) {
            throw ValidationException::withMessages(['episode_number' => "L'épisode n° {$number} existe déjà dans cette série."]);
        }

        $content->forceFill(['parent_id' => $series->id, 'episode_number' => $number]);
    }

    private function applyStatus(Content $content, string $action, ?Carbon $scheduledAt): void
    {
        match ($action) {
            'draft' => $content->exists && in_array($content->status, [ContentStatus::Published, ContentStatus::Scheduled], true)
                ? null // un contenu publié modifié reste publié
                : $content->forceFill(['status' => ContentStatus::Draft]),
            'review' => $content->forceFill(['status' => ContentStatus::Review]),
            'publish' => $content->forceFill(['status' => ContentStatus::Published, 'scheduled_at' => null, 'published_at' => $content->published_at ?? now()]),
            // Pour un contenu programmé, la date de publication prend la date de programmation (§ 5.1.3).
            'schedule' => $content->forceFill(['status' => ContentStatus::Scheduled, 'scheduled_at' => $scheduledAt, 'published_at' => $scheduledAt]),
            default => throw ValidationException::withMessages(['action' => 'Action inconnue.']),
        };
    }

    /** @param list<int> $ids */
    private function syncRelated(Content $content, array $ids): void
    {
        $valid = Content::query()->whereIn('id', $ids)->whereKeyNot($content->id)->pluck('id')->all();
        $sync = [];
        foreach (array_values(array_filter($ids, fn ($id) => in_array($id, $valid, true))) as $i => $id) {
            $sync[$id] = ['position' => $i];
        }
        $content->related()->sync($sync);
    }
}
