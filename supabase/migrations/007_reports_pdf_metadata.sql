-- ============================================================================
-- 007_reports_pdf_metadata.sql
-- Metadata auto-extracted from a report's PDF at upload time: page count and
-- table of contents (from the PDF's embedded outline/bookmarks, when present).
-- Populated client-side (see SimpleWorkForm.jsx) — never entered by hand.
-- ============================================================================

alter table public.reports
  add column if not exists page_count int,
  add column if not exists table_of_contents jsonb not null default '[]';
