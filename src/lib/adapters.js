/* ============================================================
   Conversion entre les lignes Supabase (snake_case, text[], jsonb)
   et la forme attendue par les composants front (camelCase,
   listes = chaînes séparées par des retours à la ligne).
============================================================= */

export const ARRAY_FIELDS = new Set([
  "quotes", "images", "embeddedVideos", "references", "tags", "coAuthors",
  "charts", "tables", "appendices", "bibliography", "authors", "diagrams",
  "usefulLinks", "videos", "guests", "additionalResources", "speakers",
  "toolsUsed", "datasets", "screenshots", "visualizations", "downloadableFiles",
  "favoriteQuotes", "illustrations", "relatedDocuments", "attachments",
]);

export const JSON_LIST_FIELDS = new Set(["relatedContent", "relatedArticles", "similarBooks"]);

export function snakeToCamel(str) {
  return str.replace(/_([a-z0-9])/g, (_, c) => c.toUpperCase());
}

export function camelToSnake(str) {
  return str.replace(/[A-Z]/g, c => "_" + c.toLowerCase());
}

/** Supabase row -> shape the existing UI components expect. */
export function rowToUi(row) {
  if (!row) return row;
  const out = {};
  for (const [key, value] of Object.entries(row)) {
    const camelKey = snakeToCamel(key);
    if (value == null) {
      out[camelKey] = ARRAY_FIELDS.has(camelKey) || JSON_LIST_FIELDS.has(camelKey) ? "" : value;
      continue;
    }
    if (ARRAY_FIELDS.has(camelKey) && Array.isArray(value)) {
      out[camelKey] = value.join("\n");
      continue;
    }
    if (JSON_LIST_FIELDS.has(camelKey)) {
      if (Array.isArray(value)) {
        out[camelKey] = value.length ? value.map(v => (typeof v === "string" ? v : v.title || v.id || "")).filter(Boolean).join(", ") : "";
        continue;
      }
    }
    out[camelKey] = value;
  }
  return out;
}

/** UI shape (from a form) -> Supabase row ready for insert/update. */
export function uiToRow(ui) {
  const out = {};
  for (const [key, value] of Object.entries(ui)) {
    const snakeKey = camelToSnake(key);
    if (ARRAY_FIELDS.has(key) && typeof value === "string") {
      out[snakeKey] = value.split("\n").map(s => s.trim()).filter(Boolean);
      continue;
    }
    if (JSON_LIST_FIELDS.has(key) && value === "") {
      // These jsonb columns are NOT NULL DEFAULT '[]' — an explicit null
      // would override the default and violate the constraint.
      out[snakeKey] = [];
      continue;
    }
    // Empty strings are invalid for date/timestamp/numeric columns (e.g. an
    // untouched "Date de publication" field) — Postgres wants null instead.
    out[snakeKey] = value === "" ? null : value;
  }
  return out;
}
