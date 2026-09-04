import React from "react";
import { createPortal } from "react-dom";
import { X, Edit3, ExternalLink } from "lucide-react";
import { T } from "../../theme.js";
import { fmtDate } from "../../lib/contentTypes.js";
import { workPath } from "../../lib/paths.js";
import { WORK_DETAIL_COMPONENTS } from "../work-details/index.js";
import Cover from "../Cover.jsx";
import StatusPill from "../StatusPill.jsx";
import Btn from "../Btn.jsx";

const STATUS_LABELS = { draft: "Brouillon", published: "Publié", scheduled: "Programmé" };

export default function WorkPreviewModal({ work, onEdit, onClose }) {
  const BodyComponent = WORK_DETAIL_COMPONENTS[work.table];

  return createPortal(
    <div className="ytd-admin-modal-backdrop" onMouseDown={e => e.target === e.currentTarget && onClose()}>
      <article className="ytd-publication-preview-modal" role="dialog" aria-modal="true" aria-labelledby="publication-preview-title">
        <div className="ytd-publication-preview-head">
          <div><span className="ytd-admin-kicker">{work.category}</span><h2 id="publication-preview-title">{work.title}</h2></div>
          <button type="button" onClick={onClose} aria-label="Fermer"><X size={18} /></button>
        </div>
        <div className="ytd-publication-preview-cover"><Cover tone={T.green} label={work.category} image={work.coverImage} tall /></div>
        <div className="ytd-publication-preview-meta">
          <StatusPill statut={STATUS_LABELS[work.status]} />
          <span>{fmtDate(work.publishedAt || work.scheduledAt || work.createdAt)}</span>
        </div>
        <div style={{ marginTop: 10 }}>
          {BodyComponent ? <BodyComponent work={work} /> : <p style={{ color: T.inkSoft }}>Aperçu non disponible pour ce type.</p>}
        </div>
        <div className="ytd-admin-book-preview-actions" style={{ marginTop: 20 }}>
          <Btn variant="green" onClick={onEdit}><Edit3 size={15} /> Modifier</Btn>
          {work.status === "published" && (
            <a href={workPath(work.routeSlug, work.slug)} target="_blank" rel="noreferrer" style={{ display: "inline-flex", alignItems: "center", gap: 6, color: T.green, fontFamily: "'JetBrains Mono', monospace", fontSize: 12 }}>
              <ExternalLink size={14} /> Voir la page publique
            </a>
          )}
        </div>
      </article>
    </div>,
    document.body
  );
}
