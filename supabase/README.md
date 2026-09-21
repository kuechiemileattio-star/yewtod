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
7. `migrations/007_reports_pdf_metadata.sql` — colonnes `page_count` et `table_of_contents` sur `reports` (extraites automatiquement du PDF importé, voir §7).
8. `migrations/008_articles_pdf.sql` — mêmes colonnes (+ `pdf_file`) sur `articles`, pour pouvoir aussi joindre un PDF à un article.
9. `migrations/009_content_views.sql` — suivi des vues (table `content_views`, vue `content_index`, fonction `get_top_viewed`) pour le widget "Le plus consulté" du tableau de bord.
10. `migrations/010_collaboration_attachments_bucket.sql` — bucket Storage public pour les pièces jointes du formulaire de collaboration.
11. `migrations/011_articles_editorial_taxonomy.sql` — colonnes `theme`, `subtheme`, `content_type` (actualité/dossier/définition/brève) et `featured` sur `articles`.
12. `migrations/012_scheduled_publish_visibility.sql` — un contenu au statut `scheduled` devient visible publiquement tout seul une fois sa `scheduled_at` passée (la policy RLS ne vérifiait auparavant que `status = 'published'`).
13. `migrations/013_public_view_count.sql` — fonction RPC publique `get_public_view_count` pour afficher un compteur de vues sur chaque page (les lignes brutes de `content_views` restent réservées aux managers).
14. `migrations/014_articles_multi_theme.sql` — remplace `theme`/`subtheme` (un seul thème) par `themes text[]` : un article peut appartenir à plusieurs thèmes à la fois (cases à cocher dans le formulaire, comme les "Fields" de SCIRP). Migre automatiquement les données existantes.
15. `migrations/015_content_download_tracking.sql` — ajoute un compteur de téléchargements public (`get_public_download_count`), distinct du compteur de vues.
16. `migrations/016_books_purchase_entrance_fields.sql` — ajoute à `books` les champs façon fiche SCIRP : pages, mois de publication, DOI, ISBN/prix/lien d'achat (papier et ebook), description longue, exemples de chapitres, composants du livre, description ebook, biographie de l'auteur·e.
17. `seed.sql` — rôles par défaut (Super Admin, Administrateur, Éditeur, Contributeur, Modérateur des collaborations), catalogue de permissions, paramètres de départ.

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

## 6bis. Supprimer un membre

Dashboard → **Utilisateurs & rôles**, icône corbeille à côté d'un membre (indisponible sur son propre compte). Réservé à la permission `manage_users` (Super Admin l'a par défaut). Appelle l'Edge Function `delete-member`, qui supprime le compte via `supabase.auth.admin.deleteUser` — le profil (`on delete cascade`) et donc tous les droits/accès du membre disparaissent immédiatement. Action irréversible.

## 6ter. Extraction de PDF par l'IA (Claude)

Bouton **"Analyser avec Claude (IA)"**, affiché dès qu'un PDF est déposé dans le formulaire (Rapports/Articles/Livres). Contrairement à l'extraction automatique existante (signets/texte bruts du PDF, côté navigateur), ce bouton envoie le document à l'API Claude pour une vraie lecture — titre, résumé, sommaire avec un résumé par section, DOI, nombre de pages — via l'Edge Function `extract-pdf-ai`.

**Mise en place (à faire une seule fois)** :
1. Récupère une clé API sur [console.anthropic.com](https://console.anthropic.com) (section API Keys).
2. Configure le secret sur le projet Supabase :
   ```
   supabase secrets set ANTHROPIC_API_KEY=sk-ant-... --project-ref <project-ref>
   ```
3. Déploie la fonction :
   ```
   supabase functions deploy extract-pdf-ai --project-ref <project-ref>
   ```

Sans ces deux étapes, le bouton renvoie une erreur explicite ("ANTHROPIC_API_KEY n'est pas configurée..."). Coût : facturé à l'usage par l'API Anthropic (par PDF analysé), pas par Supabase.

## 7. Publier un contenu

Dashboard → module correspondant (Articles, Rapports, Études, …) → éditeur
Markdown → choisir `draft` / `published` / `scheduled` et une date. Le contenu
publié apparaît immédiatement sur le site public (`Works`, `Books`, `Home`).

Pour un **Rapport** : au moment où le PDF est déposé dans le formulaire, le
nombre de pages et le sommaire (table des matières) sont extraits
automatiquement depuis le fichier lui-même (ses signets/outline PDF, quand le
fichier en contient) — rien à saisir à la main. Un PDF sans signets ne produit
simplement pas de sommaire.

## 8. Articles de démonstration (optionnel)

`seed_demo_articles.sql` insère 10 articles d'exemple **au contenu original**
(un par thème de la taxonomie éditoriale — Économie, Sociologie, Science
politique, Intelligence artificielle, Anthropologie, Environnement & société,
Éducation, Idées & débats — mélangeant dossier/définition/actualité/brève)
pour tester le formulaire éditorial avec de vraies données une fois
`011_articles_editorial_taxonomy.sql` appliquée. Purement optionnel, à
supprimer ou modifier librement une fois le test fait.
