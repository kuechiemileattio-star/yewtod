import React from "react";
import { ArrowLeft } from "lucide-react";
import { T } from "../theme.js";
import { WORKS, CONTENT_FIELDS, fmtDate } from "../data.js";
import Divider from "../components/Divider.jsx";
import Reveal from "../components/Reveal.jsx";
import Cover from "../components/Cover.jsx";
import Tag from "../components/Tag.jsx";
import SectionLabel from "../components/SectionLabel.jsx";

export default function WorkDetail({ work, back, openWork }) {
  if (!work) return null;
  const related = WORKS.filter(w => w.id !== work.id && w.category === work.category).slice(0, 3);
  const structuredFields = (CONTENT_FIELDS[work.category] || []).filter(([key]) => work[key]);
  return (
    <div style={{ maxWidth: 760, margin: "0 auto", padding: "56px 24px 110px" }}>
      <button onClick={back} style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 36 }}>
        <ArrowLeft size={15} /> Retour aux Works
      </button>
      <Tag>{work.category}</Tag>
      <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(30px, 4.5vw, 44px)", fontWeight: 500, lineHeight: 1.12, margin: "16px 0 18px" }}>{work.title}</h1>
      <div style={{ display: "flex", gap: 16, alignItems: "center", fontFamily: "'IBM Plex Mono', monospace", fontSize: 12.5, color: T.inkSoft, marginBottom: 32 }}>
        <span>{work.author}</span><span>·</span><span>{fmtDate(work.date)}</span><span>·</span><span>{work.readTime}</span>
      </div>
      <Cover tone={work.tone} label={work.category} tall image={work.coverImage} />
      <div style={{ fontFamily: "'Newsreader', serif", fontSize: 19, lineHeight: 1.75, color: T.ink, marginTop: 36 }}>
        <p style={{ fontWeight: 500, fontSize: 21 }}>{work.subtitle || work.excerpt}</p>
        {structuredFields.map(([key, label]) => (
          <section key={key} style={{ marginTop: 34 }}>
            <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 25, fontWeight: 500, lineHeight: 1.15, margin: "0 0 10px" }}>{label}</h2>
            <p style={{ whiteSpace: "pre-line", margin: 0 }}>{work[key]}</p>
          </section>
        ))}
        {!structuredFields.length && <><p>Ce contenu est un exemple de mise en page pour une publication complète : le corps de l'article s'affiche ici en grande lisibilité, avec une colonne resserrée pour un confort de lecture proche de celui d'une publication éditoriale longue.</p><p>La structure peut accueillir des médias intégrés, des encadrés de données, des citations mises en valeur, des références bibliographiques et des publications associées.</p></>}
      </div>

      <Divider margin="56px 0 40px" />
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
