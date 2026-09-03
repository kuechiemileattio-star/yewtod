import React, { useState } from "react";
import { createPortal } from "react-dom";
import { useNavigate } from "react-router-dom";
import { Plus, Edit3, Trash2, ExternalLink } from "lucide-react";
import { T } from "../../theme.js";
import { CATEGORIES, CONTENT_TYPES, getTypeByTable, fmtDate } from "../../lib/contentTypes.js";
import { workPath } from "../../lib/paths.js";
import { useAdminWorks } from "../../hooks/useAdminWorks.js";
import { useWorkMutations } from "../../hooks/useWorkMutations.js";
import Cover from "../../components/Cover.jsx";
import StatusPill from "../../components/StatusPill.jsx";
import Btn from "../../components/Btn.jsx";
import WorkEditor from "../../components/dashboard/WorkEditor.jsx";

const STATUS_LABELS = { draft: "Brouillon", published: "Publié", scheduled: "Programmé" };

export default function PublicationsPanel() {
  const navigate = useNavigate();
  const { works, loading, reload } = useAdminWorks();
  const { deleteWork } = useWorkMutations();
  const [category, setCategory] = useState("Toutes");
  const [status, setStatus] = useState("Tous");
  const [pickingType, setPickingType] = useState(false);
  const [editingWork, setEditingWork] = useState(null);
  const [editingType, setEditingType] = useState(null);

  const filtered = works.filter(w =>
    (category === "Toutes" || w.category === category) &&
    (status === "Tous" || STATUS_LABELS[w.status] === status)
  );

  function startCreate(type) {
    setEditingType(type);
    setEditingWork(null);
    setPickingType(false);
  }

  function startEdit(work) {
    setEditingType(getTypeByTable(work.table));
    setEditingWork(work);
  }

  async function handleDelete(work) {
    if (!window.confirm(`Supprimer définitivement "${work.title}" ?`)) return;
    await deleteWork(work.table, work.id);
    await reload();
  }

  return (
    <div className="ytd-admin-view">
      <div className="ytd-admin-section-heading">
        <div><span className="ytd-admin-kicker">Toutes catégories</span><h1>Publications</h1><p>Créer et gérer articles, rapports, études, notes de recherche, séries et épisodes documentaires, expérimentations et visualisations de données.</p></div>
        <Btn variant="green" onClick={() => setPickingType(true)}><Plus size={15} /> Nouvelle publication</Btn>
      </div>

      <div className="ytd-admin-book-toolbar">
        <div className="ytd-admin-filter-scroll">{["Toutes", ...CATEGORIES].map(c => <button key={c} onClick={() => setCategory(c)} className={category === c ? "is-active" : ""}>{c}</button>)}</div>
        <div className="ytd-admin-filter-scroll">{["Tous", "Publié", "Brouillon", "Programmé"].map(s => <button key={s} onClick={() => setStatus(s)} className={status === s ? "is-active" : ""}>{s}</button>)}</div>
      </div>

      {loading ? <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</p> : (
        <div className="ytd-admin-publication-grid">
          {filtered.map((w, index) => (
            <article className="ytd-admin-publication-card" key={w.id} style={{ animationDelay: `${index * 40}ms` }}>
              <button type="button" className="ytd-admin-publication-cover" onClick={() => startEdit(w)} aria-label={`Modifier ${w.title}`}>
                <Cover tone={T.green} label={w.category} title={w.title} image={w.coverImage} tall />
              </button>
              <div className="ytd-admin-publication-card-meta">
                <span>{fmtDate(w.publishedAt || w.createdAt)}</span>
                <StatusPill statut={STATUS_LABELS[w.status]} />
              </div>
              <div className="ytd-admin-publication-card-actions">
                <button type="button" onClick={() => startEdit(w)}><Edit3 size={13} style={{ marginRight: 5, verticalAlign: "-2px" }} />Modifier</button>
                {w.status === "published" && <button type="button" onClick={() => navigate(workPath(w.routeSlug, w.slug))} aria-label="Voir la page publique"><ExternalLink size={14} /></button>}
                <button type="button" onClick={() => handleDelete(w)} aria-label="Supprimer"><Trash2 size={14} /></button>
              </div>
            </article>
          ))}
        </div>
      )}
      {!loading && filtered.length === 0 && <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Aucune publication ne correspond à ces filtres.</p>}

      {pickingType && createPortal(
        <div className="ytd-admin-modal-backdrop" onMouseDown={e => e.target === e.currentTarget && setPickingType(false)}>
          <div className="ytd-admin-panel ytd-admin-content-modal" style={{ maxWidth: 480 }}>
            <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 22, margin: "0 0 6px" }}>Quel type de publication ?</h2>
            <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13, margin: "0 0 18px" }}>Le formulaire s'adapte ensuite aux champs propres à ce type.</p>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
              {CONTENT_TYPES.map(type => (
                <button key={type.table} onClick={() => startCreate(type)} style={{ padding: "14px 12px", border: `1px solid ${T.line}`, background: T.paper, color: T.ink, cursor: "pointer", textAlign: "left", fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 13.5, borderRadius: 6, transition: "transform .2s ease, border-color .2s ease" }} onMouseEnter={e => { e.currentTarget.style.borderColor = T.green; e.currentTarget.style.transform = "translateY(-2px)"; }} onMouseLeave={e => { e.currentTarget.style.borderColor = T.line; e.currentTarget.style.transform = "none"; }}>
                  {type.label}
                </button>
              ))}
            </div>
            <Btn variant="outline" onClick={() => setPickingType(false)} style={{ marginTop: 18 }}>Annuler</Btn>
          </div>
        </div>,
        document.body
      )}

      {editingType && (
        <WorkEditor
          type={editingType}
          work={editingWork}
          onClose={() => { setEditingType(null); setEditingWork(null); }}
          onSaved={async () => { setEditingType(null); setEditingWork(null); await reload(); }}
        />
      )}
    </div>
  );
}
