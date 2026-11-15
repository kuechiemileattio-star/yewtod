<?php

namespace App\Enums;

/**
 * Registre des 8 types de publications (cahier § 3, § 12.2) : libellés,
 * segment d'URL (la valeur), gabarit de page, champ d'extrait.
 */
enum ContentType: string
{
    case Article = 'articles';
    case Report = 'rapports';
    case Study = 'etudes';
    case ResearchNote = 'notes-de-recherche';
    case DocumentarySeries = 'series-documentaires';
    case DocumentaryEpisode = 'episodes-documentaires';
    case Experiment = 'experimentations';
    case DataVisualization = 'visualisations-de-donnees';

    public function label(): string
    {
        return match ($this) {
            self::Article => 'Article',
            self::Report => 'Rapport',
            self::Study => 'Étude',
            self::ResearchNote => 'Note de recherche',
            self::DocumentarySeries => 'Série documentaire',
            self::DocumentaryEpisode => 'Épisode documentaire',
            self::Experiment => 'Expérimentation',
            self::DataVisualization => 'Visualisation de données',
        };
    }

    public function plural(): string
    {
        return match ($this) {
            self::Article => 'Articles',
            self::Report => 'Rapports',
            self::Study => 'Études',
            self::ResearchNote => 'Notes de recherche',
            self::DocumentarySeries => 'Séries documentaires',
            self::DocumentaryEpisode => 'Épisodes documentaires',
            self::Experiment => 'Expérimentations',
            self::DataVisualization => 'Visualisations de données',
        };
    }

    /** Gabarit de la page détail (cahier § 2.4). */
    public function template(): string
    {
        return match ($this) {
            self::Article => 'article',
            self::DocumentarySeries, self::DocumentaryEpisode => 'documentary',
            default => 'journal',
        };
    }

    /** Libellé du champ affiché comme extrait (cahier § 3). */
    public function excerptLabel(): string
    {
        return match ($this) {
            self::Article => 'Résumé',
            self::Report => 'Résumé exécutif',
            self::Study => 'Question de recherche',
            self::ResearchNote => 'Idée principale',
            self::DocumentarySeries, self::DataVisualization => 'Description',
            self::DocumentaryEpisode => 'Résumé',
            self::Experiment => 'Objectif',
        };
    }

    /**
     * Types gérés dans « Autres publications » du dashboard (cahier § 4).
     *
     * @return list<self>
     */
    public static function others(): array
    {
        return [self::Study, self::ResearchNote, self::DocumentarySeries, self::DocumentaryEpisode, self::Experiment];
    }

    /** Teinte d'accent de la couverture par défaut. */
    public function hue(): string
    {
        return match ($this) {
            self::Article, self::ResearchNote => 'accent',
            self::Report, self::Study, self::Experiment => 'accent-2',
            default => 'warning',
        };
    }
}
