import React, { useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { Plus, Edit3, Trash2, ExternalLink } from "lucide-react";
import { T } from "../../theme.js";
import { fmtDate } from "../../lib/contentTypes.js";
import { workPath } from "../../lib/paths.js";
import { SIMPLE_WORK_TYPES } from "../../lib/simpleWorkTypes.js";
import { useAdminWorks } from "../../hooks/useAdminWorks.js";
import { useWorkMutations } from "../../hooks/useWorkMutations.js";
import Cover from "../../components/Cover.jsx";
import StatusPill from "../../components/StatusPill.jsx";
import Btn from "../../components/Btn.jsx";
import WorkPreviewModal from "../../components/dashboard/WorkPreviewModal.jsx";

const STATUS_LABELS = { draft: "Brouillon", published: "Publié", scheduled: "Programmé" };

export default function SimpleTypePanel({ tableKey }) {
  const config = SIMPLE_WORK_TYPES[tableKey];
  const navigate = useNavigate();
  const location = useLocation();
  const { works, loading, reload } = useAdminWorks();
  const { deleteWork } = useWorkMutations();
  const [status, setStatus] = useState("Tous");
  const [previewingWork, setPreviewingWork] = useState(null);
  const [warning, setWarning] = useState(location.state?.warning || "");

  const items = works.filter(w => w.table === tableKey && (status === "Tous" || STATUS_LABELS[w.status] === status));

  async function handleDelete(work) {
    if (!window.confirm(`Supprimer définitivement "${work.title}" ?`)) return;
    await deleteWork(work.table, work.id);
    await reload();
  }

  return (
    <div className="ytd-admin-view">
      <div className="ytd-admin-section-heading">
        <div><span className="ytd-admin-kicker">Contenu</span><h1>{config.plural}</h1><p>Créer et gérer les {config.plural.toLowerCase()} publiés sur le site.</p></div>
        <Btn variant="green" onClick={() => navigate(`/dashboard/${config.adminPath}/nouveau`)}><Plus size={15} /> Nouveau {config.singular.toLowerCase()}</Btn>
      </div>

      {warning && (
        <p style={{ margin: "0 0 18px", padding: "12px 16px", border: `1px solid ${T.red}55`, background: `${T.red}14`, color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 13, lineHeight: 1.5 }}>
          {warning} <button type="button" onClick={() => setWarning("")} style={{ marginLeft: 10, border: 0, background: "none", color: T.red, textDecoration: "underline", cursor: "pointer", font: "inherit" }}>Fermer</button>
        </p>
      )}

      <div className="ytd-admin-book-toolbar">
        <div className="ytd-admin-filter-scroll">{["Tous", "Publié", "Brouillon", "Programmé"].map(s => <button key={s} onClick={() => setStatus(s)} className={status === s ? "is-active" : ""}>{s}</button>)}</div>
      </div>

      {loading ? <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</p> : (
        <div className="ytd-admin-publication-grid">
          {items.map((w, index) => (
            <article className="ytd-admin-publication-card" key={w.id} style={{ animationDelay: `${index * 40}ms` }}>
              <button type="button" className="ytd-admin-publication-cover" onClick={() => setPreviewingWork(w)} aria-label={`Aperçu de ${w.title}`}>
                <Cover tone={T.green} label={w.category} title={w.title} image={w.coverImage} tall />
              </button>
              <div className="ytd-admin-publication-card-meta">
                <span>{fmtDate(w.publishedAt || w.createdAt)}</span>
                <StatusPill statut={STATUS_LABELS[w.status]} />
              </div>
              <div className="ytd-admin-publication-card-actions">
                <button type="button" onClick={() => navigate(`/dashboard/${config.adminPath}/${w.id}`)}><Edit3 size={13} style={{ marginRight: 5, verticalAlign: "-2px" }} />Modifier</button>
                {w.status === "published" && <button type="button" onClick={() => navigate(workPath(w.routeSlug, w.slug))} aria-label="Voir la page publique"><ExternalLink size={14} /></button>}
                <button type="button" onClick={() => handleDelete(w)} aria-label="Supprimer"><Trash2 size={14} /></button>
              </div>
            </article>
          ))}
        </div>
      )}
      {!loading && items.length === 0 && <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Aucun contenu ne correspond à ces filtres.</p>}

      {previewingWork && (
        <WorkPreviewModal
          work={previewingWork}
          onEdit={() => { navigate(`/dashboard/${config.adminPath}/${previewingWork.id}`); setPreviewingWork(null); }}
          onClose={() => setPreviewingWork(null)}
        />
      )}
    </div>
  );
}
