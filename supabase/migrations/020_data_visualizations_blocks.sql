-- ============================================================================
-- 020_data_visualizations_blocks.sql
-- A single "Visualisations de données" entry can now hold several individual
-- visualizations (title + description + its own CSV file + chart type each),
-- added one at a time from the admin form — same "Ajouter" logic as the
-- article/report sous-titre builder (see tableOfContents on articles/reports).
-- The existing single csv_file/visualization_type columns are kept for
-- backward compatibility with entries created before this migration.
-- ============================================================================

alter table public.data_visualizations
  add column if not exists blocks jsonb not null default '[]';
