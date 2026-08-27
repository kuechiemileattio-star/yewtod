import React from "react";
import { ArrowLeft } from "lucide-react";
import { T } from "../theme.js";
import { WORKS, CONTENT_FIELDS, DEFAULT_MEDIA, fmtDate } from "../data.js";
import Divider from "../components/Divider.jsx";
import Reveal from "../components/Reveal.jsx";
import Cover from "../components/Cover.jsx";
import Tag from "../components/Tag.jsx";
import SectionLabel from "../components/SectionLabel.jsx";
import MediaDisplay, { extractMediaUrls, isVideoMedia } from "../components/MediaDisplay.jsx";

const IMAGE_FIELDS = new Set(["images", "screenshots", "illustrations", "imageDownload"]);
const VIDEO_FIELDS = new Set(["video", "videos", "embeddedVideos", "trailer"]);

export default function WorkDetail({ work, back, openWork, works = WORKS }) {
  if (!work) return null;
  const related = works.filter(w => w.id !== work.id && w.category === work.category && w.statut !== "Brouillon").slice(0, 3);
  const structuredFields = (CONTENT_FIELDS[work.category] || []).filter(([key]) => work[key]);
  const mediaFields = structuredFields.filter(([key]) => IMAGE_FIELDS.has(key) || VIDEO_FIELDS.has(key));
  const textFields = structuredFields.filter(([key]) => !IMAGE_FIELDS.has(key) && !VIDEO_FIELDS.has(key));
  const associatedMedia = mediaFields.flatMap(([key, label]) => extractMediaUrls(work[key]).map(url => ({ key, label, url, type: VIDEO_FIELDS.has(key) || isVideoMedia(url) ? "video" : "image" })));
  return (
    <div className="ytd-work-detail-page" style={{ maxWidth: 1000, margin: "0 auto", padding: "56px 24px 110px" }}>
      <button onClick={back} style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 36 }}>
        <ArrowLeft size={15} /> Retour aux Works
      </button>
      <div className="ytd-work-detail-hero"><div><Tag>{work.category}</Tag><h1>{work.title}</h1>
      <div className="ytd-work-detail-meta">
        <span>{work.author}</span><span>·</span><span>{fmtDate(work.date)}</span><span>·</span><span>{work.readTime}</span>
      </div></div><div className="ytd-work-detail-index"><span>Publication</span><strong>{String(work.id).replace("w", "").padStart(2, "0")}</strong><small>Yewtod SS</small></div></div>
      <div className="ytd-work-detail-cover"><Cover tone={work.tone} label={work.category} tall image={work.coverImage || DEFAULT_MEDIA[work.category]?.image} /></div>
      <div className="ytd-work-detail-body" style={{ fontFamily: "'Newsreader', serif", fontSize: 19, lineHeight: 1.75, color: T.ink }}>
        <p style={{ fontWeight: 500, fontSize: 21 }}>{work.subtitle || work.excerpt}</p>
        <div className="ytd-work-detail-attributes">
          {textFields.map(([key, label], index) => (
            <section className={`ytd-work-detail-section ytd-detail-attribute-${key}`} key={key} style={{ animationDelay: `${Math.min(index, 6) * 70}ms` }}>
              <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 25, fontWeight: 500, lineHeight: 1.15, margin: "0 0 10px" }}>{label}</h2>
              <p style={{ whiteSpace: "pre-line", margin: 0 }}>{work[key]}</p>
            </section>
          ))}
          {mediaFields.filter(([key]) => extractMediaUrls(work[key]).length === 0).map(([key, label], index) => (
            <section className={`ytd-work-detail-section ytd-detail-attribute-${key}`} key={key} style={{ animationDelay: `${Math.min(index + textFields.length, 6) * 70}ms` }}>
              <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 25, fontWeight: 500, lineHeight: 1.15, margin: "0 0 10px" }}>{label}</h2>
              <p style={{ whiteSpace: "pre-line", margin: 0 }}>{work[key]}</p>
            </section>
          ))}
        </div>
        {mediaFields.some(([key]) => extractMediaUrls(work[key]).length > 0) && <section className="ytd-work-detail-media-section"><h2>Médias associés</h2><div className="ytd-work-detail-media-grid">{associatedMedia.map(({ key, label, url, type }) => <figure key={`${key}-${url}`}><MediaDisplay type={type} url={url} alt={label} /><figcaption>{label}</figcaption></figure>)}</div></section>}
        {!structuredFields.length && <><p>Ce contenu est un exemple de mise en page pour une publication complète : le corps de l'article s'affiche ici en grande lisibilité, avec une colonne resserrée pour un confort de lecture proche de celui d'une publication éditoriale longue.</p><p>La structure peut accueillir des médias intégrés, des encadrés de données, des citations mises en valeur, des références bibliographiques et des publications associées.</p></>}
      </div>

      <Divider margin="68px 0 40px" />
      <SectionLabel>Publications associées</SectionLabel>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 24 }} className="ytd-grid-3">
        {related.map((w, i) => (
          <Reveal key={w.id} delay={i * 70}>
          <div onClick={() => openWork(w)} className="ytd-card" style={{ cursor: "pointer" }}>
            <Cover tone={w.tone} label={w.category} />
            <h4 style={{ fontFamily: "'Newsreader', serif", fontSize: 15.5, fontWeight: 600, margin: "10px 0 0", lineHeight: 1.3 }}>{w.title}</h4>
          </div>
          </Reveal>
        ))}
        {related.length === 0 && <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft }}>Aucune autre publication dans cette catégorie pour l'instant.</p>}
      </div>
    </div>
  );
}
