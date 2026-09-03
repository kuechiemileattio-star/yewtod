-- ============================================================================
-- 004_settings_media_newsletter_invitations.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Site settings (key/value store — flexible, one row per setting)
-- Well-known keys used by the frontend:
--   site_name, logo_url, favicon_url, seo_description,
--   reflection_of_week -> { "text": "...", "author": "...", "date": "..." }
-- ----------------------------------------------------------------------------
create table public.site_settings (
  key text primary key,
  value jsonb not null default '{}',
  updated_at timestamptz not null default now(),
  updated_by uuid references public.profiles(id)
);

create trigger trg_site_settings_touch before update on public.site_settings
  for each row execute function public.touch_updated_at();

-- ----------------------------------------------------------------------------
-- Editable static pages (Meet Yewtod, Home hero blocks, footer text, etc.)
-- ----------------------------------------------------------------------------
create table public.pages (
  key text primary key,
  title text,
  content jsonb not null default '{}',
  updated_at timestamptz not null default now(),
  updated_by uuid references public.profiles(id)
);

create trigger trg_pages_touch before update on public.pages
  for each row execute function public.touch_updated_at();

-- ----------------------------------------------------------------------------
-- Social links
-- ----------------------------------------------------------------------------
create table public.social_links (
  id uuid primary key default gen_random_uuid(),
  platform text not null,
  url text not null,
  display_order int not null default 0
);

-- ----------------------------------------------------------------------------
-- Newsletter subscribers
-- ----------------------------------------------------------------------------
create table public.newsletter_subscribers (
  id uuid primary key default gen_random_uuid(),
  email text not null unique,
  status newsletter_status not null default 'active',
  subscribed_at timestamptz not null default now()
);

-- ----------------------------------------------------------------------------
-- Media library
-- ----------------------------------------------------------------------------
create table public.media (
  id uuid primary key default gen_random_uuid(),
  url text not null,
  storage_path text not null,
  type media_type not null default 'other',
  size_bytes bigint,
  alt_text text,
  uploaded_by uuid references public.profiles(id),
  created_at timestamptz not null default now()
);

-- ----------------------------------------------------------------------------
-- Invitations (audit trail of invites; access is actually gated by
-- profiles.status, this table just tracks who invited whom, and lets the
-- admin revoke/resend before acceptance)
-- ----------------------------------------------------------------------------
create table public.invitations (
  id uuid primary key default gen_random_uuid(),
  email text not null,
  role_id uuid not null references public.roles(id),
  token text not null unique default encode(gen_random_bytes(24), 'hex'),
  status invitation_status not null default 'pending',
  invited_by uuid references public.profiles(id),
  sent_at timestamptz not null default now(),
  expires_at timestamptz not null default (now() + interval '7 days')
);

create index idx_invitations_email on public.invitations(email);
create index idx_invitations_status on public.invitations(status);
