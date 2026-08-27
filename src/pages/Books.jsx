import React, { useState } from "react";
import { T } from "../theme.js";
import { BOOKS, BOOK_CATEGORIES, BOOK_COVERS } from "../data.js";
import Reveal from "../components/Reveal.jsx";

export default function Books({ openBook, books = BOOKS }) {
  const [cat, setCat] = useState("Toutes");
  const filtered = cat === "Toutes" ? books : books.filter(b => b.category === cat);

  return (
    <div style={{ maxWidth: 1120, margin: "0 auto", padding: "72px 24px 110px" }}>
      <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(34px, 4.5vw, 48px)", fontWeight: 500, margin: "0 0 12px" }}>Books</h1>
      <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 16, color: T.inkSoft, maxWidth: 600, marginBottom: 40 }}>
        La bibliothèque personnelle : livres recommandés, articles scientifiques, rapports publics et thèses, avec un résumé personnel pour chacun.
      </p>

      <div style={{ display: "flex", gap: 10, flexWrap: "wrap", marginBottom: 44 }}>
        {["Toutes", ...BOOK_CATEGORIES].map(c => (
          <button key={c} onClick={() => setCat(c)} className="ytd-pill" style={{
            fontFamily: "'IBM Plex Mono', monospace", fontSize: 12.5, padding: "8px 14px", cursor: "pointer",
            border: `1px solid ${cat === c ? T.ink : T.line}`, background: cat === c ? T.ink : "transparent",
            color: cat === c ? T.paper : T.inkSoft, borderRadius: 20, textTransform: "capitalize",
          }}>{c}</button>
        ))}
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 28 }} className="ytd-grid-3 ytd-books-grid">
        {filtered.map((b, i) => (
          <Reveal key={b.id} delay={(i % 3) * 80}>
          <article onClick={() => openBook(b)} onKeyDown={e => e.key === "Enter" && openBook(b)} role="button" tabIndex={0} className="ytd-book-card" style={{ display: "flex", flexDirection: "column", background: T.paper, border: `1px solid ${T.line}`, overflow: "hidden", cursor: "pointer" }}>
            <div className="ytd-book-cover" style={{ height: 220, position: "relative", background: `linear-gradient(160deg, ${b.tone}20, ${b.tone}38)`, borderBottom: `1px solid ${T.line}`, overflow: "hidden" }}>
              <img loading="lazy" src={BOOK_COVERS[b.title]} alt={`Couverture de ${b.title}`} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
              <span style={{ position: "absolute", left: 12, bottom: 12, fontFamily: "'Space Mono', monospace", fontSize: 9.5, color: T.paper, background: `${T.greenDeep}E6`, padding: "5px 8px", textTransform: "uppercase" }}>À lire</span>
            </div>
            <div style={{ padding: "18px 18px 20px" }}>
              <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10.5, color: T.green, textTransform: "uppercase" }}>{b.category} · {b.difficulty}</span>
              <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 17.5, fontWeight: 600, margin: "6px 0 4px", lineHeight: 1.25 }}>{b.title}</h3>
              <span style={{ fontFamily: "'Inter', sans-serif", fontSize: 13, color: T.inkSoft }}>{b.author}</span>
              <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: T.ink, lineHeight: 1.55, marginTop: 8 }}>{b.note}</p>
            </div>
          </article>
          </Reveal>
        ))}
      </div>
    </div>
  );
}
