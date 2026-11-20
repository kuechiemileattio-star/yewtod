<?php

namespace App\Enums;

/**
 * Les permissions du cahier (§ 6) + `publish_content` (décision 10 : circuit
 * éditorial, un Contributeur soumet, un Éditeur publie) + `access_webmail`
 * (décision D72 : lien vers le webmail de l'équipe).
 */
enum Permission: string
{
    case ManageArticles = 'manage_articles';
    case PublishContent = 'publish_content';
    case ManageBooks = 'manage_books';
    case ManageCollaborations = 'manage_collaborations';
    case ManagePages = 'manage_pages';
    case ManageMedia = 'manage_media';
    case ManageSettings = 'manage_settings';
    case ManageUsers = 'manage_users';
    case InviteUsers = 'invite_users';
    case ManageNewsletter = 'manage_newsletter';
    case AccessWebmail = 'access_webmail';

    public function label(): string
    {
        return match ($this) {
            self::ManageArticles => 'Gérer les publications',
            self::PublishContent => 'Publier et programmer',
            self::ManageBooks => 'Gérer les livres',
            self::ManageCollaborations => 'Gérer les collaborations',
            self::ManagePages => 'Gérer les pages',
            self::ManageMedia => 'Gérer la médiathèque',
            self::ManageSettings => 'Gérer les paramètres',
            self::ManageUsers => 'Gérer les membres et les rôles',
            self::InviteUsers => 'Inviter des membres',
            self::ManageNewsletter => 'Gérer la newsletter',
            self::AccessWebmail => 'Accéder au webmail',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ManageArticles => 'Les 8 types de publications, la modération de leurs avis',
            self::PublishContent => 'Passer un contenu en « publié » ou « programmé » (sinon : soumission en relecture)',
            self::ManageBooks => 'Les livres, la modération de leurs avis',
            self::ManageCollaborations => 'Les demandes de collaboration et leurs pièces jointes',
            self::ManagePages => 'Les pages éditables (Meet, Accueil, pied de page)',
            self::ManageMedia => 'La médiathèque',
            self::ManageSettings => 'Nom du site, SEO, logo, favicon, réflexion de la semaine, réseaux sociaux',
            self::ManageUsers => 'Membres, rôles, permissions des rôles',
            self::InviteUsers => 'Envoi et révocation d\'invitations',
            self::ManageNewsletter => 'Abonnés à la newsletter et synchronisation Brevo',
            self::AccessWebmail => 'Lien vers la messagerie de l\'équipe (Roundcube LWS) ; chaque boîte demande ses propres identifiants',
        };
    }
}
