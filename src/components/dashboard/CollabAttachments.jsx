import React, { useEffect, useState } from "react";
import { Paperclip, AlertCircle, Loader2 } from "lucide-react";
import { T } from "../../theme.js";
import { supabase } from "../../lib/supabaseClient.js";
import { COLLAB_BUCKET, collabAttachmentPath } from "../../lib/attachments.js";

const SIGNED_URL_TTL = 600; // secondes : le lien expire 10 minutes après l'ouverture de la fiche

const chip = {
  display: "inline-flex", alignItems: "center", gap: 6, marginRight: 10, marginBottom: 6, padding: "8px 12px",
  border: `1px solid ${T.line}`, borderRadius: 6, background: T.paperAlt, color: T.ink, textDecoration: "none",
  fontFamily: "'Inter', sans-serif", fontSize: 13,
};

/**
 * Pièces jointes d'une demande de collaboration (bucket privé).
 * Chaque pièce jointe valide reçoit une URL SIGNÉE temporaire, générée par
 * l'API Storage — ce qui exige la permission manage_collaborations (RLS).
 * Une valeur invalide (lien externe, "javascript:…") n'est jamais rendue en lien.
 */
export default function CollabAttachments({ attachments }) {
  const [signed, setSigned] = useState({}); // valeur d'origine → URL signée
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const items = (attachments || []).map(value => ({
    value,
    path: collabAttachmentPath(value, import.meta.env.VITE_SUPABASE_URL),
  }));
  const key = items.map(i => i.value).join("|");

  useEffect(() => {
    const valid = items.filter(i => i.path);
    setSigned({});
    setError("");
    if (valid.length === 0) return;
    let active = true;
    setLoading(true);
    supabase.storage
      .from(COLLAB_BUCKET)
      .createSignedUrls(valid.map(i => i.path), SIGNED_URL_TTL)
      .then(({ data, error: err }) => {
        if (!active) return;
        if (err) { setError(err.message || "Impossible de générer les liens."); return; }
        const map = {};
        (data || []).forEach((d, idx) => { if (d?.signedUrl) map[valid[idx].value] = d.signedUrl; });
        setSigned(map);
      })
      .finally(() => active && setLoading(false));
    return () => { active = false; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key]);

  if (items.length === 0) return null;

  return (
    <div style={{ marginBottom: 18 }}>
      {items.map((item, idx) => {
        const url = signed[item.value];
        if (url) {
          return (
            <a key={item.value} href={url} target="_blank" rel="noreferrer noopener" style={chip}>
              <Paperclip size={13} color={T.green} /> Pièce jointe {items.length > 1 ? idx + 1 : ""}
            </a>
          );
        }
        if (item.path && loading) {
          return <span key={item.value} style={chip}><Loader2 size={13} className="ytd-spin" /> Préparation du lien…</span>;
        }
        return (
          <span key={item.value} style={{ ...chip, color: T.inkSoft }} title={item.value}>
            <AlertCircle size={13} color={T.red} /> {item.path ? "Pièce jointe introuvable" : "Pièce jointe invalide (ignorée)"}
          </span>
        );
      })}
      {error && <p style={{ color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 12.5, margin: "6px 0 0" }}>{error}</p>}
    </div>
  );
}
