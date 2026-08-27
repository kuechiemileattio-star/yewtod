import React, { useEffect } from "react";
import { X } from "lucide-react";
import { CONTENT_FIELDS, DEFAULT_MEDIA } from "../data.js";
import Cover from "./Cover.jsx";
import StatusPill from "./StatusPill.jsx";
import MediaDisplay from "./MediaDisplay.jsx";

export default function PublicationPreviewModal({ post, onClose }) {
  useEffect(() => {
    const closeOnEscape = event => event.key === "Escape" && onClose();
    document.addEventListener("keydown", closeOnEscape);
    return () => document.removeEventListener("keydown", closeOnEscape);
  }, [onClose]);

  if (!post) return null;
  const fields = (CONTENT_FIELDS[post.category] || []).filter(([key]) => post[key]);
  const image = post.coverImage || DEFAULT_MEDIA[post.category]?.image;
  const video = post.mediaVideoUrl || post.trailer || post.video;

  return (
    <div className="ytd-admin-modal-backdrop" onMouseDown={event => event.target === event.currentTarget && onClose()}>
      <article className="ytd-publication-preview-modal" role="dialog" aria-modal="true" aria-labelledby="publication-preview-title">
        <div className="ytd-publication-preview-head">
          <div><span className="ytd-admin-kicker">Aperçu de la publication</span><h2 id="publication-preview-title">{post.title}</h2></div>
          <button type="button" onClick={onClose} aria-label="Fermer"><X size={18} /></button>
        </div>
        <div className="ytd-publication-preview-cover"><Cover tone={post.tone} label={post.category} title={post.title} image={image} tall /></div>
        <div className="ytd-publication-preview-meta"><span>{post.author || "Yewtod"}</span><span>{post.date}</span><span>{post.readTime || "Temps non renseigné"}</span><StatusPill statut={post.statut} /></div>
        <p className="ytd-publication-preview-excerpt">{post.excerpt || post.summary || "Aucun résumé renseigné."}</p>
        <div className="ytd-publication-preview-fields">
          {fields.map(([key, label]) => <section key={key}><h3>{label}</h3><p>{post[key]}</p></section>)}
        </div>
        {video && <div className="ytd-publication-preview-video"><MediaDisplay type="video" url={video} alt={post.title} /></div>}
      </article>
    </div>
  );
}
