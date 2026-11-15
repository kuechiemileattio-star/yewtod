<?php

namespace App\Support;

/**
 * Listes fermées utilisées par la validation et les filtres (listes blanches).
 * Les 8 axes thématiques sont provisoires (le cahier n'en donne pas les noms) :
 * voir docs/DECISIONS.md, D23.
 */
class Taxonomy
{
    public const THEMES = [
        'societe' => 'Société et inégalités',
        'economie' => 'Économie et travail',
        'politique' => 'Politique et institutions',
        'education' => 'Éducation et savoirs',
        'sante' => 'Santé et corps',
        'environnement' => 'Environnement et territoires',
        'culture' => 'Cultures et religions',
        'numerique' => 'Numérique et médias',
    ];

    public const ARTICLE_KINDS = ['actualite' => 'Actualité', 'dossier' => 'Dossier', 'definition' => 'Définition', 'breve' => 'Brève'];

    public const NOTE_PROGRESS = ['idee' => 'Idée', 'en_cours' => 'En cours', 'abouti' => 'Abouti'];

    public const CHART_TYPES = ['bar' => 'Barres', 'line' => 'Lignes', 'area' => 'Aires', 'stacked' => 'Barres empilées', 'scatter' => 'Nuage de points', 'pie' => 'Camembert'];

    public const LANGUAGES = ['fr' => 'Français', 'en' => 'Anglais'];
}
