-- ============================================================================
-- 018_content_comments.sql
-- Visitor comments/avis on any "Works" content — same idea as book_reviews
-- (003_books_collaborations.sql) but generalized via (table_name, content_id)
-- the same way content_views (009) tracks views across all 8 tables, instead
-- of one comments table per content type.
-- ============================================================================

create table public.content_comments (
  id uuid primary key default gen_random_uuid(),
  table_name text not null,
  content_id uuid not null,
  author_name text not null,
  text text not null,
  created_at timestamptz not null default now()
);

create index idx_content_comments_lookup on public.content_comments(table_name, content_id, created_at desc);

alter table public.content_comments enable row level security;

create policy "content_comments_select_all" on public.content_comments
  for select using (true);
create policy "content_comments_insert_all" on public.content_comments
  for insert with check (true);
create policy "content_comments_delete_manager" on public.content_comments
  for delete using (public.has_permission(auth.uid(), 'manage_articles'));
