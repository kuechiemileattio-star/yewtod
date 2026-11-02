-- ============================================================================
-- 023_public_writes.sql
-- Étape 4 de la sécurisation : écritures publiques (anonymes).
--
-- Corrige :
--   - avis (content_comments, book_reviews) publiés sans relecture, sans
--     limite de taille, sur n'importe quelle cible (brouillon, contenu
--     inexistant, « table » arbitraire) ;
--   - collaborations : champs sans limite ; status / created_at fixés par le
--     visiteur (une demande pouvait arriver déjà « archivée ») ;
--   - newsletter : format libre et énumération (un doublon renvoyait 23505,
--     révélant qu'une adresse est inscrite) ;
--   - content_views : vues/téléchargements insérables sur n'importe quelle cible.
--
-- Choix : les règles de format portent sur l'INSERTION publique (policies
-- INSERT ... WITH CHECK et fonctions RPC), pas sur des contraintes CHECK de
-- table : une contrainte CHECK (même NOT VALID) s'appliquerait aussi à toute
-- MISE À JOUR d'une ligne ancienne — un modérateur ne pourrait plus changer le
-- statut d'une ancienne demande non conforme.
--
-- ⚠️ Front : l'insertion directe dans newsletter_subscribers et content_views
-- est retirée → le front doit appeler subscribe_newsletter() et
-- log_content_event() (même branche que cette migration).
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1. Fonctions d'aide
--    Exécutables par anon : Postgres évalue les policies avec les droits de
--    l'appelant (voir 022). Elles ne révèlent rien d'autre qu'un booléen.
-- ----------------------------------------------------------------------------

-- Un contenu est-il PUBLIQUEMENT visible ? (mêmes règles que la RLS de lecture
-- de 005 / 012 ; liste blanche des 9 tables → pas d'injection via p_table)
create or replace function public.is_content_public(p_table text, p_id uuid)
returns boolean
language plpgsql
stable
security definer
set search_path = public
as $$
declare
  v_visible boolean;
begin
  if p_table is null or p_id is null then
    return false;
  end if;
  if p_table = 'books' then
    select exists (select 1 from public.books where id = p_id and status = 'published')
      into v_visible;
  elsif p_table in ('articles', 'reports', 'studies', 'research_notes', 'documentary_series',
                    'documentary_episodes', 'experiments', 'data_visualizations') then
    execute format(
      'select exists (select 1 from public.%I where id = $1 and (status = ''published''
         or (status = ''scheduled'' and scheduled_at is not null and scheduled_at <= now())))',
      p_table)
      into v_visible
      using p_id;
  else
    return false;
  end if;
  return coalesce(v_visible, false);
end;
$$;

-- Qui modère un avis : manage_books pour un livre, manage_articles sinon.
create or replace function public.can_moderate_comment(p_table text)
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select case
    when p_table = 'books' then public.has_permission(auth.uid(), 'manage_books')
    else public.has_permission(auth.uid(), 'manage_articles')
  end;
$$;

-- Pièces jointes d'une collaboration : 3 au plus, chemins produits par le
-- formulaire (<uuid>.<ext>, voir 022 et src/lib/attachments.js).
create or replace function public.valid_collab_attachments(p_paths text[])
returns boolean
language sql
immutable
set search_path = public
as $$
  select coalesce(cardinality(p_paths), 0) <= 3
    and not exists (
      select 1 from unnest(coalesce(p_paths, '{}')) as a(path)
      where a.path !~ '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.(pdf|doc|docx|jpg|jpeg|png|webp)$'
    );
$$;

revoke all on function public.is_content_public(text, uuid) from public;
revoke all on function public.can_moderate_comment(text) from public;
revoke all on function public.valid_collab_attachments(text[]) from public;
grant execute on function public.is_content_public(text, uuid) to anon, authenticated, service_role;
grant execute on function public.can_moderate_comment(text) to anon, authenticated, service_role;
grant execute on function public.valid_collab_attachments(text[]) to anon, authenticated, service_role;


-- ----------------------------------------------------------------------------
-- 2. Valeurs imposées pour les écritures venant de l'API publique
--    (JWT anon / authenticated ; le SQL Editor et service_role ne sont pas
--    concernés, comme pour le trigger de 021).
-- ----------------------------------------------------------------------------
create or replace function public.force_public_write_defaults()
returns trigger
language plpgsql
set search_path = public
as $$
declare
  v_jwt_role text := coalesce(
    nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'role', '');
begin
  if v_jwt_role not in ('anon', 'authenticated') then
    return new;
  end if;

  if tg_op = 'INSERT' then
    new.created_at := now();
    if tg_table_name in ('content_comments', 'book_reviews') then
      new.status := 'pending';
      new.moderated_by := null;
      new.moderated_at := null;
    elsif tg_table_name = 'collaborations' then
      new.status := 'nouveau';
      new.updated_at := now();
    end if;
    return new;
  end if;

  -- UPDATE d'un avis (modération) : seul le statut change ; on trace qui et quand.
  -- IF imbriqués (et non « A and B ») : PL/pgSQL résout toutes les colonnes
  -- d'une expression avant de l'évaluer, sans court-circuit — new.table_name
  -- ferait échouer le trigger sur book_reviews, qui n'a pas cette colonne.
  if tg_table_name = 'content_comments' then
    if (new.table_name, new.content_id, new.author_name, new.text, new.created_at)
       is distinct from (old.table_name, old.content_id, old.author_name, old.text, old.created_at) then
      raise exception 'La modération ne peut changer que le statut d''un avis.' using errcode = '42501';
    end if;
  elsif tg_table_name = 'book_reviews' then
    if (new.book_id, new.author_name, new.text, new.created_at)
       is distinct from (old.book_id, old.author_name, old.text, old.created_at) then
      raise exception 'La modération ne peut changer que le statut d''un avis.' using errcode = '42501';
    end if;
  end if;
  if new.status is distinct from old.status then
    new.moderated_by := auth.uid();
    new.moderated_at := now();
  end if;
  return new;
end;
$$;

revoke all on function public.force_public_write_defaults() from public, anon, authenticated;


-- ----------------------------------------------------------------------------
-- 3. Avis sur les contenus (content_comments) — pré-modération
-- ----------------------------------------------------------------------------
-- Colonne ajoutée avec 'approved' : les avis EXISTANTS restent visibles ;
-- puis le défaut passe à 'pending' pour les nouveaux.
alter table public.content_comments add column if not exists status text not null default 'approved';
alter table public.content_comments alter column status set default 'pending';
alter table public.content_comments drop constraint if exists content_comments_status_check;
alter table public.content_comments add constraint content_comments_status_check
  check (status in ('pending', 'approved', 'rejected'));
alter table public.content_comments add column if not exists moderated_by uuid references public.profiles(id) on delete set null;
alter table public.content_comments add column if not exists moderated_at timestamptz;
create index if not exists idx_content_comments_status on public.content_comments(status, created_at);

drop trigger if exists trg_content_comments_public_defaults on public.content_comments;
create trigger trg_content_comments_public_defaults
  before insert or update on public.content_comments
  for each row execute function public.force_public_write_defaults();

drop policy if exists "content_comments_select_all" on public.content_comments;
drop policy if exists "content_comments_select_public" on public.content_comments;
create policy "content_comments_select_public" on public.content_comments
  for select using (status = 'approved' and public.is_content_public(table_name, content_id));

drop policy if exists "content_comments_select_moderators" on public.content_comments;
create policy "content_comments_select_moderators" on public.content_comments
  for select using (public.can_moderate_comment(table_name));

drop policy if exists "content_comments_insert_all" on public.content_comments;
drop policy if exists "content_comments_insert_public" on public.content_comments;
create policy "content_comments_insert_public" on public.content_comments
  for insert with check (
    status = 'pending'
    and char_length(btrim(author_name)) between 1 and 80
    and char_length(btrim(text)) between 1 and 2000
    and public.is_content_public(table_name, content_id)
  );

drop policy if exists "content_comments_update_moderators" on public.content_comments;
create policy "content_comments_update_moderators" on public.content_comments
  for update
  using (public.can_moderate_comment(table_name))
  with check (public.can_moderate_comment(table_name));

drop policy if exists "content_comments_delete_manager" on public.content_comments;
create policy "content_comments_delete_manager" on public.content_comments
  for delete using (public.can_moderate_comment(table_name));


-- ----------------------------------------------------------------------------
-- 4. Avis sur les livres (book_reviews) — même modèle, modération manage_books
-- ----------------------------------------------------------------------------
alter table public.book_reviews add column if not exists status text not null default 'approved';
alter table public.book_reviews alter column status set default 'pending';
alter table public.book_reviews drop constraint if exists book_reviews_status_check;
alter table public.book_reviews add constraint book_reviews_status_check
  check (status in ('pending', 'approved', 'rejected'));
alter table public.book_reviews add column if not exists moderated_by uuid references public.profiles(id) on delete set null;
alter table public.book_reviews add column if not exists moderated_at timestamptz;
create index if not exists idx_book_reviews_status on public.book_reviews(status, created_at);

drop trigger if exists trg_book_reviews_public_defaults on public.book_reviews;
create trigger trg_book_reviews_public_defaults
  before insert or update on public.book_reviews
  for each row execute function public.force_public_write_defaults();

drop policy if exists "book_reviews_select_all" on public.book_reviews;
drop policy if exists "book_reviews_select_public" on public.book_reviews;
create policy "book_reviews_select_public" on public.book_reviews
  for select using (status = 'approved' and public.is_content_public('books', book_id));

drop policy if exists "book_reviews_select_moderators" on public.book_reviews;
create policy "book_reviews_select_moderators" on public.book_reviews
  for select using (public.has_permission(auth.uid(), 'manage_books'));

drop policy if exists "book_reviews_insert_all" on public.book_reviews;
drop policy if exists "book_reviews_insert_public" on public.book_reviews;
create policy "book_reviews_insert_public" on public.book_reviews
  for insert with check (
    status = 'pending'
    and char_length(btrim(author_name)) between 1 and 80
    and char_length(btrim(text)) between 1 and 2000
    and public.is_content_public('books', book_id)
  );

drop policy if exists "book_reviews_update_moderators" on public.book_reviews;
create policy "book_reviews_update_moderators" on public.book_reviews
  for update
  using (public.has_permission(auth.uid(), 'manage_books'))
  with check (public.has_permission(auth.uid(), 'manage_books'));

drop policy if exists "book_reviews_delete_manager" on public.book_reviews;
create policy "book_reviews_delete_manager" on public.book_reviews
  for delete using (public.has_permission(auth.uid(), 'manage_books'));


-- ----------------------------------------------------------------------------
-- 5. Demandes de collaboration
-- ----------------------------------------------------------------------------
drop trigger if exists trg_collaborations_public_defaults on public.collaborations;
create trigger trg_collaborations_public_defaults
  before insert on public.collaborations
  for each row execute function public.force_public_write_defaults();

drop policy if exists "collaborations_insert_public" on public.collaborations;
create policy "collaborations_insert_public" on public.collaborations
  for insert with check (
    status = 'nouveau'
    and char_length(btrim(name)) between 1 and 120
    and (organization is null or char_length(organization) <= 200)
    and char_length(email) <= 254
    and email ~* '^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$'
    and char_length(btrim(subject)) between 1 and 200
    and char_length(btrim(type)) between 1 and 60
    and (description is null or char_length(description) <= 5000)
    and public.valid_collab_attachments(attachments)
  );


-- ----------------------------------------------------------------------------
-- 6. Newsletter : inscription uniquement via subscribe_newsletter()
--    Même réponse que l'adresse soit nouvelle ou déjà inscrite → plus
--    d'énumération. Une adresse désinscrite n'est pas réactivée ici.
-- ----------------------------------------------------------------------------
drop policy if exists "newsletter_insert_public" on public.newsletter_subscribers;

create or replace function public.subscribe_newsletter(p_email text)
returns void
language plpgsql
volatile
security definer
set search_path = public
as $$
declare
  v_email text := lower(btrim(coalesce(p_email, '')));
begin
  if char_length(v_email) = 0 or char_length(v_email) > 254
     or v_email !~ '^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$' then
    raise exception 'Adresse email invalide.' using errcode = '22023';
  end if;
  if not exists (select 1 from public.newsletter_subscribers where lower(email) = v_email) then
    insert into public.newsletter_subscribers (email) values (v_email)
    on conflict (email) do nothing;
  end if;
end;
$$;

revoke all on function public.subscribe_newsletter(text) from public;
grant execute on function public.subscribe_newsletter(text) to anon, authenticated, service_role;


-- ----------------------------------------------------------------------------
-- 7. Vues / téléchargements : uniquement via log_content_event()
--    Cible vérifiée (contenu publiquement visible, liste blanche). Une cible
--    invalide est ignorée silencieusement (aucune information renvoyée).
--    NB : un compteur public anonyme reste gonflable en appelant la fonction
--    en boucle ; seul un identifiant de visiteur (IP, cookie → consentement
--    RGPD) permettrait de l'empêcher. Le front dédoublonne par session.
-- ----------------------------------------------------------------------------
drop policy if exists "content_views_insert_public" on public.content_views;

create or replace function public.log_content_event(p_table text, p_content_id uuid, p_kind text default 'view')
returns void
language plpgsql
volatile
security definer
set search_path = public
as $$
begin
  if p_kind not in ('view', 'download') or not public.is_content_public(p_table, p_content_id) then
    return;
  end if;
  insert into public.content_views (table_name, content_id, kind) values (p_table, p_content_id, p_kind);
end;
$$;

revoke all on function public.log_content_event(text, uuid, text) from public;
grant execute on function public.log_content_event(text, uuid, text) to anon, authenticated, service_role;


-- ----------------------------------------------------------------------------
-- 8. File de modération pour le dashboard
--    Avis en attente que l'appelant a le droit de modérer, avec le titre et le
--    slug du contenu (content_index n'est plus lisible directement depuis 021).
-- ----------------------------------------------------------------------------
create or replace function public.get_moderation_queue()
returns table (
  kind text, id uuid, table_name text, content_id uuid,
  content_title text, content_slug text,
  author_name text, text text, created_at timestamptz
)
language sql
stable
security definer
set search_path = public
as $$
  select q.* from (
    select 'comment'::text, c.id, c.table_name, c.content_id, ci.title, ci.slug,
           c.author_name, c.text, c.created_at
    from public.content_comments c
    left join public.content_index ci on ci.table_name = c.table_name and ci.id = c.content_id
    where c.status = 'pending' and public.can_moderate_comment(c.table_name)
    union all
    select 'review'::text, r.id, 'books'::text, r.book_id, b.title, b.slug,
           r.author_name, r.text, r.created_at
    from public.book_reviews r
    left join public.books b on b.id = r.book_id
    where r.status = 'pending' and public.has_permission(auth.uid(), 'manage_books')
  ) as q (kind, id, table_name, content_id, content_title, content_slug, author_name, text, created_at)
  order by q.created_at
  limit 500;
$$;

revoke all on function public.get_moderation_queue() from public, anon;
grant execute on function public.get_moderation_queue() to authenticated, service_role;
