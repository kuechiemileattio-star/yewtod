import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import { Search } from "lucide-react";
import { T } from "../theme.js";
import { BOOK_CATEGORIES, fmtDate } from "../lib/contentTypes.js";
import { bookPath } from "../lib/paths.js";
import { useBooks } from "../hooks/useBooks.js";
import useDocumentMeta from "../hooks/useDocumentMeta.js";
import Reveal from "../components/Reveal.jsx";

const SORTS = {
  recent: { label: "Plus récents", fn: (a, b) => new Date(b.publishedAt || b.createdAt || 0) - new Date(a.publishedAt || a.createdAt || 0) },
  az: { label: "Titre (A–Z)", fn: (a, b) => a.title.localeCompare(b.title) },
};

const selectStyle = {
  fontFamily: "'Inter', sans-serif", fontSize: 13.5, padding: "10px 12px",
  border: `1px solid ${T.line}`, background: T.paper, color: T.ink, cursor: "pointer",
};

const CATEGORY_TAG = { fontFamily: "'Inter', sans-serif", fontSize: 12, fontWeight: 700, color: T.red, textTransform: "uppercase", letterSpacing: ".02em" };
const COVER_BG = "#EEF0F1";

function Cover({ book, height }) {
  return (
    <div style={{ height, background: COVER_BG, display: "grid", placeItems: "center", overflow: "hidden" }}>
      {book.coverImage
        ? <img loading="lazy" src={book.coverImage} alt={`Couverture de ${book.title}`} style={{ width: "100%", height: "100%", objectFit: "cover", filter: "drop-shadow(0 10px 14px rgba(0,0,0,.25))" }} />
        : <span style={{ fontFamily: "'Inter', sans-serif", fontSize: 11, color: T.inkSoft }}>Pas de couverture</span>}
    </div>
  );
}

export default function Books() {
  const navigate = useNavigate();
  const { books, loading } = useBooks();
  useDocumentMeta("Books", "La bibliothèque personnelle de Yewtod SS : livres recommandés avec avis personnel, citations favorites et niveau de difficulté.");
  const [cat, setCat] = useState("Toutes");
  const [author, setAuthor] = useState("Tous");
  const [year, setYear] = useState("Toutes");
  const [sort, setSort] = useState("recent");
  const [search, setSearch] = useState("");

  const authors = [...new Set(books.map(b => b.author).filter(Boolean))].sort((a, b) => a.localeCompare(b));
  const years = [...new Set(books.map(b => b.publicationYear).filter(Boolean))].sort((a, b) => b - a);

  const filtered = books
    .filter(b => cat === "Toutes" || b.category === cat)
    .filter(b => author === "Tous" || b.author === author)
    .filter(b => year === "Toutes" || String(b.publicationYear) === year)
    .filter(b => `${b.title} ${b.author}`.toLowerCase().includes(search.trim().toLowerCase()))
    .sort(SORTS[sort].fn);

  const [hero, ...rest] = filtered;
  const sideList = rest.slice(0, 3);
  const gridBooks = rest.slice(3);

  const openBook = book => navigate(bookPath(book.slug));

  return (
    <div style={{ maxWidth: 1180, margin: "0 auto", padding: "72px 24px 110px" }}>
      <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(34px, 4.5vw, 48px)", fontWeight: 500, margin: "0 0 12px" }}>Books</h1>
      <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 16, color: T.inkSoft, maxWidth: 640, marginBottom: 40 }}>
        La bibliothèque de Yewtod SS explore en profondeur les sujets qui comptent le plus : livres recommandés, avec un avis personnel, des citations favorites et un niveau de difficulté pour chacun.
      </p>

      <div style={{ display: "flex", gap: 12, flexWrap: "wrap", alignItems: "center", marginBottom: 44, paddingBottom: 24, borderBottom: `1px solid ${T.line}` }}>
        <label style={{ display: "flex", alignItems: "center", gap: 8, flex: "1 1 240px", minWidth: 200, padding: "10px 12px", border: `1px solid ${T.line}`, background: T.paper }}>
          <Search size={15} color={T.inkSoft} />
          <input
            value={search} onChange={e => setSearch(e.target.value)} placeholder="Rechercher un titre ou un auteur"
            style={{ border: 0, outline: "none", background: "none", width: "100%", fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: T.ink }}
          />
        </label>
        <select value={cat} onChange={e => setCat(e.target.value)} style={selectStyle} aria-label="Filtrer par catégorie">
          {["Toutes", ...BOOK_CATEGORIES].map(c => <option key={c} value={c}>{c === "Toutes" ? "Toutes les catégories" : c}</option>)}
        </select>
        <select value={author} onChange={e => setAuthor(e.target.value)} style={selectStyle} aria-label="Filtrer par auteur">
          <option value="Tous">Tous les auteurs</option>
          {authors.map(a => <option key={a} value={a}>{a}</option>)}
        </select>
        <select value={year} onChange={e => setYear(e.target.value)} style={selectStyle} aria-label="Filtrer par année">
          <option value="Toutes">Toutes les années</option>
          {years.map(y => <option key={y} value={String(y)}>{y}</option>)}
        </select>
        <select value={sort} onChange={e => setSort(e.target.value)} style={selectStyle} aria-label="Trier">
          {Object.entries(SORTS).map(([key, s]) => <option key={key} value={key}>{s.label}</option>)}
        </select>
      </div>

      {loading ? (
        <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft }}>Chargement…</p>
      ) : filtered.length === 0 ? (
        <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft }}>Aucun livre ne correspond à cette recherche.</p>
      ) : (
        <>
          {hero && (
            <div className="ytd-books-hero" style={{ gridTemplateColumns: sideList.length ? "1.6fr 2px 1fr" : "1fr", marginBottom: 56, paddingBottom: 48, borderBottom: `1px solid ${T.line}` }}>
              <Reveal>
                <article onClick={() => openBook(hero)} onKeyDown={e => e.key === "Enter" && openBook(hero)} role="button" tabIndex={0} style={{ cursor: "pointer" }}>
                  <Cover book={hero} height={480} />
                  <div style={{ paddingTop: 18 }}>
                    <span style={CATEGORY_TAG}>{hero.category}</span>
                    <h2 style={{ fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: "clamp(32px, 4vw, 52px)", fontWeight: 800, color: T.ink, margin: "10px 0 10px", lineHeight: 1.05 }}>{hero.title}</h2>
                    <span style={{ display: "block", fontFamily: "'Inter', sans-serif", fontSize: 16, color: T.ink }}>{hero.author}</span>
                    {(hero.publishedAt || hero.createdAt) && <span style={{ display: "block", marginTop: 4, fontFamily: "'Inter', sans-serif", fontSize: 13, color: T.inkSoft }}>{fmtDate(hero.publishedAt || hero.createdAt)}</span>}
                  </div>
                </article>
              </Reveal>

              {sideList.length > 0 && (
                <>
                  <div className="ytd-books-divider" style={{ background: T.line }} />
                  <div>
                    {sideList.map((b, i) => (
                      <Reveal key={b.id} delay={i * 60}>
                        <article
                          onClick={() => openBook(b)} onKeyDown={e => e.key === "Enter" && openBook(b)} role="button" tabIndex={0}
                          style={{ display: "flex", gap: 16, alignItems: "flex-start", cursor: "pointer", padding: "18px 0", borderTop: i > 0 ? `1px solid ${T.line}` : "none" }}
                        >
                          <div style={{ flexShrink: 0, width: 74 }}><Cover book={b} height={100} /></div>
                          <div style={{ minWidth: 0 }}>
                            <span style={CATEGORY_TAG}>{b.category}</span>
                            <h3 style={{ fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 18, fontWeight: 800, color: T.ink, margin: "5px 0 4px", lineHeight: 1.2 }}>{b.title}</h3>
                            <span style={{ display: "block", fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: T.ink }}>{b.author}</span>
                            {(b.publishedAt || b.createdAt) && <span style={{ display: "block", marginTop: 2, fontFamily: "'Inter', sans-serif", fontSize: 12, color: T.inkSoft }}>{fmtDate(b.publishedAt || b.createdAt)}</span>}
                          </div>
                        </article>
                      </Reveal>
                    ))}
                  </div>
                </>
              )}
            </div>
          )}

          {gridBooks.length > 0 && (
            <div className="ytd-books-grid">
              {gridBooks.map((b, i) => (
                <Reveal key={b.id} delay={(i % 4) * 70}>
                  <article
                    onClick={() => openBook(b)} onKeyDown={e => e.key === "Enter" && openBook(b)} role="button" tabIndex={0}
                    style={{ cursor: "pointer", height: "100%" }}
                  >
                    <span style={CATEGORY_TAG}>{b.category}</span>
                    <div style={{ marginTop: 8 }}><Cover book={b} height={220} /></div>
                    <div style={{ paddingTop: 14 }}>
                      <h3 style={{ fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 19, fontWeight: 800, color: T.ink, margin: "0 0 6px", lineHeight: 1.2 }}>{b.title}</h3>
                      <span style={{ display: "block", fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: T.ink }}>{b.author}</span>
                      {(b.publishedAt || b.createdAt) && <span style={{ display: "block", marginTop: 3, fontFamily: "'Inter', sans-serif", fontSize: 12, color: T.inkSoft }}>{fmtDate(b.publishedAt || b.createdAt)}</span>}
                    </div>
                  </article>
                </Reveal>
              ))}
            </div>
          )}
        </>
      )}
    </div>
  );
}
