-- ============================================================================
-- 019_articles_similar_articles.sql
-- Manual "À lire aussi" curation for articles, same pattern as similar_books
-- was never a column (books use similar_books jsonb from 003) — here we use
-- a plain text[] of titles, matched against the articles list client-side,
-- consistent with how ARRAY_FIELDS/adapters.js already handles list fields.
-- ============================================================================

alter table public.articles add column if not exists similar_articles text[] not null default '{}';
