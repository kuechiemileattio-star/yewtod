-- ============================================================================
-- 001_schema_core.sql
-- Extensions, enums, helper functions, roles/permissions, profiles, auth wiring
-- ============================================================================

create extension if not exists "pgcrypto";
create extension if not exists "unaccent";

-- ----------------------------------------------------------------------------
-- Enums
-- ----------------------------------------------------------------------------
create type content_status as enum ('draft', 'published', 'scheduled');
create type profile_status as enum ('invited', 'active', 'suspended');
create type invitation_status as enum ('pending', 'accepted', 'expired', 'revoked');
create type collaboration_status as enum ('nouveau', 'en_cours', 'archive');
create type research_progress as enum ('idee', 'en_cours', 'abouti');
create type book_difficulty as enum ('accessible', 'intermediaire', 'exigeant');
create type newsletter_status as enum ('active', 'unsubscribed');
create type media_type as enum ('image', 'video', 'pdf', 'document', 'other');

-- ----------------------------------------------------------------------------
-- Generic helper functions
-- ----------------------------------------------------------------------------

-- Slugify a title into a url-safe string
create or replace function public.slugify(input text)
returns text
language sql
immutable
as $$
  select trim(both '-' from
    regexp_replace(
      lower(regexp_replace(unaccent(coalesce(input, '')), '[^a-zA-Z0-9\s-]', '', 'g')),
      '\s+', '-', 'g'
    )
  );
$$;

-- Auto-touch updated_at on any table that has that column
create or replace function public.touch_updated_at()
returns trigger
language plpgsql
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

-- Auto-generate a unique slug from `title` when slug is left blank.
-- Reused as a BEFORE INSERT trigger on every content table (all share a `title` + `slug` column).
create or replace function public.ensure_slug()
returns trigger
language plpgsql
as $$
declare
  base_slug text;
  candidate text;
  suffix int := 0;
  hit_count int;
begin
  if new.slug is null or new.slug = '' then
    base_slug := public.slugify(new.title);
    if base_slug = '' then
      base_slug := 'item';
    end if;
    candidate := base_slug;
    loop
      execute format('select count(*) from %I where slug = $1', TG_TABLE_NAME) into hit_count using candidate;
      exit when hit_count = 0;
      suffix := suffix + 1;
      candidate := base_slug || '-' || suffix;
    end loop;
    new.slug := candidate;
  end if;
  return new;
end;
$$;

-- Rough reading time estimate (words / 200 wpm), used for articles
create or replace function public.compute_reading_time(body text)
returns int
language sql
immutable
as $$
  select greatest(1, ceil(array_length(regexp_split_to_array(trim(coalesce(body, '')), '\s+'), 1) / 200.0)::int);
$$;

-- ----------------------------------------------------------------------------
-- Roles & permissions (RBAC)
-- ----------------------------------------------------------------------------

create table public.roles (
  id uuid primary key default gen_random_uuid(),
  name text not null unique,
  description text,
  is_system_role boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_roles_touch before update on public.roles
  for each row execute function public.touch_updated_at();

-- Prevent deletion of built-in system roles (e.g. Super Admin)
create or replace function public.guard_system_role_delete()
returns trigger
language plpgsql
as $$
begin
  if old.is_system_role then
    raise exception 'Le rôle "%" est un rôle système et ne peut pas être supprimé.', old.name;
  end if;
  return old;
end;
$$;

create trigger trg_roles_guard_delete before delete on public.roles
  for each row execute function public.guard_system_role_delete();

create table public.permissions (
  id uuid primary key default gen_random_uuid(),
  key text not null unique,
  label text not null,
  description text
);

create table public.role_permissions (
  role_id uuid not null references public.roles(id) on delete cascade,
  permission_id uuid not null references public.permissions(id) on delete cascade,
  primary key (role_id, permission_id)
);

-- ----------------------------------------------------------------------------
-- Profiles (extends auth.users)
-- ----------------------------------------------------------------------------

create table public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  full_name text,
  email text,
  avatar_url text,
  bio text,
  role_id uuid references public.roles(id),
  status profile_status not null default 'invited',
  invited_by uuid references public.profiles(id),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_profiles_touch before update on public.profiles
  for each row execute function public.touch_updated_at();

-- ----------------------------------------------------------------------------
-- Permission-check functions (security definer: bypass RLS to read the
-- caller's own role, safe because they only ever read data tied to auth.uid())
-- ----------------------------------------------------------------------------

create or replace function public.has_permission(user_id uuid, perm_key text)
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select exists (
    select 1
    from public.profiles p
    join public.role_permissions rp on rp.role_id = p.role_id
    join public.permissions perm on perm.id = rp.permission_id
    where p.id = user_id
      and p.status = 'active'
      and perm.key = perm_key
  );
$$;

create or replace function public.get_my_permissions()
returns setof text
language sql
stable
security definer
set search_path = public
as $$
  select perm.key
  from public.profiles p
  join public.role_permissions rp on rp.role_id = p.role_id
  join public.permissions perm on perm.id = rp.permission_id
  where p.id = auth.uid()
    and p.status = 'active';
$$;

create or replace function public.get_my_profile()
returns public.profiles
language sql
stable
security definer
set search_path = public
as $$
  select * from public.profiles where id = auth.uid();
$$;

-- ----------------------------------------------------------------------------
-- Auto-create a profile row whenever a new auth.users row appears.
-- Invitations are created via supabase.auth.admin.inviteUserByEmail() with
-- user_metadata = { full_name, role_id, invited_by } — see the invite-user
-- Edge Function. This trigger turns that into a `profiles` row with
-- status = 'invited'.
-- ----------------------------------------------------------------------------

create or replace function public.handle_new_auth_user()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  insert into public.profiles (id, email, full_name, role_id, status, invited_by)
  values (
    new.id,
    new.email,
    new.raw_user_meta_data ->> 'full_name',
    nullif(new.raw_user_meta_data ->> 'role_id', '')::uuid,
    'invited',
    nullif(new.raw_user_meta_data ->> 'invited_by', '')::uuid
  )
  on conflict (id) do nothing;
  return new;
end;
$$;

create trigger on_auth_user_created
  after insert on auth.users
  for each row execute function public.handle_new_auth_user();
