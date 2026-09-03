import React from "react";
import { useNavigate, useParams, Link } from "react-router-dom";
import { ArrowLeft } from "lucide-react";
import { T } from "../theme.js";
import { fmtDate, getTypeByRouteSlug } from "../lib/contentTypes.js";
import { workPath, PATHS } from "../lib/paths.js";
import { useWork, useWorks } from "../hooks/useWorks.js";
import useDocumentMeta from "../hooks/useDocumentMeta.js";
import Divider from "../components/Divider.jsx";
import Reveal from "../components/Reveal.jsx";
import Cover from "../components/Cover.jsx";
import Tag from "../components/Tag.jsx";
import SectionLabel from "../components/SectionLabel.jsx";
import Breadcrumb from "../components/Breadcrumb.jsx";
import { WORK_DETAIL_COMPONENTS } from "../components/work-details/index.js";

export default function WorkDetail() {
  const navigate = useNavigate();
  const { typeSlug, slug } = useParams();
  const { work, loading } = useWork(typeSlug, slug);
  const { works } = useWorks();
  const type = getTypeByRouteSlug(typeSlug);

  useDocumentMeta(work?.title, work?.excerpt);

  if (loading) return <div style={{ maxWidth: 1000, margin: "0 auto", padding: "120px 24px", color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</div>;

  if (!work || !type) {
    return (
      <div style={{ maxWidth: 1000, margin: "0 auto", padding: "120px 24px 100px" }}>
        <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft, marginBottom: 20 }}>Cette publication est introuvable ou n'est plus disponible.</p>
        <Link to={PATHS.works} style={{ color: T.green, fontFamily: "'IBM Plex Mono', monospace", fontSize: 13 }}>← Retour aux Works</Link>
      </div>
    );
  }

  const related = works.filter(w => w.id !== work.id && w.category === work.category).slice(0, 3);
  const BodyComponent = WORK_DETAIL_COMPONENTS[work.table];

  return (
    <div className="ytd-work-detail-page" style={{ maxWidth: 1000, margin: "0 auto", padding: "56px 24px 110px" }}>
      <button onClick={() => navigate(-1)} style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 16 }}>
        <ArrowLeft size={15} /> Retour
      </button>
      <Breadcrumb items={[{ label: "Home", to: PATHS.home }, { label: "Works", to: PATHS.works }, { label: work.category, to: PATHS.works }, { label: work.title }]} />
      <div className="ytd-work-detail-hero">
        <div>
          <Tag>{work.category}</Tag>
          <h1>{work.title}</h1>
          <div className="ytd-work-detail-meta">
            <span>{work.author}</span><span>·</span><span>{fmtDate(work.date)}</span>{work.readTime && <><span>·</span><span>{work.readTime}</span></>}
          </div>
        </div>
      </div>
      {work.coverImage && <div className="ytd-work-detail-cover"><Cover tone={work.tone} label={work.category} tall image={work.coverImage} /></div>}
      <div className="ytd-work-detail-body" style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
        {BodyComponent ? <BodyComponent work={work} /> : <p style={{ color: T.inkSoft }}>Type de contenu non pris en charge.</p>}
      </div>

      <Divider margin="68px 0 40px" />
      <SectionLabel>Publications associées</SectionLabel>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 24 }} className="ytd-grid-3">
        {related.map((w, i) => (
          <Reveal key={w.id} delay={i * 70}>
            <div onClick={() => navigate(workPath(w.routeSlug, w.slug))} className="ytd-card" style={{ cursor: "pointer" }}>
              <Cover tone={w.tone} label={w.category} image={w.coverImage} />
              <h4 style={{ fontFamily: "'Newsreader', serif", fontSize: 15.5, fontWeight: 600, margin: "10px 0 0", lineHeight: 1.3 }}>{w.title}</h4>
            </div>
          </Reveal>
        ))}
        {related.length === 0 && <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft }}>Aucune autre publication dans cette catégorie pour l'instant.</p>}
      </div>
    </div>
  );
}
