-- ============================================================================
-- 011_articles_editorial_taxonomy.sql
-- Editorial taxonomy for articles, modelled on a classic editorial CMS
-- (category -> sub-category, content type, tags already existed, "featured"
-- to push a piece onto the homepage) — requested to bring the article form
-- in line with how a site like Futura-Sciences organizes its content.
-- `tags` and `scheduled_at` already exist on articles since 002_content_tables.sql.
-- ============================================================================

alter table public.articles
  add column if not exists theme text,
  add column if not exists subtheme text,
  add column if not exists content_type text not null default 'dossier',
  add column if not exists featured boolean not null default false;

alter table public.articles
  drop constraint if exists articles_content_type_check;
alter table public.articles
  add constraint articles_content_type_check check (content_type in ('actualite', 'dossier', 'definition', 'breve'));

create index if not exists idx_articles_theme on public.articles(theme);
create index if not exists idx_articles_content_type on public.articles(content_type);
create index if not exists idx_articles_featured on public.articles(featured) where featured;
