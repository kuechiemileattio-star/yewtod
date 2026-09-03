-- ============================================================================
-- 005_rls_policies.sql
-- Row Level Security for every table.
--
-- Pattern for content tables (articles, reports, studies, research_notes,
-- documentary_series, documentary_episodes, experiments, data_visualizations):
--   - anyone (incl. anonymous) can SELECT rows where status = 'published'
--   - holders of `manage_articles` can SELECT/INSERT/UPDATE/DELETE any row
-- Books follow the same pattern gated by `manage_books`.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- roles / permissions / role_permissions
-- ----------------------------------------------------------------------------
alter table public.roles enable row level security;
alter table public.permissions enable row level security;
alter table public.role_permissions enable row level security;

create policy "roles_select_managers" on public.roles
  for select using (public.has_permission(auth.uid(), 'manage_users'));
create policy "roles_write_managers" on public.roles
  for insert with check (public.has_permission(auth.uid(), 'manage_users'));
create policy "roles_update_managers" on public.roles
  for update using (public.has_permission(auth.uid(), 'manage_users'))
  with check (public.has_permission(auth.uid(), 'manage_users'));
create policy "roles_delete_managers" on public.roles
  for delete using (public.has_permission(auth.uid(), 'manage_users'));

create policy "permissions_select_managers" on public.permissions
  for select using (public.has_permission(auth.uid(), 'manage_users'));

create policy "role_permissions_select_managers" on public.role_permissions
  for select using (public.has_permission(auth.uid(), 'manage_users'));
create policy "role_permissions_write_managers" on public.role_permissions
  for insert with check (public.has_permission(auth.uid(), 'manage_users'));
create policy "role_permissions_delete_managers" on public.role_permissions
  for delete using (public.has_permission(auth.uid(), 'manage_users'));

-- ----------------------------------------------------------------------------
-- profiles
-- ----------------------------------------------------------------------------
alter table public.profiles enable row level security;

create policy "profiles_select_self_or_manager" on public.profiles
  for select using (id = auth.uid() or public.has_permission(auth.uid(), 'manage_users'));
create policy "profiles_update_self_or_manager" on public.profiles
  for update using (id = auth.uid() or public.has_permission(auth.uid(), 'manage_users'))
  with check (id = auth.uid() or public.has_permission(auth.uid(), 'manage_users'));
-- No client-side INSERT policy: profiles are created exclusively by the
-- on_auth_user_created trigger (security definer) when an invite is accepted.
create policy "profiles_delete_manager" on public.profiles
  for delete using (public.has_permission(auth.uid(), 'manage_users'));

-- ----------------------------------------------------------------------------
-- Content tables — manage_articles
-- ----------------------------------------------------------------------------
do $$
declare
  t text;
  tables text[] := array[
    'articles', 'reports', 'studies', 'research_notes',
    'documentary_series', 'documentary_episodes',
    'experiments', 'data_visualizations'
  ];
begin
  foreach t in array tables loop
    execute format('alter table public.%I enable row level security', t);

    execute format($f$
      create policy "%1$s_select_public_or_manager" on public.%1$s
        for select using (status = 'published' or public.has_permission(auth.uid(), 'manage_articles'))
    $f$, t);

    execute format($f$
      create policy "%1$s_insert_manager" on public.%1$s
        for insert with check (public.has_permission(auth.uid(), 'manage_articles'))
    $f$, t);

    execute format($f$
      create policy "%1$s_update_manager" on public.%1$s
        for update using (public.has_permission(auth.uid(), 'manage_articles'))
        with check (public.has_permission(auth.uid(), 'manage_articles'))
    $f$, t);

    execute format($f$
      create policy "%1$s_delete_manager" on public.%1$s
        for delete using (public.has_permission(auth.uid(), 'manage_articles'))
    $f$, t);
  end loop;
end $$;

-- ----------------------------------------------------------------------------
-- Books — manage_books
-- ----------------------------------------------------------------------------
alter table public.books enable row level security;

create policy "books_select_public_or_manager" on public.books
  for select using (status = 'published' or public.has_permission(auth.uid(), 'manage_books'));
create policy "books_insert_manager" on public.books
  for insert with check (public.has_permission(auth.uid(), 'manage_books'));
create policy "books_update_manager" on public.books
  for update using (public.has_permission(auth.uid(), 'manage_books'))
  with check (public.has_permission(auth.uid(), 'manage_books'));
create policy "books_delete_manager" on public.books
  for delete using (public.has_permission(auth.uid(), 'manage_books'));

-- Book reviews: public can read + submit, only managers can delete (moderation)
alter table public.book_reviews enable row level security;

create policy "book_reviews_select_all" on public.book_reviews
  for select using (true);
create policy "book_reviews_insert_all" on public.book_reviews
  for insert with check (true);
create policy "book_reviews_delete_manager" on public.book_reviews
  for delete using (public.has_permission(auth.uid(), 'manage_books'));

-- ----------------------------------------------------------------------------
-- Collaborations — public insert, manage_collaborations for the rest
-- ----------------------------------------------------------------------------
alter table public.collaborations enable row level security;

create policy "collaborations_insert_public" on public.collaborations
  for insert with check (true);
create policy "collaborations_select_manager" on public.collaborations
  for select using (public.has_permission(auth.uid(), 'manage_collaborations'));
create policy "collaborations_update_manager" on public.collaborations
  for update using (public.has_permission(auth.uid(), 'manage_collaborations'))
  with check (public.has_permission(auth.uid(), 'manage_collaborations'));
create policy "collaborations_delete_manager" on public.collaborations
  for delete using (public.has_permission(auth.uid(), 'manage_collaborations'));

-- ----------------------------------------------------------------------------
-- Site settings & pages — public read, manage_settings / manage_pages write
-- ----------------------------------------------------------------------------
alter table public.site_settings enable row level security;

create policy "site_settings_select_all" on public.site_settings
  for select using (true);
create policy "site_settings_write_manager" on public.site_settings
  for insert with check (public.has_permission(auth.uid(), 'manage_settings'));
create policy "site_settings_update_manager" on public.site_settings
  for update using (public.has_permission(auth.uid(), 'manage_settings'))
  with check (public.has_permission(auth.uid(), 'manage_settings'));
create policy "site_settings_delete_manager" on public.site_settings
  for delete using (public.has_permission(auth.uid(), 'manage_settings'));

alter table public.pages enable row level security;

create policy "pages_select_all" on public.pages
  for select using (true);
create policy "pages_write_manager" on public.pages
  for insert with check (public.has_permission(auth.uid(), 'manage_pages'));
create policy "pages_update_manager" on public.pages
  for update using (public.has_permission(auth.uid(), 'manage_pages'))
  with check (public.has_permission(auth.uid(), 'manage_pages'));
create policy "pages_delete_manager" on public.pages
  for delete using (public.has_permission(auth.uid(), 'manage_pages'));

alter table public.social_links enable row level security;

create policy "social_links_select_all" on public.social_links
  for select using (true);
create policy "social_links_write_manager" on public.social_links
  for insert with check (public.has_permission(auth.uid(), 'manage_settings'));
create policy "social_links_update_manager" on public.social_links
  for update using (public.has_permission(auth.uid(), 'manage_settings'))
  with check (public.has_permission(auth.uid(), 'manage_settings'));
create policy "social_links_delete_manager" on public.social_links
  for delete using (public.has_permission(auth.uid(), 'manage_settings'));

-- ----------------------------------------------------------------------------
-- Newsletter — public insert (subscribe), manage_newsletter for the rest
-- ----------------------------------------------------------------------------
alter table public.newsletter_subscribers enable row level security;

create policy "newsletter_insert_public" on public.newsletter_subscribers
  for insert with check (true);
create policy "newsletter_select_manager" on public.newsletter_subscribers
  for select using (public.has_permission(auth.uid(), 'manage_newsletter'));
create policy "newsletter_update_manager" on public.newsletter_subscribers
  for update using (public.has_permission(auth.uid(), 'manage_newsletter'))
  with check (public.has_permission(auth.uid(), 'manage_newsletter'));
create policy "newsletter_delete_manager" on public.newsletter_subscribers
  for delete using (public.has_permission(auth.uid(), 'manage_newsletter'));

-- ----------------------------------------------------------------------------
-- Media library — manage_media only (files are served via public Storage URLs,
-- so the catalogue table itself does not need to be publicly readable)
-- ----------------------------------------------------------------------------
alter table public.media enable row level security;

create policy "media_select_manager" on public.media
  for select using (public.has_permission(auth.uid(), 'manage_media'));
create policy "media_insert_manager" on public.media
  for insert with check (public.has_permission(auth.uid(), 'manage_media'));
create policy "media_update_manager" on public.media
  for update using (public.has_permission(auth.uid(), 'manage_media'))
  with check (public.has_permission(auth.uid(), 'manage_media'));
create policy "media_delete_manager" on public.media
  for delete using (public.has_permission(auth.uid(), 'manage_media'));

-- ----------------------------------------------------------------------------
-- Invitations — invite_users only
-- ----------------------------------------------------------------------------
alter table public.invitations enable row level security;

create policy "invitations_select_inviters" on public.invitations
  for select using (public.has_permission(auth.uid(), 'invite_users'));
create policy "invitations_insert_inviters" on public.invitations
  for insert with check (public.has_permission(auth.uid(), 'invite_users'));
create policy "invitations_update_inviters" on public.invitations
  for update using (public.has_permission(auth.uid(), 'invite_users'))
  with check (public.has_permission(auth.uid(), 'invite_users'));
create policy "invitations_delete_inviters" on public.invitations
  for delete using (public.has_permission(auth.uid(), 'invite_users'));
