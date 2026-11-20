<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Menu du dashboard (cahier § 4) : n'affiche que les modules autorisés.
 * L'affichage n'est qu'un confort : chaque route est AUSSI protégée côté
 * serveur (middleware `permission:` + policies).
 */
class DashboardMenu
{
    /**
     * @return list<array{label: string|null, items: list<array{label: string, route: string, icon: string, badge: int, external: bool}>}>
     */
    public static function for(User $user): array
    {
        $groups = [
            [null, [
                ['Vue d\'ensemble', 'dashboard', 'home', null],
            ]],
            ['Contenu', [
                ['Rapports', 'dashboard.contents.rapports', 'report', 'manage_articles'],
                ['Articles', 'dashboard.contents.articles', 'article', 'manage_articles'],
                ['Visualisations', 'dashboard.contents.visualisations-de-donnees', 'chart', 'manage_articles'],
                ['Autres publications', 'dashboard.contents.others', 'stack', 'manage_articles'],
                ['Livres', 'dashboard.books', 'book', 'manage_books'],
                ['Collaborations', 'dashboard.collaborations', 'mail', 'manage_collaborations'],
                ['Modération', 'dashboard.moderation', 'shield', 'manage_articles|manage_books'],
            ]],
            ['Administration', [
                ['Médiathèque', 'dashboard.media', 'image', 'manage_media'],
                ['Newsletter', 'dashboard.newsletter', 'send', 'manage_newsletter'],
                ['Utilisateurs & rôles', 'dashboard.users', 'users', 'manage_users|invite_users'],
                ['Pages', 'dashboard.pages', 'page', 'manage_pages'],
                ['Paramètres', 'dashboard.settings', 'cog', 'manage_settings'],
                ['Journal d\'audit', 'dashboard.audit', 'list', 'super_admin'],
                ['Webmail', 'dashboard.webmail', 'mail', 'access_webmail'],
            ]],
            [null, [
                ['Mon profil', 'dashboard.profile', 'user', null],
            ]],
        ];

        $badges = self::badges($user);
        $menu = [];
        foreach ($groups as [$label, $items]) {
            $visible = [];
            foreach ($items as [$itemLabel, $route, $icon, $permission]) {
                if (! Route::has($route) || ! self::allowed($user, $permission)) {
                    continue;
                }
                // Webmail : ouvert dans un nouvel onglet, masqué tant que WEBMAIL_URL n'est pas valide.
                $external = $route === 'dashboard.webmail';
                if ($external && ! Webmail::enabled()) {
                    continue;
                }
                $visible[] = ['label' => $itemLabel, 'route' => $route, 'icon' => $icon, 'badge' => $badges[$route] ?? 0, 'external' => $external];
            }
            if ($visible !== []) {
                $menu[] = ['label' => $label, 'items' => $visible];
            }
        }

        return $menu;
    }

    private static function allowed(User $user, ?string $permission): bool
    {
        if ($permission === null) {
            return true;
        }
        if ($permission === 'super_admin') {
            return $user->isSuperAdmin();
        }

        return collect(explode('|', $permission))->contains(fn (string $p) => $user->hasPermission($p));
    }

    /**
     * Pastilles de compteur (cahier § 10) : avis en attente, nouvelles collaborations.
     *
     * @return array<string, int>
     */
    private static function badges(User $user): array
    {
        $badges = [];
        foreach (app()->tagged('dashboard.badges') as $provider) {
            /** @var callable(User): array<string, int> $provider */
            $badges = array_merge($badges, $provider($user));
        }

        return $badges;
    }
}
