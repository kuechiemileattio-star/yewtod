-- ============================================================================
-- 012_scheduled_publish_visibility.sql
-- `status = 'scheduled'` already existed in the content_status enum (see
-- 001_schema_core.sql) but the public RLS policy never actually surfaced a
-- scheduled row once its date arrived — it only ever checked `status =
-- 'published'`. This makes "programmer la publication" a real feature: a
-- scheduled row becomes publicly visible on its own once scheduled_at has
-- passed, no cron job flipping status required.
-- ============================================================================

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
    execute format('drop policy if exists "%1$s_select_public_or_manager" on public.%1$s', t);
    execute format($f$
      create policy "%1$s_select_public_or_manager" on public.%1$s
        for select using (
          status = 'published'
          or (status = 'scheduled' and scheduled_at is not null and scheduled_at <= now())
          or public.has_permission(auth.uid(), 'manage_articles')
        )
    $f$, t);
  end loop;
end $$;
