-- ============================================================================
-- 015_content_download_tracking.sql
-- SCIRP shows both a "Views" and a "Downloads" counter on every article —
-- `content_views` (009_content_views.sql) only ever tracked page views. This
-- adds a `kind` column ('view' | 'download') to the same table instead of a
-- parallel one, plus the matching public read-only counters
-- (013_public_view_count.sql's get_public_view_count is redefined to filter
-- on kind = 'view').
-- ============================================================================

alter table public.content_views add column if not exists kind text not null default 'view';
alter table public.content_views drop constraint if exists content_views_kind_check;
alter table public.content_views add constraint content_views_kind_check check (kind in ('view', 'download'));

create or replace function public.get_public_view_count(p_table text, p_content_id uuid)
returns bigint
language sql
stable
security definer
set search_path = public
as $$
  select count(*)::bigint
  from public.content_views
  where table_name = p_table and content_id = p_content_id and kind = 'view';
$$;

create or replace function public.get_public_download_count(p_table text, p_content_id uuid)
returns bigint
language sql
stable
security definer
set search_path = public
as $$
  select count(*)::bigint
  from public.content_views
  where table_name = p_table and content_id = p_content_id and kind = 'download';
$$;

grant execute on function public.get_public_download_count(text, uuid) to anon, authenticated;
