-- ============================================================================
-- 014_articles_multi_theme.sql
-- SCIRP's submission form lets an author check MULTIPLE subject fields for
-- one manuscript, not just one — this replaces the single theme/subtheme
-- pair (011_articles_editorial_taxonomy.sql) with a `themes text[]`, so one
-- article can belong to several themes at once (e.g. Économie + Sociologie).
-- Sub-theme granularity is dropped — the existing free-text `tags` field
-- already covers that finer level.
-- ============================================================================

alter table public.articles add column if not exists themes text[] not null default '{}';

update public.articles set themes = array[theme]
where theme is not null and theme <> '' and themes = '{}';

alter table public.articles drop column if exists subtheme;
alter table public.articles drop column if exists theme;

create index if not exists idx_articles_themes on public.articles using gin(themes);
