import React from "react";
import { ArrowLeft } from "lucide-react";
import { T } from "../theme.js";
import { WORKS, fmtDate } from "../data.js";
import Divider from "../components/Divider.jsx";
import Reveal from "../components/Reveal.jsx";
import Cover from "../components/Cover.jsx";
import Tag from "../components/Tag.jsx";
import SectionLabel from "../components/SectionLabel.jsx";

export default function WorkDetail({ work, back, openWork }) {
  const related = WORKS.filter(w => w.id !== work.id && w.category === work.category).slice(0, 3);
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
      <Cover tone={work.tone} label={work.category} tall />
      <div style={{ fontFamily: "'Newsreader', serif", fontSize: 19, lineHeight: 1.75, color: T.ink, marginTop: 36 }}>
        <p style={{ fontWeight: 500, fontSize: 21 }}>{work.excerpt}</p>
        <p>Ce contenu est un exemple de mise en page pour une publication complète : le corps de l'article s'affiche ici en grande lisibilité, avec une colonne resserrée pour un confort de lecture proche de celui de Gates Notes. Chaque publication peut accueillir des médias intégrés (images, vidéos, graphiques), des encadrés de données, et des citations mises en valeur.</p>
        <p>La structure type d'une fiche de contenu comprend un titre, une image de couverture, un résumé, une catégorie, une date, un auteur, un temps de lecture et des tags — exactement les métadonnées définies dans le cahier des charges — ainsi qu'un corps de texte enrichi, des références bibliographiques et une sélection de publications associées, affichée ci-dessous.</p>
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
