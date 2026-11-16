<?php

namespace App\Models;

use App\Services\ImageProcessor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Fichier de la médiathèque (cahier § 10 P2). Seul le texte alternatif est
 * modifiable ; chemin, type et taille sont écrits par MediaLibrary.
 *
 * @property int $id
 * @property string $path
 * @property string $kind
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $alt
 * @property int|null $uploaded_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read User|null $uploader
 */
class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['alt'];

    protected $attributes = ['alt' => null, 'width' => null, 'height' => null, 'uploaded_by' => null];

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function srcset(): string
    {
        return $this->kind === 'image' ? ImageProcessor::srcset($this->path) : '';
    }
}
