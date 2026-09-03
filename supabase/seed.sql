-- ============================================================================
-- seed.sql
-- Default roles, permissions, role_permissions, and starter site content.
-- Run this AFTER all files in supabase/migrations/ have been applied.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Permissions catalogue
-- ----------------------------------------------------------------------------
insert into public.permissions (key, label, description) values
  ('manage_articles',       'Gérer les publications',   'Créer/modifier/supprimer articles, rapports, études, notes de recherche, séries documentaires, expérimentations, visualisations de données'),
  ('manage_books',          'Gérer les livres',         'Créer/modifier/supprimer les fiches livres'),
  ('manage_collaborations', 'Gérer les collaborations', 'Consulter et traiter les demandes de collaboration reçues'),
  ('manage_pages',          'Gérer les pages',          'Modifier le contenu de "Meet Yewtod", la Home et le footer'),
  ('manage_media',          'Gérer les médias',         'Accéder à la bibliothèque de médias et y ajouter des fichiers'),
  ('manage_settings',       'Gérer les paramètres',     'Modifier les informations du site, le SEO, les réseaux sociaux et la réflexion de la semaine'),
  ('manage_users',          'Gérer les utilisateurs',   'Gérer les membres, les rôles et les permissions'),
  ('invite_users',          'Inviter des membres',      'Envoyer des invitations à rejoindre le dashboard'),
  ('manage_newsletter',     'Gérer la newsletter',      'Consulter les abonnés et préparer des campagnes')
on conflict (key) do nothing;

-- ----------------------------------------------------------------------------
-- Default roles
-- ----------------------------------------------------------------------------
insert into public.roles (name, description, is_system_role) values
  ('Super Admin',    'Accès complet à toutes les fonctionnalités du site.', true),
  ('Administrateur', 'Accès complet hors gestion des rôles système.', false),
  ('Éditeur',        'Peut créer et publier des travaux.', false),
  ('Contributeur',   'Peut rédiger des travaux en brouillon pour relecture.', false),
  ('Modérateur des collaborations', 'Traite les demandes de collaboration reçues.', false)
on conflict (name) do nothing;

-- Super Admin: every permission
insert into public.role_permissions (role_id, permission_id)
select r.id, p.id
from public.roles r
cross join public.permissions p
where r.name = 'Super Admin'
on conflict do nothing;

-- Administrateur: every permission except none removed by default
-- (kept distinct from Super Admin so the system role stays untouched/undeletable)
insert into public.role_permissions (role_id, permission_id)
select r.id, p.id
from public.roles r
cross join public.permissions p
where r.name = 'Administrateur'
on conflict do nothing;

-- Éditeur: content + media, no invite/users/settings
insert into public.role_permissions (role_id, permission_id)
select r.id, p.id
from public.roles r
join public.permissions p on p.key in ('manage_articles', 'manage_books', 'manage_media')
where r.name = 'Éditeur'
on conflict do nothing;

-- Contributeur: content only (still gated to draft workflow at the UI level)
insert into public.role_permissions (role_id, permission_id)
select r.id, p.id
from public.roles r
join public.permissions p on p.key in ('manage_articles', 'manage_media')
where r.name = 'Contributeur'
on conflict do nothing;

-- Modérateur des collaborations: collaborations only
insert into public.role_permissions (role_id, permission_id)
select r.id, p.id
from public.roles r
join public.permissions p on p.key = 'manage_collaborations'
where r.name = 'Modérateur des collaborations'
on conflict do nothing;

-- ----------------------------------------------------------------------------
-- Default site settings
-- ----------------------------------------------------------------------------
insert into public.site_settings (key, value) values
  ('site_name', '"Yewtod SS"'),
  ('seo_description', '"Yewtod SS, média personnel de réflexion sur les sciences sociales, les systèmes complexes, l''économie, la politique publique et l''intelligence artificielle."'),
  ('logo_url', '""'),
  ('favicon_url', '""'),
  ('reflection_of_week', '{"text": "", "author": "Yewtod", "date": null}')
on conflict (key) do nothing;

insert into public.pages (key, title, content) values
  ('meet_yewtod', 'Meet Yewtod', '{}'),
  ('home', 'Home', '{}'),
  ('footer', 'Footer', '{}')
on conflict (key) do nothing;

-- ============================================================================
-- Creating the first Super Admin account
-- ============================================================================
-- SQL alone cannot create an auth.users row with a usable password hash, so
-- create the first account from the Supabase Dashboard (Authentication > Add
-- user) or via the Admin API, THEN run the snippet below with that user's
-- email to promote them to Super Admin and activate the profile:
--
--   update public.profiles
--   set role_id = (select id from public.roles where name = 'Super Admin'),
--       status = 'active'
--   where email = 'REPLACE_WITH_ADMIN_EMAIL';
-- ============================================================================
