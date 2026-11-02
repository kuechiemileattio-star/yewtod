-- ============================================================================
-- 022_storage_hardening.sql
-- Étape 3 de la sécurisation : Storage.
--
-- Rappel : un fichier d'un bucket PUBLIC est servi par son URL publique
-- (/storage/v1/object/public/...) SANS consulter la RLS. Les policies SELECT
-- sur storage.objects ne servent qu'à l'API (list, download, URL signées).
--
-- Corrige :
--   - collaboration-attachments : bucket public, upload anonyme sans limite,
--     listable par tous → fuite des CV/projets des candidats, hébergement de
--     fichiers arbitraires (HTML, etc.).
--   - covers / documents / media-library / avatars : listables par tous
--     (policy storage_public_read) → énumération, PDF de brouillons compris.
--   - aucune limite de taille ni de type MIME (SVG accepté comme image).
--   - portrait du fondateur (PagesPanel) : upload impossible dans avatars ;
--     il va désormais dans media-library, ouvert à manage_pages.
--
-- Les fichiers déjà stockés ne sont pas modifiés ; les limites s'appliquent
-- aux nouveaux uploads.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1. Limites par bucket (appliquées par l'API Storage à chaque upload)
--    allowed_mime_types porte sur le Content-Type DÉCLARÉ à l'upload, qui est
--    aussi celui avec lequel le fichier sera SERVI : un HTML déclaré en PDF
--    sera servi en PDF, donc jamais interprété par le navigateur.
-- ----------------------------------------------------------------------------
update storage.buckets
set public = true,
    file_size_limit = 5 * 1024 * 1024,
    allowed_mime_types = array['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif']
where id = 'covers';

update storage.buckets
set public = true,
    file_size_limit = 2 * 1024 * 1024,
    allowed_mime_types = array['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif']
where id = 'avatars';

update storage.buckets
set public = true,
    file_size_limit = 20 * 1024 * 1024,
    allowed_mime_types = array['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif',
                               'application/pdf', 'video/mp4', 'video/webm']
where id = 'media-library';

update storage.buckets
set public = true,
    file_size_limit = 50 * 1024 * 1024,
    allowed_mime_types = array['application/pdf', 'text/csv', 'application/vnd.ms-excel', 'text/plain']
where id = 'documents';

-- Pièces jointes des candidats : données personnelles → bucket PRIVÉ.
-- Lecture uniquement par URL signée générée dans le dashboard.
update storage.buckets
set public = false,
    file_size_limit = 10 * 1024 * 1024,
    allowed_mime_types = array['application/pdf', 'application/msword',
                               'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                               'image/jpeg', 'image/png', 'image/webp']
where id = 'collaboration-attachments';


-- ----------------------------------------------------------------------------
-- 2. Qui gère quel bucket (une seule définition, réutilisée par les policies)
-- ----------------------------------------------------------------------------
create or replace function public.storage_can_manage(p_bucket text)
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select case p_bucket
    when 'covers' then
      public.has_permission(auth.uid(), 'manage_articles')
      or public.has_permission(auth.uid(), 'manage_books')
      or public.has_permission(auth.uid(), 'manage_media')
    when 'documents' then
      public.has_permission(auth.uid(), 'manage_articles')
      or public.has_permission(auth.uid(), 'manage_books')
      or public.has_permission(auth.uid(), 'manage_media')
    when 'media-library' then
      public.has_permission(auth.uid(), 'manage_articles')
      or public.has_permission(auth.uid(), 'manage_books')
      or public.has_permission(auth.uid(), 'manage_media')
      or public.has_permission(auth.uid(), 'manage_pages')
    when 'collaboration-attachments' then
      public.has_permission(auth.uid(), 'manage_collaborations')
    else false
  end;
$$;

-- anon DOIT pouvoir l'exécuter : Postgres évalue toutes les policies applicables
-- à une requête, y compris celles d'autres buckets. Sans ce droit, toute requête
-- anonyme sur storage.objects (upload du formulaire de collaboration compris)
-- échouerait en « permission denied for function ». Pour anon, elle renvoie
-- toujours false (auth.uid() est null) : rien n'est révélé.
revoke all on function public.storage_can_manage(text) from public;
grant execute on function public.storage_can_manage(text) to anon, authenticated, service_role;


-- ----------------------------------------------------------------------------
-- 3. Policies des buckets de contenu (covers, documents, media-library)
--    Plus de lecture API publique : les URL publiques suffisent au site.
-- ----------------------------------------------------------------------------
drop policy if exists "storage_public_read" on storage.objects;
drop policy if exists "storage_content_select_managers" on storage.objects;
create policy "storage_content_select_managers" on storage.objects
  for select using (
    bucket_id in ('covers', 'documents', 'media-library')
    and public.storage_can_manage(bucket_id)
  );

drop policy if exists "storage_content_write" on storage.objects;
create policy "storage_content_write" on storage.objects
  for insert with check (
    bucket_id in ('covers', 'documents', 'media-library')
    and public.storage_can_manage(bucket_id)
  );

drop policy if exists "storage_content_update" on storage.objects;
create policy "storage_content_update" on storage.objects
  for update
  using (
    bucket_id in ('covers', 'documents', 'media-library')
    and public.storage_can_manage(bucket_id)
  )
  with check (
    bucket_id in ('covers', 'documents', 'media-library')
    and public.storage_can_manage(bucket_id)
  );

drop policy if exists "storage_content_delete" on storage.objects;
create policy "storage_content_delete" on storage.objects
  for delete using (
    bucket_id in ('covers', 'documents', 'media-library')
    and public.storage_can_manage(bucket_id)
  );


-- ----------------------------------------------------------------------------
-- 4. Avatars : lecture API de son propre dossier seulement (les écritures
--    restent celles de 006 : dossier <auth.uid()>/).
-- ----------------------------------------------------------------------------
drop policy if exists "storage_avatars_select_own" on storage.objects;
create policy "storage_avatars_select_own" on storage.objects
  for select using (
    bucket_id = 'avatars' and (storage.foldername(name))[1] = auth.uid()::text
  );


-- ----------------------------------------------------------------------------
-- 5. Pièces jointes de collaboration (bucket privé)
--    - upload anonyme : UN fichier à la racine, nommé <uuid>.<extension>,
--      exactement ce que produit le formulaire public (useStorageUpload) ;
--    - lecture (URL signées) et suppression : manage_collaborations.
-- ----------------------------------------------------------------------------
drop policy if exists "storage_collab_attachments_public_read" on storage.objects;
drop policy if exists "storage_collab_attachments_select_manager" on storage.objects;
create policy "storage_collab_attachments_select_manager" on storage.objects
  for select using (
    bucket_id = 'collaboration-attachments'
    and public.storage_can_manage(bucket_id)
  );

drop policy if exists "storage_collab_attachments_public_insert" on storage.objects;
create policy "storage_collab_attachments_public_insert" on storage.objects
  for insert with check (
    bucket_id = 'collaboration-attachments'
    and cardinality(storage.foldername(name)) = 0
    and name ~ '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.(pdf|doc|docx|jpg|jpeg|png|webp)$'
  );

drop policy if exists "storage_collab_attachments_manager_delete" on storage.objects;
create policy "storage_collab_attachments_manager_delete" on storage.objects
  for delete using (
    bucket_id = 'collaboration-attachments'
    and public.storage_can_manage(bucket_id)
  );
