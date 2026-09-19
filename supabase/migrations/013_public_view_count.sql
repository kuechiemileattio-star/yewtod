-- ============================================================================
-- 013_public_view_count.sql
-- `content_views` is only readable by managers (009_content_views.sql), which
-- is right for raw rows, but a public site like SCIRP shows a simple view
-- count on every article/report page. This adds a narrow, public, read-only
-- RPC that returns just a number for one piece of content — no row-level
-- data, no permission check needed since a view count isn't sensitive.
-- ============================================================================

create or replace function public.get_public_view_count(p_table text, p_content_id uuid)
returns bigint
language sql
stable
security definer
set search_path = public
as $$
  select count(*)::bigint
  from public.content_views
  where table_name = p_table and content_id = p_content_id;
$$;

grant execute on function public.get_public_view_count(text, uuid) to anon, authenticated;
