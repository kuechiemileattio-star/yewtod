-- ============================================================================
-- 016_books_purchase_entrance_fields.sql
-- BookDetail.jsx already reads all of these fields (built to match SCIRP's
-- book page — DOI, ISBN, prices, chapter samples, "Components of the Book"),
-- but the columns never existed on `books` and the admin form never had
-- inputs for them. This closes that gap.
-- ============================================================================

alter table public.books
  add column if not exists page_count int,
  add column if not exists publication_month int,
  add column if not exists doi text,
  add column if not exists isbn_paperback text,
  add column if not exists price_paperback numeric(8, 2),
  add column if not exists buy_paperback_url text,
  add column if not exists isbn_ebook text,
  add column if not exists price_ebook numeric(8, 2),
  add column if not exists buy_ebook_url text,
  add column if not exists description text,
  add column if not exists chapter_samples jsonb not null default '[]',
  add column if not exists components text[] not null default '{}',
  add column if not exists ebook_description text,
  add column if not exists author_bio text;

alter table public.books
  drop constraint if exists books_publication_month_check;
alter table public.books
  add constraint books_publication_month_check check (publication_month is null or publication_month between 1 and 12);
