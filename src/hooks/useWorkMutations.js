import { useState } from "react";
import { supabase } from "../lib/supabaseClient.js";
import { uiToRow } from "../lib/adapters.js";

/** Create/update/delete a row in one of the 8 content tables. */
export function useWorkMutations() {
  const [saving, setSaving] = useState(false);

  async function createWork(table, uiFields) {
    setSaving(true);
    try {
      const row = uiToRow(uiFields);
      const { error } = await supabase.from(table).insert(row);
      if (error) throw error;
    } finally {
      setSaving(false);
    }
  }

  async function updateWork(table, id, uiFields) {
    setSaving(true);
    try {
      const row = uiToRow(uiFields);
      delete row.id;
      delete row.table;
      delete row.category;
      delete row.route_slug;
      const { error } = await supabase.from(table).update(row).eq("id", id);
      if (error) throw error;
    } finally {
      setSaving(false);
    }
  }

  async function deleteWork(table, id) {
    const { error } = await supabase.from(table).delete().eq("id", id);
    if (error) throw error;
  }

  return { createWork, updateWork, deleteWork, saving };
}
