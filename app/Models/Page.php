<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Page éditable (Meet, Accueil, pied de page). Le contenu est un objet JSON
 * validé par PageEditor ; les valeurs par défaut s'affichent tant
 * qu'aucune version n'est enregistrée (cahier § 5.9).
 *
 * @property int $id
 * @property string $key
 * @property array<string, mixed> $content
 * @property int|null $updated_by
 */
class Page extends Model
{
    public const KEYS = ['meet', 'home', 'footer'];

    protected $fillable = ['content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }
}
