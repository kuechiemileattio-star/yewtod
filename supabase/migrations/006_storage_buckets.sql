-- ============================================================================
-- 006_storage_buckets.sql
-- Storage buckets + policies. All buckets are public for read (covers,
-- avatars, media and PDFs are all meant to be linkable from public pages);
-- writes are gated behind the matching permission.
-- ============================================================================

insert into storage.buckets (id, name, public)
values
  ('covers', 'covers', true),
  ('documents', 'documents', true),
  ('media-library', 'media-library', true),
  ('avatars', 'avatars', true)
on conflict (id) do nothing;

-- Public read on all four buckets
create policy "storage_public_read" on storage.objects
  for select using (bucket_id in ('covers', 'documents', 'media-library', 'avatars'));

-- covers / documents / media-library: writable by manage_articles, manage_books or manage_media
create policy "storage_content_write" on storage.objects
  for insert with check (
    bucket_id in ('covers', 'documents', 'media-library')
    and (
      public.has_permission(auth.uid(), 'manage_articles')
      or public.has_permission(auth.uid(), 'manage_books')
      or public.has_permission(auth.uid(), 'manage_media')
    )
  );

create policy "storage_content_update" on storage.objects
  for update using (
    bucket_id in ('covers', 'documents', 'media-library')
    and (
      public.has_permission(auth.uid(), 'manage_articles')
      or public.has_permission(auth.uid(), 'manage_books')
      or public.has_permission(auth.uid(), 'manage_media')
    )
  );

create policy "storage_content_delete" on storage.objects
  for delete using (
    bucket_id in ('covers', 'documents', 'media-library')
    and (
      public.has_permission(auth.uid(), 'manage_articles')
      or public.has_permission(auth.uid(), 'manage_books')
      or public.has_permission(auth.uid(), 'manage_media')
    )
  );

-- avatars: any authenticated user can manage their own avatar file
-- (object path convention: avatars/{user_id}/...)
create policy "storage_avatars_write_own" on storage.objects
  for insert with check (
    bucket_id = 'avatars' and (storage.foldername(name))[1] = auth.uid()::text
  );

create policy "storage_avatars_update_own" on storage.objects
  for update using (
    bucket_id = 'avatars' and (storage.foldername(name))[1] = auth.uid()::text
  );

create policy "storage_avatars_delete_own" on storage.objects
  for delete using (
    bucket_id = 'avatars' and (storage.foldername(name))[1] = auth.uid()::text
  );
