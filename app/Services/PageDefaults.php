<?php

namespace App\Services;

/**
 * Valeurs par défaut des pages éditables (cahier § 2.2, 2.5, 5.9, bug n° 7).
 * Elles s'affichent tant que rien n'a été enregistré depuis le dashboard.
 */
class PageDefaults
{
    /** @return array<string, mixed> */
    public static function for(string $key): array
    {
        return match ($key) {
            'home' => [
                'hero_title' => 'Comprendre les sociétés, au plus près du terrain.',
                'hero_text' => 'Yewtod SS publie des analyses, des rapports, des études et des documentaires en sciences sociales, en accès libre.',
                'pillars' => [
                    ['title' => 'Des analyses', 'text' => 'Des articles et des notes pour éclairer l\'actualité avec les outils des sciences sociales.'],
                    ['title' => 'Du terrain', 'text' => 'Des rapports, des études et des séries documentaires nourris d\'enquêtes et d\'entretiens.'],
                    ['title' => 'Des lectures', 'text' => 'Une bibliothèque commentée d\'ouvrages pour aller plus loin.'],
                ],
            ],
            'meet' => [
                'hero_title' => 'Meet Yewtod',
                'hero_text' => 'Un média indépendant de recherche en sciences sociales, né de la conviction que la connaissance doit circuler hors des cercles spécialisés.',
                'how_i_work' => [
                    ['title' => 'Enquêter', 'text' => 'Partir des faits, des données et des personnes concernées.'],
                    ['title' => 'Analyser', 'text' => 'Mobiliser les méthodes et la littérature scientifique, sans jargon inutile.'],
                    ['title' => 'Transmettre', 'text' => 'Publier en accès libre, sous des formats variés : écrits, données, vidéos.'],
                ],
                'faq' => [
                    ['q' => 'Qu\'est-ce que Yewtod SS ?', 'a' => 'Un média de recherche en sciences sociales qui publie des travaux en accès libre.'],
                    ['q' => 'Qui écrit sur le site ?', 'a' => 'Le fondateur et des contributrices et contributeurs invités, chercheurs ou praticiens.'],
                    ['q' => 'Les contenus sont-ils gratuits ?', 'a' => 'Oui, toutes les publications sont librement consultables et téléchargeables.'],
                    ['q' => 'Puis-je proposer un travail ?', 'a' => 'Oui, via la page Collaborations : proposition de recherche, d\'article, de livre ou de partenariat.'],
                    ['q' => 'Comment citer une publication ?', 'a' => 'Chaque page propose une référence prête à copier, au format APA ou BibTeX.'],
                ],
                'founder_name' => 'Le fondateur',
                'portrait_path' => null,
                'bio_1' => 'Chercheur en sciences sociales, il a fondé Yewtod SS pour rendre accessibles des travaux exigeants à un large public.',
                'bio_2' => 'Ses recherches portent sur les transformations sociales, économiques et politiques contemporaines.',
                'expertise' => ['Sociologie', 'Économie politique', 'Méthodes d\'enquête'],
            ],
            'footer' => [
                'cta_title' => 'Démarrer une conversation',
                'cta_text' => 'Une idée de recherche, un partenariat, une invitation ? Écrivons ensemble la suite.',
                'about' => 'Média de recherche en sciences sociales.',
            ],
            default => [],
        };
    }
}
