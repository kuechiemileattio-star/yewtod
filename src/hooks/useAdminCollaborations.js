import { useCallback, useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

function normalize(row) {
  return {
    id: row.id,
    nom: row.name,
    org: row.organization,
    email: row.email,
    sujet: row.subject,
    type: row.type,
    description: row.description,
    statut: { nouveau: "Nouveau", en_cours: "En cours", archive: "Archivé" }[row.status] || row.status,
    statusKey: row.status,
    date: row.created_at,
  };
}

const STATUS_TO_KEY = { Nouveau: "nouveau", "En cours": "en_cours", Archivé: "archive" };

export function useAdminCollaborations() {
  const [collabs, setCollabs] = useState([]);
  const [loading, setLoading] = useState(true);

  const reload = useCallback(async () => {
    setLoading(true);
    const { data, error } = await supabase.from("collaborations").select("*").order("created_at", { ascending: false });
    if (!error) setCollabs((data || []).map(normalize));
    setLoading(false);
  }, []);

  useEffect(() => { reload(); }, [reload]);

  async function updateStatus(id, statutLabel) {
    const status = STATUS_TO_KEY[statutLabel] || statutLabel;
    const { error } = await supabase.from("collaborations").update({ status }).eq("id", id);
    if (error) throw error;
    await reload();
  }

  return { collabs, loading, updateStatus, reload };
}
