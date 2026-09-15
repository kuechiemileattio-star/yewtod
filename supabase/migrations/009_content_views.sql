-- ============================================================================
-- 009_content_views.sql
-- Real page-view tracking for the dashboard's "Le plus consulté cette
-- semaine" widget. One row per page load of a published content/book detail
-- page; anyone (incl. anonymous visitors) can log a view, only managers can
-- read the aggregates.
-- ============================================================================

create table public.content_views (
  id bigint generated always as identity primary key,
  table_name text not null,
  content_id uuid not null,
  viewed_at timestamptz not null default now()
);

create index idx_content_views_lookup on public.content_views(table_name, content_id, viewed_at);
create index idx_content_views_viewed_at on public.content_views(viewed_at);

alter table public.content_views enable row level security;

create policy "content_views_insert_public" on public.content_views
  for insert with check (true);
create policy "content_views_select_manager" on public.content_views
  for select using (public.has_permission(auth.uid(), 'manage_articles'));

-- Every "Works" table + books, flattened to (id, title, table_name, category,
-- slug) so a single query can join view counts back to a title regardless of
-- which of the 9 tables the content lives in.
create or replace view public.content_index as
  select id, title, 'articles' as table_name, 'Articles' as category, slug from public.articles
  union all
  select id, title, 'reports', 'Rapports', slug from public.reports
  union all
  select id, title, 'studies', 'Études', slug from public.studies
  union all
  select id, title, 'research_notes', 'Notes de recherche', slug from public.research_notes
  union all
  select id, title, 'documentary_series', 'Séries documentaires', slug from public.documentary_series
  union all
  select id, title, 'documentary_episodes', 'Épisodes documentaires', slug from public.documentary_episodes
  union all
  select id, title, 'experiments', 'Expérimentations', slug from public.experiments
  union all
  select id, title, 'data_visualizations', 'Visualisations de données', slug from public.data_visualizations
  union all
  select id, title, 'books', 'Livres', slug from public.books;

-- Top N most-viewed items since a given timestamp. security definer because
-- content_views/content_index aren't otherwise readable by managers directly
-- (RLS on the underlying tables would require manage_articles AND
-- manage_books); the permission check below stands in for that.
create or replace function public.get_top_viewed(since timestamptz, limit_count int default 3)
returns table(table_name text, content_id uuid, title text, category text, slug text, views bigint)
language plpgsql
stable
security definer
set search_path = public
as $$
begin
  if not public.has_permission(auth.uid(), 'manage_articles') then
    raise exception 'insufficient_privilege';
  end if;
  return query
    select ci.table_name, ci.id, ci.title, ci.category, ci.slug, count(cv.id)::bigint as views
    from public.content_views cv
    join public.content_index ci on ci.table_name = cv.table_name and ci.id = cv.content_id
    where cv.viewed_at >= since
    group by ci.table_name, ci.id, ci.title, ci.category, ci.slug
    order by views desc
    limit limit_count;
end;
$$;

grant execute on function public.get_top_viewed(timestamptz, int) to authenticated;
