import React, { useState } from "react";
import { T } from "../theme.js";
import { WORKS, CATEGORIES, fmtDate } from "../data.js";
import Reveal from "../components/Reveal.jsx";
import Cover from "../components/Cover.jsx";
import Tag from "../components/Tag.jsx";

export default function Works({ openWork }) {
  const [cat, setCat] = useState("Toutes");
  const filtered = cat === "Toutes" ? WORKS : WORKS.filter(w => w.category === cat);

  return (
    <div style={{ maxWidth: 1120, margin: "0 auto", padding: "72px 24px 100px" }}>
      <Reveal>
        <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(34px, 4.5vw, 48px)", fontWeight: 500, margin: "0 0 12px" }}>Works</h1>
        <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 16, color: T.inkSoft, maxWidth: 600, marginBottom: 40 }}>
          La bibliothèque des travaux publiés : articles, rapports, études, notes de recherche, séries documentaires, expérimentations et visualisations de données.
        </p>
      </Reveal>

      <div style={{ display: "flex", gap: 10, flexWrap: "wrap", marginBottom: 44 }}>
        {["Toutes", ...CATEGORIES].map(c => (
          <button key={c} onClick={() => setCat(c)} className="ytd-pill" style={{
            fontFamily: "'IBM Plex Mono', monospace", fontSize: 12.5, padding: "8px 14px", cursor: "pointer",
            border: `1px solid ${cat === c ? T.ink : T.line}`, background: cat === c ? T.ink : "transparent",
            color: cat === c ? T.paper : T.inkSoft, borderRadius: 20,
          }}>{c}</button>
        ))}
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 32 }} className="ytd-grid-3">
        {filtered.map((w, i) => (
          <Reveal key={w.id} delay={(i % 3) * 80} as="div">
          <div onClick={() => openWork(w)} className="ytd-card" style={{ cursor: "pointer" }}>
            <Cover tone={w.tone} label={w.category} tall />
            <div style={{ marginTop: 16 }}>
              <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: T.inkSoft }}>{fmtDate(w.date)} · {w.readTime}</span>
              <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 20, fontWeight: 600, margin: "8px 0 8px", lineHeight: 1.25 }}>{w.title}</h3>
              <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, lineHeight: 1.55, margin: "0 0 10px" }}>{w.excerpt}</p>
              <div style={{ display: "flex", gap: 6, flexWrap: "wrap" }}>{w.tags.map(t => <Tag key={t}>{t}</Tag>)}</div>
            </div>
          </div>
          </Reveal>
        ))}
      </div>
      {filtered.length === 0 && <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft }}>Aucun travail dans cette catégorie pour le moment.</p>}
    </div>
  );
}
