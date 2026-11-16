<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Book;
use App\Models\Content;
use App\Models\Media;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Médiathèque (cahier § 10 P2, bug n° 6, `manage_media`) : envoi (20 Mo,
 * type vérifié sur le contenu, images réencodées en WebP), texte
 * alternatif, réutilisation par URL, repérage et suppression des orphelins.
 */
class MediaLibrary
{
    public function __construct(private readonly ImageProcessor $images) {}

    public function upload(User $user, UploadedFile $file): Media
    {
        $this->authorize($user);
        if ($file->getSize() > UploadService::MAX_MEDIA_KB * 1024) {
            throw ValidationException::withMessages(['upload' => 'Fichier trop volumineux (20 Mo maximum).']);
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) ?: '';
        $name = Str::limit(preg_replace('/[^\pL\pN ._-]/u', '', $file->getClientOriginalName()) ?: 'fichier', 200, '');

        if (in_array($mime, ImageProcessor::MIMES, true)) {
            $stored = $this->images->store($file, 'media');
            $data = ['path' => $stored['path'], 'kind' => 'image', 'mime' => 'image/webp', 'size' => (int) Storage::disk('public')->size($stored['path']),
                'width' => min($stored['width'], 65535), 'height' => min($stored['height'], 65535)];
        } elseif ($mime === 'application/pdf' && UploadService::isPdf($file->getRealPath())) {
            // PDF PUBLIC par nature (médiathèque) : nom aléatoire, jamais exécutable.
            $path = 'media/'.Str::random(40).'.pdf';
            Storage::disk('public')->putFileAs('media', $file, basename($path));
            $data = ['path' => $path, 'kind' => 'document', 'mime' => $mime, 'size' => (int) $file->getSize()];
        } else {
            throw ValidationException::withMessages(['upload' => 'Format refusé : images (JPEG, PNG, WebP, GIF, AVIF) ou PDF. Le SVG n\'est pas accepté.']);
        }

        $media = new Media;
        $media->forceFill($data + ['original_name' => $name, 'uploaded_by' => $user->id, 'alt' => null])->save();
        Audit::record('media.uploaded', $media, ['kind' => $media->kind]);

        return $media;
    }

    public function updateAlt(User $user, Media $media, string $alt): void
    {
        $this->authorize($user);
        $alt = trim($alt);
        if (mb_strlen($alt) > 300) {
            throw ValidationException::withMessages(['alt' => 'Texte alternatif trop long (300 caractères maximum).']);
        }
        $media->fill(['alt' => $alt === '' ? null : $alt])->save();
    }

    public function delete(User $user, Media $media): void
    {
        $this->authorize($user);
        $media->kind === 'image' ? $this->images->delete($media->path) : Storage::disk('public')->delete($media->path);
        $media->delete();
        Audit::record('media.deleted', $media, ['name' => $media->original_name]);
    }

    /**
     * Un fichier est « orphelin » s'il n'est référencé nulle part : contenus
     * (couverture, sections, méta), livres, pages, réglages. La recherche se
     * fait sur la racine du nom aléatoire, commune à toutes les variantes.
     */
    public static function isUsed(Media $media): bool
    {
        $stem = preg_replace('/-\d+\.webp$|\.pdf$/', '', basename($media->path)) ?: basename($media->path);
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $stem).'%';

        return Content::query()->where(fn ($q) => $q->where('cover_path', 'like', $like)->orWhere('sections', 'like', $like)->orWhere('meta', 'like', $like))->exists()
            || Book::query()->where(fn ($q) => $q->where('cover_path', 'like', $like)->orWhere('description', 'like', $like))->exists()
            || Page::query()->where('content', 'like', $like)->exists()
            || Setting::query()->where('value', 'like', $like)->exists();
    }

    public function deleteOrphans(User $user): int
    {
        $this->authorize($user);
        $count = 0;
        Media::query()->where('created_at', '<', now()->subDay())->chunkById(100, function ($items) use ($user, &$count) {
            foreach ($items as $media) {
                if (! self::isUsed($media)) {
                    $this->delete($user, $media);
                    $count++;
                }
            }
        });

        return $count;
    }

    private function authorize(User $user): void
    {
        if (! $user->isActive() || ! $user->hasPermission(Permission::ManageMedia)) {
            throw new AuthorizationException('La médiathèque est réservée aux membres ayant la permission « Médiathèque ».');
        }
    }
}
