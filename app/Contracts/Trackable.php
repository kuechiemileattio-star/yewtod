<?php

namespace App\Contracts;

/**
 * Élément dont on compte les vues et téléchargements publics (cahier § 5.3).
 * Le modèle porte les colonnes `views_count` et `downloads_count`.
 */
interface Trackable
{
    /** Visible du public (publié, ou programmé échu) : seule condition pour compter. */
    public function isPubliclyVisible(): bool;
}
