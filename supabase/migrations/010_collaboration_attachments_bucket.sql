-- ============================================================================
-- 010_collaboration_attachments_bucket.sql
-- The public collaboration form lets visitors attach a file (the
-- `attachments` column on `collaborations` already existed but had no
-- storage bucket to upload into — the form field was a disabled placeholder).
-- Anonymous visitors can upload (insert) and read; only managers can delete.
-- ============================================================================

insert into storage.buckets (id, name, public)
values ('collaboration-attachments', 'collaboration-attachments', true)
on conflict (id) do nothing;

create policy "storage_collab_attachments_public_read" on storage.objects
  for select using (bucket_id = 'collaboration-attachments');

create policy "storage_collab_attachments_public_insert" on storage.objects
  for insert with check (bucket_id = 'collaboration-attachments');

create policy "storage_collab_attachments_manager_delete" on storage.objects
  for delete using (
    bucket_id = 'collaboration-attachments'
    and public.has_permission(auth.uid(), 'manage_collaborations')
  );
