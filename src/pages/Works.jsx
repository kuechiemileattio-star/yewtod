import React, { useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { T } from "../theme.js";
import { CATEGORIES, fmtDate } from "../lib/contentTypes.js";
import { workPath } from "../lib/paths.js";
import { useWorks } from "../hooks/useWorks.js";
import useDocumentMeta from "../hooks/useDocumentMeta.js";
import Reveal from "../components/Reveal.jsx";
import Cover from "../components/Cover.jsx";
import Tag from "../components/Tag.jsx";

export default function Works() {
  const navigate = useNavigate();
  const { works, loading } = useWorks();
  useDocumentMeta("Works", "Articles, rapports, études, notes de recherche, séries documentaires, expérimentations et visualisations de données publiés par Yewtod SS.");
  const [searchParams, setSearchParams] = useSearchParams();
  const categorieParam = searchParams.get("categorie");
  const [cat, setCat] = useState(() => (categorieParam && CATEGORIES.includes(categorieParam)) ? categorieParam : (sessionStorage.getItem("yewtod-works-filter") || "Toutes"));

  function selectCategory(c) {
    setCat(c);
    sessionStorage.setItem("yewtod-works-filter", c);
    setSearchParams(c === "Toutes" ? {} : { categorie: c }, { replace: true });
  }

  const filtered = cat === "Toutes" ? works : works.filter(w => w.category === cat);
  const openWork = work => navigate(workPath(work.routeSlug, work.slug));

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
          <button key={c} onClick={() => selectCategory(c)} className="ytd-pill" style={{
            fontFamily: "'IBM Plex Mono', monospace", fontSize: 12.5, padding: "8px 14px", cursor: "pointer",
            border: `1px solid ${cat === c ? T.ink : T.line}`, background: cat === c ? T.ink : "transparent",
            color: cat === c ? T.paper : T.inkSoft, borderRadius: 20,
          }}>{c}</button>
        ))}
      </div>

      {loading ? (
        <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft }}>Chargement…</p>
      ) : (
        <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: "40px 32px" }} className="ytd-grid-3">
          {filtered.map((w, i) => (
            <Reveal key={w.id} delay={(i % 3) * 80} as="div" style={{ height: "100%" }}>
              <article
                onClick={() => openWork(w)}
                onKeyDown={event => event.key === "Enter" && openWork(w)}
                className="ytd-card ytd-work-card"
                style={{ cursor: "pointer", display: "flex", flexDirection: "column", height: "100%" }}
                role="link" tabIndex={0} aria-label={`Voir le détail de ${w.title}`}
              >
                <Cover tone={w.tone} label={w.category} image={w.coverImage} tall />
                <div style={{ marginTop: 16, display: "flex", flexDirection: "column", flex: 1 }}>
                  <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft }}>
                    {fmtDate(w.date)}{w.readTime ? ` · ${w.readTime}` : ""}
                  </span>
                  <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 19, fontWeight: 600, margin: "8px 0 8px", lineHeight: 1.28 }}>{w.title}</h3>
                  {w.excerpt && (
                    <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: T.inkSoft, lineHeight: 1.55, margin: "0 0 12px", flex: 1 }}>
                      {w.excerpt.length > 130 ? `${w.excerpt.slice(0, 130).trimEnd()}…` : w.excerpt}
                    </p>
                  )}
                  <div style={{ display: "flex", flexWrap: "wrap", gap: 6, marginTop: "auto" }}>
                    {(w.tags || "").split("\n").filter(Boolean).slice(0, 3).map(tag => <Tag key={tag}>{tag}</Tag>)}
                  </div>
                </div>
              </article>
            </Reveal>
          ))}
        </div>
      )}
      {!loading && filtered.length === 0 && <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft }}>Aucun travail dans cette catégorie pour le moment.</p>}
    </div>
  );
}
