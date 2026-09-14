-- ============================================================================
-- 008_articles_pdf.sql
-- Adds the same PDF support to articles as reports already have: an
-- optional document, plus page count / table of contents auto-extracted
-- from that PDF at upload time (see 007_reports_pdf_metadata.sql).
-- ============================================================================

alter table public.articles
  add column if not exists pdf_file text,
  add column if not exists page_count int,
  add column if not exists table_of_contents jsonb not null default '[]';
