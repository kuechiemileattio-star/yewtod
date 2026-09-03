-- ============================================================================
-- 002_content_tables.sql
-- The 8 "Works" content types. Each table shares the common fields required
-- by the spec: status, published_at, scheduled_at, slug, created_by, updated_at.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Articles
-- ----------------------------------------------------------------------------
create table public.articles (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  subtitle text,
  cover_image text,
  summary text,
  content text,
  quotes text[] not null default '{}',
  images text[] not null default '{}',
  embedded_videos text[] not null default '{}',
  "references" text[] not null default '{}',
  tags text[] not null default '{}',
  related_content jsonb not null default '[]',
  author_id uuid references public.profiles(id),
  co_authors text[] not null default '{}',
  reading_time_minutes int,
  status content_status not null default 'draft',
  published_at timestamptz,
  scheduled_at timestamptz,
  created_by uuid references public.profiles(id),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create or replace function public.articles_set_reading_time()
returns trigger
language plpgsql
as $$
begin
  new.reading_time_minutes := public.compute_reading_time(new.content);
  return new;
end;
$$;

create trigger trg_articles_slug before insert on public.articles for each row execute function public.ensure_slug();
create trigger trg_articles_touch before update on public.articles for each row execute function public.touch_updated_at();
create trigger trg_articles_reading_time before insert or update of content on public.articles
  for each row execute function public.articles_set_reading_time();
create index idx_articles_status on public.articles(status);
create index idx_articles_published_at on public.articles(published_at desc);
create index idx_articles_tags on public.articles using gin(tags);

-- ----------------------------------------------------------------------------
-- Rapports (reports)
-- ----------------------------------------------------------------------------
create table public.reports (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  cover_image text,
  executive_summary text,
  problem_statement text,
  context text,
  methodology text,
  analyses text,
  charts text[] not null default '{}',
  tables text[] not null default '{}',
  results text,
  recommendations text,
  conclusion text,
  appendices text[] not null default '{}',
  bibliography text[] not null default '{}',
  pdf_file text,
  version text,
  authors text[] not null default '{}',
  tags text[] not null default '{}',
  related_content jsonb not null default '[]',
  status content_status not null default 'draft',
  published_at timestamptz,
  scheduled_at timestamptz,
  created_by uuid references public.profiles(id),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_reports_slug before insert on public.reports for each row execute function public.ensure_slug();
create trigger trg_reports_touch before update on public.reports for each row execute function public.touch_updated_at();
create index idx_reports_status on public.reports(status);
create index idx_reports_published_at on public.reports(published_at desc);

-- ----------------------------------------------------------------------------
-- Études (studies)
-- ----------------------------------------------------------------------------
create table public.studies (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  cover_image text,
  research_question text,
  context text,
  objectives text,
  hypotheses text,
  methodology text,
  data_used text,
  analyses text,
  results text,
  discussion text,
  limitations text,
  perspectives text,
  bibliography text[] not null default '{}',
  authors text[] not null default '{}',
  tags text[] not null default '{}',
  related_content jsonb not null default '[]',
  status content_status not null default 'draft',
  published_at timestamptz,
  scheduled_at timestamptz,
  created_by uuid references public.profiles(id),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_studies_slug before insert on public.studies for each row execute function public.ensure_slug();
create trigger trg_studies_touch before update on public.studies for each row execute function public.touch_updated_at();
create index idx_studies_status on public.studies(status);
create index idx_studies_published_at on public.studies(published_at desc);

-- ----------------------------------------------------------------------------
-- Notes de recherche (research notes)
-- ----------------------------------------------------------------------------
create table public.research_notes (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  cover_image text,
  main_idea text,
  context text,
  observations text,
  hypotheses text,
  diagrams text[] not null default '{}',
  personal_notes text,
  "references" text[] not null default '{}',
  useful_links text[] not null default '{}',
  progress_status research_progress not null default 'idee',
  tags text[] not null default '{}',
  related_content jsonb not null default '[]',
  status content_status not null default 'draft',
  published_at timestamptz,
  scheduled_at timestamptz,
  created_by uuid references public.profiles(id),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_research_notes_slug before insert on public.research_notes for each row execute function public.ensure_slug();
create trigger trg_research_notes_touch before update on public.research_notes for each row execute function public.touch_updated_at();
create index idx_research_notes_status on public.research_notes(status);

-- ----------------------------------------------------------------------------
-- Séries documentaires (documentary series)
-- ----------------------------------------------------------------------------
create table public.documentary_series (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  description text,
  cover_image text,
  theme text,
  trailer_url text,
  videos text[] not null default '{}',
  transcript text,
  guests text[] not null default '{}',
  additional_resources text[] not null default '{}',
  related_articles jsonb not null default '[]',
  "references" text[] not null default '{}',
  tags text[] not null default '{}',
  status content_status not null default 'draft',
  published_at timestamptz,
  scheduled_at timestamptz,
  created_by uuid references public.profiles(id),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_documentary_series_slug before insert on public.documentary_series for each row execute function public.ensure_slug();
create trigger trg_documentary_series_touch before update on public.documentary_series for each row execute function public.touch_updated_at();
create index idx_documentary_series_status on public.documentary_series(status);

-- ----------------------------------------------------------------------------
-- Épisodes documentaires (documentary episodes) — belongs to a series
-- ----------------------------------------------------------------------------
create table public.documentary_episodes (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  series_id uuid not null references public.documentary_series(id) on delete cascade,
  episode_number int not null,
  title text not null,
  summary text,
  video_url text,
  transcript text,
  speakers text[] not null default '{}',
  chapters jsonb not null default '[]',
  illustrations text[] not null default '{}',
  "references" text[] not null default '{}',
  related_documents text[] not null default '{}',
  status content_status not null default 'draft',
  published_at timestamptz,
  scheduled_at timestamptz,
  created_by uuid references public.profiles(id),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (series_id, episode_number)
);

create trigger trg_documentary_episodes_slug before insert on public.documentary_episodes for each row execute function public.ensure_slug();
create trigger trg_documentary_episodes_touch before update on public.documentary_episodes for each row execute function public.touch_updated_at();
create index idx_documentary_episodes_series on public.documentary_episodes(series_id);
create index idx_documentary_episodes_status on public.documentary_episodes(status);

-- ----------------------------------------------------------------------------
-- Expérimentations (experiments)
-- ----------------------------------------------------------------------------
create table public.experiments (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  cover_image text,
  objective text,
  problem_statement text,
  protocol text,
  tools_used text[] not null default '{}',
  datasets text[] not null default '{}',
  source_code_url text,
  screenshots text[] not null default '{}',
  visualizations text[] not null default '{}',
  results text,
  analysis text,
  limitations text,
  conclusion text,
  downloadable_files text[] not null default '{}',
  tags text[] not null default '{}',
  related_content jsonb not null default '[]',
  status content_status not null default 'draft',
  published_at timestamptz,
  scheduled_at timestamptz,
  created_by uuid references public.profiles(id),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_experiments_slug before insert on public.experiments for each row execute function public.ensure_slug();
create trigger trg_experiments_touch before update on public.experiments for each row execute function public.touch_updated_at();
create index idx_experiments_status on public.experiments(status);

-- ----------------------------------------------------------------------------
-- Visualisations de données (data visualizations)
-- ----------------------------------------------------------------------------
create table public.data_visualizations (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  cover_image text,
  description text,
  data_source text,
  visualization_type text,
  chart_config jsonb not null default '{}',
  filters jsonb not null default '{}',
  legend text,
  analysis text,
  csv_file text,
  image_file text,
  source_code_url text,
  tags text[] not null default '{}',
  related_content jsonb not null default '[]',
  status content_status not null default 'draft',
  published_at timestamptz,
  scheduled_at timestamptz,
  created_by uuid references public.profiles(id),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_data_visualizations_slug before insert on public.data_visualizations for each row execute function public.ensure_slug();
create trigger trg_data_visualizations_touch before update on public.data_visualizations for each row execute function public.touch_updated_at();
create index idx_data_visualizations_status on public.data_visualizations(status);
