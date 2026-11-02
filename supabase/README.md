# Base de données Yewtod SS — Supabase

## ⚠️ À partir du 30 octobre 2026 : GRANT obligatoire sur toute NOUVELLE table

Supabase arrête d'accorder automatiquement l'accès API (PostgREST/supabase-js)
aux nouvelles tables du schéma `public`. **Les tables existantes ne sont pas
concernées** — seule une table créée après cette date, sans `GRANT` explicite,
serait bloquée par l'API.

Aucune des migrations actuelles n'a besoin d'être modifiée. Mais **toute
prochaine migration qui crée une nouvelle table** doit inclure ceci (à
adapter avec le vrai nom de table) :

```sql
grant select on public.ma_nouvelle_table to anon;
grant select, insert, update, delete on public.ma_nouvelle_table to authenticated;
grant select, insert, update, delete on public.ma_nouvelle_table to service_role;
```

Ne pas oublier — sans ça, la table sera invisible pour le site une fois créée.

## 0. Environnements : dev et prod

| Projet Supabase | Ref | Rôle |
|---|---|---|
| `yewtod-dev` | `htyimhfpggmofuqsdlpb` | Développement et tests — toutes les migrations y passent d'abord |
| `yewtod` | `xzhzhkrwbjndiwuoujuv` | Production — n'est modifié qu'une fois les migrations validées sur dev |

- La CLI agit sur le projet **lié** (`npx supabase link --project-ref <ref>`).
  Ce lien est un état **local** stocké dans `supabase/.temp/` — ce dossier est
  ignoré par git et ne doit jamais être commité. Avant toute commande `--linked` :
  `cat supabase/.temp/project-ref`.
- La CLI est figée en devDependency (`supabase` dans `package.json`) :
  `npx supabase …` utilise toujours la même version pour tout le monde.
- Une migration poussée (`npx supabase db push --linked`) est **définitive** :
  on ne modifie jamais un fichier déjà poussé, on en crée un nouveau (`021_…`).
- `supabase/config.toml` ne règle pas Auth/Storage du projet en ligne (ça se fait
  dans le dashboard) ; seule sa section `[functions.*]` est utilisée au déploiement.
- Contrôle de cohérence (build, audit, migrations local ↔ en ligne) :
  `npm run verify` (ou `bash scripts/verifier.sh --rapide` pour sauter `npm ci`).

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

## 4bis. Modèle de sécurité RBAC (migration 021)

La base applique elle-même ces règles (trigger `trg_profiles_guard_update`,
policies RLS, fonctions `is_super_admin` / `can_assign_role`) : le dashboard
ne fait que les afficher. Elles valent pour toute requête faite avec la clé
`anon` ou un jeton utilisateur ; le SQL Editor (`postgres`) et les Edge
Functions (`service_role`) restent des acteurs de confiance.

| Règle | Contenu |
|---|---|
| R1 | Personne ne modifie **son propre** rôle ou statut — Super Admin compris. Seule exception : `invited → active` à l'onboarding. |
| R2 | Cette activation exige une invitation `pending` non expirée pour l'email du compte. |
| R3 | `id`, `email`, `invited_by`, `created_at` d'un profil ne sont pas modifiables via l'API. |
| R4 | Le rôle d'un nouveau compte vient uniquement de son invitation (`raw_user_meta_data` est contrôlé par le client et n'est jamais lu pour un droit). |
| R5 | On n'attribue (invitation ou changement de rôle) qu'un rôle dont on possède **toutes** les permissions. Le rôle système (Super Admin) : uniquement par un Super Admin. |
| R6 | On ne modifie / suspend / supprime un membre que si l'on pourrait lui attribuer son rôle actuel → un Administrateur ne peut pas toucher au Super Admin. |
| R7 | Une invitation créée côté client a `invited_by` = soi et respecte R5 ; côté client, on ne peut ensuite que la révoquer. |
| R8 | Un rôle système et ses permissions ne se modifient que par un Super Admin ; on n'accorde à un rôle qu'une permission qu'on détient ; `is_system_role` n'est pas modifiable via l'API. |

Conséquence de R1 + R6 : il reste toujours au moins un Super Admin (il ne peut
pas se rétrograder, et seul un autre Super Admin peut le faire).

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

1. Vérifie que l'appelant peut attribuer ce rôle (`can_assign_role`, règle R5 ci-dessous).
2. Révoque les éventuelles invitations `pending` pour le même email, puis insère
   la nouvelle ligne dans `invitations` (statut `pending`) — **avant** de créer le compte.
3. Crée le compte via `supabase.auth.admin.inviteUserByEmail` (email automatique).
   Le trigger `on_auth_user_created` crée le profil (`status = 'invited'`) avec
   le rôle **lu dans l'invitation** (jamais dans les métadonnées). Si l'envoi
   échoue, l'invitation est supprimée.
4. La personne invitée clique sur le lien, définit son mot de passe, complète
   son profil → `status` passe à `active` **seulement si son invitation est
   encore `pending` et non expirée** (elle passe alors à `accepted`).
   Révoquer une invitation avant ce moment bloque donc réellement l'accès.

Un compte qui existe déjà ne peut pas être réinvité : supprimer le membre puis
renvoyer une invitation.

## 6bis. Supprimer un membre

Dashboard → **Utilisateurs & rôles**, icône corbeille à côté d'un membre (indisponible sur son propre compte). Réservé à la permission `manage_users` (Super Admin l'a par défaut). Appelle l'Edge Function `delete-member`, qui supprime le compte via `supabase.auth.admin.deleteUser` — le profil (`on delete cascade`) et donc tous les droits/accès du membre disparaissent immédiatement. Action irréversible.

## 6ter. Extraction de PDF par l'IA (Google Gemini)

Bouton **"Analyser avec Claude (IA)"**, affiché dès qu'un PDF est déposé dans le formulaire (Rapports/Articles/Livres). Contrairement à l'extraction automatique existante (signets/texte bruts du PDF, côté navigateur, gratuite, toujours active), ce bouton envoie le document à une IA pour une vraie lecture — titre, résumé, sommaire avec un résumé par section, DOI, nombre de pages — via l'Edge Function `extract-pdf-ai`.

Utilise **Google Gemini** plutôt que Claude/Anthropic : Gemini a un niveau gratuit (aucune carte bancaire requise) suffisant pour un usage personnel/occasionnel, contrairement à l'API Anthropic qui nécessite un solde payant.

**Mise en place (à faire une seule fois par projet Supabase)** :
1. Récupère une clé API **gratuite** sur [aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey) (compte Google, pas de carte bancaire). Une clé **différente** pour dev et pour prod.
2. Configure le secret sur le projet lié (jamais dans un fichier du dépôt) :
   ```
   npx supabase secrets set GEMINI_API_KEY=...
   ```
3. Déploie la fonction :
   ```
   npx supabase functions deploy extract-pdf-ai --use-api
   ```

Sans clé, le bouton renvoie une erreur explicite ("GEMINI_API_KEY n'est pas configurée..."). Le niveau gratuit de Gemini a une limite de requêtes par minute/jour — largement suffisante pour un usage occasionnel ; au-delà, Google demande de passer sur un plan payant.

**Règles de sécurité de la fonction** (étape 2) :
- réservée aux membres ayant `manage_articles` ou `manage_books` ;
- n'accepte que l'URL publique d'un PDF du bucket `documents` **de ce projet** : la fonction
  télécharge elle-même le fichier dans le Storage (protection SSRF). Un lien externe collé
  dans le formulaire n'est pas analysable par l'IA — il faut téléverser le fichier ;
- taille vérifiée **avant** téléchargement (20 Mo max), signature `%PDF-` contrôlée ;
- clé Gemini transmise dans l'en-tête `x-goog-api-key`, jamais dans l'URL ;
- réponse de Gemini filtrée (champs attendus, types, longueurs) : c'est une donnée non fiable ;
- erreurs internes : détail dans les logs Supabase, message générique au client.

## 6quater. CORS des Edge Functions (`ALLOWED_ORIGINS`)

Les trois fonctions n'acceptent les appels **navigateur** que depuis les origines listées
dans le secret `ALLOWED_ORIGINS` (séparées par des virgules ; `https://*.domaine` = un seul
sous-domaine). Secret absent → toutes les origines refusées.

```
# dev (aujourd'hui)
npx supabase secrets set ALLOWED_ORIGINS="http://localhost:5173"
# une fois Cloudflare Pages branché
npx supabase secrets set ALLOWED_ORIGINS="http://localhost:5173,https://*.yewtod.pages.dev"
# production (projet yewtod)
npx supabase secrets set ALLOWED_ORIGINS="https://yewtod.com,https://www.yewtod.com"
```

CORS n'est **pas** un contrôle d'accès (curl l'ignore) : l'accès reste protégé par le JWT et
les permissions vérifiées dans chaque fonction. La logique commune est dans
`supabase/functions/_shared/cors.ts` (tests : `deno test --allow-env supabase/functions/`).

## 6quinquies. Storage : buckets et accès (migration 022)

Deux chemins d'accès bien distincts :

- **URL publique** `…/storage/v1/object/public/<bucket>/<fichier>` : contrôlée uniquement
  par le drapeau `public` du bucket, **sans RLS**. C'est ce qu'utilise le site.
- **API Storage** (`list`, `download`, `createSignedUrl`, `upload`…) : contrôlée par la
  **RLS** sur `storage.objects`. Un bucket public n'a donc besoin d'aucune policy `SELECT`
  pour s'afficher ; une policy `SELECT` ouverte ne sert qu'à **lister** son contenu.

| Bucket | Public | Max | Types | Écriture | Lecture API |
|---|---|---|---|---|---|
| `covers` | oui | 5 Mo | JPEG, PNG, WebP, GIF, AVIF | articles / livres / médias | mêmes rôles |
| `documents` | oui | 50 Mo | PDF, CSV | articles / livres / médias | mêmes rôles |
| `media-library` | oui | 20 Mo | images, PDF, MP4, WebM | articles / livres / médias / pages | mêmes rôles |
| `avatars` | oui | 2 Mo | images | son dossier `<user_id>/` | son dossier |
| `collaboration-attachments` | **non** | 10 Mo | PDF, DOC, DOCX, JPEG, PNG, WebP | **anonyme** : un fichier `<uuid>.<ext>` à la racine | `manage_collaborations` (URL signées) |

- `allowed_mime_types` porte sur le `Content-Type` déclaré à l'upload, qui est aussi celui
  avec lequel le fichier est **servi** : un HTML déclaré en PDF est servi en PDF, donc jamais
  interprété. Le SVG (qui peut contenir du JavaScript) n'est plus accepté comme image.
- Les pièces jointes des candidats sont des **données personnelles** : bucket privé, lien
  signé valable 10 minutes généré à l'ouverture de la fiche dans le dashboard. La colonne
  `collaborations.attachments` contient le chemin du fichier ; toute autre valeur est
  affichée comme texte, jamais comme lien.
- Les limites s'appliquent aux nouveaux uploads ; les fichiers déjà stockés ne sont pas
  modifiés. Le réglage global **Storage → Settings → Upload file size limit** du dashboard
  Supabase plafonne tous les buckets (50 Mo sur l'offre Free).

## 6sexies. Écritures publiques et modération (migration 023)

| Écriture anonyme | Passage | Règles |
|---|---|---|
| Avis sur un contenu (`content_comments`) | `insert` direct | nom ≤ 80, avis ≤ 2 000 caractères ; contenu **publié** uniquement ; arrive en `pending` |
| Avis sur un livre (`book_reviews`) | `insert` direct | idem ; livre publié uniquement |
| Demande de collaboration | `insert` direct | longueurs bornées, email valide, ≤ 3 pièces jointes `<uuid>.<ext>` ; arrive toujours en `nouveau` |
| Newsletter | fonction `subscribe_newsletter(email)` | email normalisé ; **même réponse** que l'adresse soit nouvelle ou déjà inscrite |
| Vue / téléchargement | fonction `log_content_event(table, id, kind)` | contenu publié uniquement ; cible invalide ignorée sans erreur |

- **Pré-modération** : un avis n'est visible sur le site qu'au statut `approved`.
  Dashboard → **Modération** (permission `manage_articles` pour les avis d'articles,
  `manage_books` pour les avis de livres) : Approuver / Rejeter / Supprimer. La
  modération ne peut changer que le statut (jamais le texte du visiteur) ;
  `moderated_by` / `moderated_at` tracent qui a décidé.
- `created_at` et les statuts initiaux sont imposés par un trigger : le visiteur ne
  peut ni antidater, ni publier directement, ni envoyer une demande « archivée ».
- Les règles de format portent sur l'**insertion publique** (policies `INSERT … WITH CHECK`),
  pas sur des contraintes `CHECK` : celles-ci s'appliqueraient aussi aux mises à jour
  des lignes anciennes (un modérateur ne pourrait plus changer leur statut).
- Les compteurs publics restent **indicatifs** : sans identifier les visiteurs (IP,
  cookie → consentement RGPD), un appel répété peut toujours les gonfler. Le front
  compte une vue par session et par contenu.
- À venir : Cloudflare Turnstile + limitation de débit (étape 5), double opt-in de la
  newsletter avant le premier envoi réel.

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
