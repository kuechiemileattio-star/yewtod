import React, { useCallback, useEffect, useState } from "react";
import { Check, X, Trash2, ExternalLink, AlertCircle, Loader2, MessageSquare, BookOpen } from "lucide-react";
import { T } from "../../theme.js";
import { supabase } from "../../lib/supabaseClient.js";
import { fmtDate, getTypeByTable } from "../../lib/contentTypes.js";
import { workPath, bookPath } from "../../lib/paths.js";

/**
 * File de modération des avis (pré-modération, migration 023).
 * La fonction get_moderation_queue ne renvoie que ce que l'appelant a le droit
 * de modérer : avis d'articles (manage_articles) et/ou avis de livres
 * (manage_books). Les actions passent par la RLS des tables.
 */
const TABLE_OF = { comment: "content_comments", review: "book_reviews" };

function publicLink(item) {
  if (!item.content_slug) return null;
  if (item.table_name === "books") return bookPath(item.content_slug);
  const type = getTypeByTable(item.table_name);
  return type ? workPath(type.routeSlug, item.content_slug) : null;
}

export default function ModerationPanel() {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState(null);
  const [error, setError] = useState("");

  const reload = useCallback(async () => {
    setLoading(true);
    const { data, error: err } = await supabase.rpc("get_moderation_queue");
    if (err) setError(err.message || "Impossible de charger la file de modération.");
    else setItems(data || []);
    setLoading(false);
  }, []);

  useEffect(() => { reload(); }, [reload]);

  async function act(item, action) {
    if (action === "delete" && !window.confirm("Supprimer définitivement cet avis ?")) return;
    setError("");
    setBusyId(item.id);
    try {
      const table = supabase.from(TABLE_OF[item.kind]);
      const { error: err } = action === "delete"
        ? await table.delete().eq("id", item.id)
        : await table.update({ status: action }).eq("id", item.id);
      if (err) throw err;
      setItems(prev => prev.filter(i => i.id !== item.id));
    } catch (err) {
      setError(err.message || "Action refusée.");
    } finally {
      setBusyId(null);
    }
  }

  return (
    <div className="ytd-dashboard-new ytd-admin-view">
      <div className="ytd-admin-section-heading">
        <div>
          <span className="ytd-admin-kicker">Contenu</span>
          <h1>Modération</h1>
          <p>Avis laissés par les visiteurs, en attente de relecture. Rien n'est visible sur le site avant d'être approuvé.</p>
        </div>
      </div>

      <section className="ytd-admin-settings-card">
        <div className="ytd-admin-settings-card-heading">
          <div><span>File d'attente</span><h2>Avis à relire</h2></div>
          <span className="ytd-admin-settings-index">{items.length}</span>
        </div>

        {error && <p className="ytd-form-message" role="alert" style={{ color: T.red, fontSize: 12.5, margin: "0 0 12px" }}><AlertCircle size={14} /> {error}</p>}
        {loading && <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>Chargement…</p>}
        {!loading && items.length === 0 && !error && (
          <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>Aucun avis en attente. 🎉</p>
        )}

        <div style={{ display: "grid", gap: 10 }}>
          {items.map(item => {
            const link = publicLink(item);
            const busy = busyId === item.id;
            const Icon = item.kind === "review" ? BookOpen : MessageSquare;
            return (
              <article key={item.id} style={{ border: `1px solid ${T.line}`, borderRadius: 8, padding: "14px 16px", background: T.paper }}>
                <div style={{ display: "flex", alignItems: "center", gap: 8, flexWrap: "wrap", fontFamily: "'Inter', sans-serif", fontSize: 12, color: T.inkSoft }}>
                  <Icon size={13} color={T.green} />
                  <span>{item.kind === "review" ? "Avis sur le livre" : "Avis sur"}</span>
                  {link
                    ? <a href={link} target="_blank" rel="noreferrer" style={{ color: T.ink, fontWeight: 600, display: "inline-flex", alignItems: "center", gap: 4 }}>{item.content_title || "(sans titre)"} <ExternalLink size={11} /></a>
                    : <strong style={{ color: T.ink }}>{item.content_title || "(contenu introuvable)"}</strong>}
                  <span>· {fmtDate(item.created_at)}</span>
                </div>
                <p style={{ margin: "10px 0 4px", fontFamily: "'Inter', sans-serif", fontSize: 13, fontWeight: 600, color: T.ink }}>{item.author_name}</p>
                {/* Texte rendu par React (échappé) : aucun HTML du visiteur n'est interprété. */}
                <p style={{ margin: 0, fontFamily: "'Inter', sans-serif", fontSize: 13.5, lineHeight: 1.55, color: T.ink, whiteSpace: "pre-wrap", overflowWrap: "anywhere" }}>{item.text}</p>
                <div style={{ display: "flex", gap: 8, marginTop: 12, flexWrap: "wrap" }}>
                  <button type="button" disabled={busy} onClick={() => act(item, "approved")} style={btn(T.green, T.paper)}>
                    {busy ? <Loader2 size={13} className="ytd-spin" /> : <Check size={13} />} Approuver
                  </button>
                  <button type="button" disabled={busy} onClick={() => act(item, "rejected")} style={btn(T.paperAlt, T.ink)}>
                    <X size={13} /> Rejeter
                  </button>
                  <button type="button" disabled={busy} onClick={() => act(item, "delete")} style={btn("transparent", T.red)} aria-label="Supprimer définitivement">
                    <Trash2 size={13} /> Supprimer
                  </button>
                </div>
              </article>
            );
          })}
        </div>
      </section>
    </div>
  );
}

function btn(background, color) {
  return {
    display: "inline-flex", alignItems: "center", gap: 6, padding: "7px 12px", borderRadius: 6,
    border: `1px solid ${background === "transparent" ? T.line : background}`, background, color,
    fontFamily: "'Inter', sans-serif", fontSize: 12.5, cursor: "pointer",
  };
}
