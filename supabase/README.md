# Base de données Yewtod SS — Supabase

## 1. Appliquer le schéma

Dans le Dashboard Supabase du projet → **SQL Editor**, exécuter les fichiers
de `supabase/migrations/` **dans l'ordre**, puis `supabase/seed.sql` :

1. `migrations/001_schema_core.sql` — extensions, enums, rôles, permissions, profils, fonctions `has_permission`/`get_my_permissions`, trigger de création de profil.
2. `migrations/002_content_tables.sql` — les 8 tables de "Works" (articles, rapports, études, notes de recherche, séries et épisodes documentaires, expérimentations, visualisations de données).
3. `migrations/003_books_collaborations.sql` — livres, avis de livres, collaborations.
4. `migrations/004_settings_media_newsletter_invitations.sql` — paramètres du site, pages éditables, réseaux sociaux, newsletter, médiathèque, invitations.
5. `migrations/005_rls_policies.sql` — Row Level Security sur toutes les tables.
6. `migrations/006_storage_buckets.sql` — buckets Storage (`covers`, `documents`, `media-library`, `avatars`) + policies.
7. `seed.sql` — rôles par défaut (Super Admin, Administrateur, Éditeur, Contributeur, Modérateur des collaborations), catalogue de permissions, paramètres de départ.

Si tu préfères la CLI Supabase (`supabase db push` / `supabase migration up`),
les fichiers sont déjà nommés dans l'ordre attendu par la CLI.

## 2. Créer le premier compte Super Admin

Le SQL seul ne peut pas créer un utilisateur `auth.users` avec mot de passe.
Après avoir appliqué le schéma :

1. Dashboard → **Authentication → Users → Add user**, créer ton compte avec ton email et un mot de passe.
2. Un profil `profiles` est automatiquement créé (statut `invited`) grâce au trigger `on_auth_user_created`.
3. Dans le SQL Editor, promouvoir ce compte en Super Admin actif :

```sql
update public.profiles
set role_id = (select id from public.roles where name = 'Super Admin'),
    status = 'active'
where email = 'ton-email@example.com';
```

4. Tu peux maintenant te connecter sur `/login` avec ce compte et accéder au dashboard complet.

## 3. Variables d'environnement pour le frontend

Copier `.env.example` vers `.env.local` à la racine du projet et renseigner :

```
VITE_SUPABASE_URL=https://xxxx.supabase.co
VITE_SUPABASE_ANON_KEY=eyJ...
```

La clé `service_role` ne doit **jamais** être exposée côté client : elle n'est
utilisée que dans les Edge Functions (`supabase/functions/`), configurée comme
secret via `supabase secrets set` ou dans les variables d'environnement du
projet Supabase (elle y est déjà disponible automatiquement sous
`SUPABASE_SERVICE_ROLE_KEY`).

## 4. Modèle de permissions

| Permission             | Donne accès à                                                        |
|-------------------------|-----------------------------------------------------------------------|
| `manage_articles`       | CRUD sur les 8 types de "Works" (articles → visualisations de données) |
| `manage_books`          | CRUD sur les fiches livres                                            |
| `manage_collaborations` | Lecture/traitement des demandes de collaboration                     |
| `manage_pages`          | Édition de Meet Yewtod, Home, footer                                  |
| `manage_media`          | Médiathèque                                                           |
| `manage_settings`       | Paramètres du site, réseaux sociaux, réflexion de la semaine          |
| `manage_users`          | Membres, rôles, permissions                                          |
| `invite_users`          | Envoi d'invitations (menu "Inviter")                                 |
| `manage_newsletter`     | Liste des abonnés à la newsletter                                     |

Seul un rôle possédant `manage_users` peut créer un rôle personnalisé et lui
attribuer `invite_users` — ce n'est jamais coché par défaut sur un rôle
non-système.

## 5. Ajouter un rôle personnalisé

Dashboard Yewtod SS → **Utilisateurs & rôles → Rôles → Nouveau rôle**, ou en SQL :

```sql
insert into public.roles (name, description) values ('Nom du rôle', 'Description');

insert into public.role_permissions (role_id, permission_id)
select r.id, p.id
from public.roles r
join public.permissions p on p.key in ('manage_articles', 'manage_media') -- permissions choisies
where r.name = 'Nom du rôle';
```

## 6. Inviter un membre

Dashboard → **Utilisateurs & rôles → Inviter**, avec un compte disposant de la
permission `invite_users`. Cela appelle l'Edge Function `invite-user`, qui :

1. Crée le compte via `supabase.auth.admin.inviteUserByEmail` (email automatique).
2. Insère une ligne dans `invitations` (statut `pending`).
3. Le trigger `on_auth_user_created` crée le profil (`status = 'invited'`).
4. La personne invitée clique sur le lien, définit son mot de passe, complète
   son profil → `status` passe à `active` → accès au dashboard limité à son rôle.

## 7. Publier un contenu

Dashboard → module correspondant (Articles, Rapports, Études, …) → éditeur
Markdown → choisir `draft` / `published` / `scheduled` et une date. Le contenu
publié apparaît immédiatement sur le site public (`Works`, `Books`, `Home`).
