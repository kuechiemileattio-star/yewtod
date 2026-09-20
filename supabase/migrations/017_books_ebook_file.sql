-- ============================================================================
-- 017_books_ebook_file.sql
-- `buy_ebook_url` (016) is an external purchase link — but Yewtod SS also
-- needs to host the ebook PDF directly (e.g. for a free/self-published book),
-- separate from that external link. Same "documents" bucket already used by
-- reports/articles.
-- ============================================================================

alter table public.books add column if not exists ebook_file text;
