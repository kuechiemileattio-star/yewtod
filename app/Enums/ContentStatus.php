<?php

namespace App\Enums;

/** Circuit éditorial (cahier § 3.2, décision 10). */
enum ContentStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Review => 'En relecture',
            self::Scheduled => 'Programmé',
            self::Published => 'Publié',
            self::Archived => 'Archivé',
        };
    }
}
