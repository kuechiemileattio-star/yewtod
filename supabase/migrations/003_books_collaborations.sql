-- ============================================================================
-- 003_books_collaborations.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Books
-- ----------------------------------------------------------------------------
create table public.books (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  cover_image text,
  author text not null,
  publisher text,
  publication_year int,
  category text not null,
  summary text,
  personal_review text,
  favorite_quotes text[] not null default '{}',
  difficulty_level book_difficulty not null default 'accessible',
  purchase_or_read_link text,
  similar_books jsonb not null default '[]',
  status content_status not null default 'draft',
  published_at timestamptz,
  scheduled_at timestamptz,
  created_by uuid references public.profiles(id),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_books_slug before insert on public.books for each row execute function public.ensure_slug();
create trigger trg_books_touch before update on public.books for each row execute function public.touch_updated_at();
create index idx_books_status on public.books(status);
create index idx_books_category on public.books(category);

-- Visitor book reviews (public can read; anyone can submit, moderated later if needed)
create table public.book_reviews (
  id uuid primary key default gen_random_uuid(),
  book_id uuid not null references public.books(id) on delete cascade,
  author_name text not null,
  text text not null,
  created_at timestamptz not null default now()
);

create index idx_book_reviews_book on public.book_reviews(book_id);

-- ----------------------------------------------------------------------------
-- Collaborations (public submission form)
-- ----------------------------------------------------------------------------
create table public.collaborations (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  organization text,
  email text not null,
  subject text not null,
  type text not null,
  description text,
  attachments text[] not null default '{}',
  status collaboration_status not null default 'nouveau',
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_collaborations_touch before update on public.collaborations
  for each row execute function public.touch_updated_at();
create index idx_collaborations_status on public.collaborations(status);
