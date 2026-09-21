import React, { useState } from "react";
import { useNavigate, useParams, Link } from "react-router-dom";
import { Search, ExternalLink, Send } from "lucide-react";
import { T } from "../theme.js";
import { bookPath, PATHS } from "../lib/paths.js";
import { useBook, useBooks, useBookReviews } from "../hooks/useBooks.js";
import useDocumentMeta from "../hooks/useDocumentMeta.js";
import useLogView from "../hooks/useLogView.js";
import Btn from "../components/Btn.jsx";

// Palette locale pour reproduire fidèlement la maquette "Purchase Entrance"
// (en plus des couleurs de T, déjà utilisées ailleurs sur le site).
const PE = {
  red: "#cc1f2f",
  redDark: "#b8172a",
  gold: "#f2a723",
  goldDark: "#e2961a",
  blueLink: "#1155b0",
  panelBg: "#f4f4f4",
  border: "#e2e2e2",
  text: "#1c1c1c",
  textSoft: "#4a4a4a",
};

// CSS injecté une seule fois — un seul fichier, rien d'autre à importer.
const STYLE_ID = "ytd-book-detail-purchase-styles";
if (typeof document !== "undefined" && !document.getElementById(STYLE_ID)) {
  const style = document.createElement("style");
  style.id = STYLE_ID;
  style.textContent = `
.pe-topbar-btn { transition: background 160ms ease, border-color 160ms ease, transform 160ms ease; }
.pe-topbar-btn:hover { transform: translateY(-1px); }
.pe-subnav-link { transition: color 160ms ease; position: relative; }
.pe-subnav-link:hover { color: ${PE.red} !important; }
.pe-tab-btn { transition: color 200ms ease, border-color 200ms ease; }
.pe-tab-btn:hover { color: ${PE.red}; }
.pe-buy-btn { transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease; }
.pe-buy-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 16px -8px rgba(226,150,26,0.6); background: ${PE.goldDark}; }
.pe-buy-btn:active { transform: translateY(0); }
.pe-chapter-link { transition: color 160ms ease; }
.pe-chapter-link:hover { color: ${PE.redDark}; text-decoration: underline; }
.pe-search-btn:hover { background: ${PE.redDark} !important; }

@media (max-width: 820px) {
  .pe-topbar-inner { flex-wrap: wrap; gap: 12px; }
  .pe-search-wrap { order: 3; width: 100%; }
  .ytd-book-detail-grid { grid-template-columns: 1fr !important; gap: 32px !important; }
  .ytd-book-sidebar-box { max-width: 220px; margin: 0 auto; }
}
`;
  document.head.appendChild(style);
}

const TABS = [
  { key: "description", label: "Description" },
  { key: "ebook", label: "Livre électronique" },
  { key: "author", label: "Informations sur l'auteur(s)" },
];

function formatPrice(value) {
  if (value === null || value === undefined || value === "") return null;
  const num = Number(value);
  return Number.isFinite(num) ? num.toFixed(2) : value;
}

function publishedLabel(book) {
  const months = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];
  const monthLabel = book.publicationMonth ? months[Number(book.publicationMonth) - 1] || book.publicationMonth : null;
  if (!book.publicationYear) return null;
  return monthLabel ? `${monthLabel} ${book.publicationYear}` : `${book.publicationYear}`;
}

/** Carte livre avec couverture — utilisée pour "Ouvrages similaires" et "À lire aussi". */
function BookCard({ book, onClick }) {
  return (
    <article onClick={onClick} onKeyDown={e => e.key === "Enter" && onClick()} role="button" tabIndex={0} style={{ cursor: "pointer" }}>
      <div style={{ height: 190, background: "#EEF0F1", display: "grid", placeItems: "center", overflow: "hidden", borderRadius: 4 }}>
        {book.coverImage
          ? <img loading="lazy" src={book.coverImage} alt={`Couverture de ${book.title}`} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
          : <span style={{ fontFamily: "'Inter', sans-serif", fontSize: 11, color: "#4a4a4a" }}>Pas de couverture</span>}
      </div>
      <div style={{ paddingTop: 10 }}>
        <span style={{ fontFamily: "'Inter', sans-serif", fontSize: 11, fontWeight: 700, color: "#cc1f2f", textTransform: "uppercase" }}>{book.category}</span>
        <h3 style={{ fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 14.5, fontWeight: 800, color: "#1c1c1c", margin: "4px 0 2px", lineHeight: 1.25 }}>{book.title}</h3>
        <span style={{ display: "block", fontFamily: "'Inter', sans-serif", fontSize: 12, color: "#4a4a4a" }}>{book.author}</span>
      </div>
    </article>
  );
}

export default function BookDetail() {
  const navigate = useNavigate();
  const { slug } = useParams();
  const { book, loading } = useBook(slug);
  const { books } = useBooks();
  const { reviews, publishReview } = useBookReviews(book?.id);
  const [reviewAuthor, setReviewAuthor] = useState("");
  const [reviewText, setReviewText] = useState("");
  const [activeTab, setActiveTab] = useState("description");
  const [searchTerm, setSearchTerm] = useState("");

  useDocumentMeta(book?.title, book?.summary || book?.personalReview);
  useLogView("books", book?.id);

  const PurchaseHeader = (
    <header style={{ background: T.paper, borderBottom: `1px solid ${PE.border}` }}>
      <div className="pe-topbar-inner" style={{ display: "flex", alignItems: "center", justifyContent: "center", maxWidth: 1440, margin: "0 auto", padding: "12px 24px", background: PE.panelBg }}>
        <div className="pe-search-wrap" style={{ display: "flex", width: "100%", maxWidth: 420 }}>
          <input
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Search By Title, Author"
            style={{ flex: 1, border: `1px solid ${PE.border}`, borderRight: "none", borderRadius: "3px 0 0 3px", padding: "9px 12px", fontFamily: "'Inter', sans-serif", fontSize: 13, outline: "none" }}
          />
          <button className="pe-search-btn" aria-label="Rechercher" style={{ background: PE.red, border: `1px solid ${PE.red}`, borderRadius: "0 3px 3px 0", padding: "0 14px", cursor: "pointer", display: "flex", alignItems: "center" }}>
            <Search size={15} color="#fff" />
          </button>
        </div>
      </div>
    </header>
  );

  if (loading) {
    return (
      <div className="ytd-book-detail-page">
        {PurchaseHeader}
        <div style={{ maxWidth: 1180, margin: "0 auto", padding: "80px 24px", color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</div>
      </div>
    );
  }

  if (!book) {
    return (
      <div className="ytd-book-detail-page">
        {PurchaseHeader}
        <div style={{ maxWidth: 1180, margin: "0 auto", padding: "80px 24px 100px" }}>
          <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft, marginBottom: 20 }}>Ce livre est introuvable ou n'est plus disponible.</p>
          <Link to={PATHS.books} style={{ color: T.green, fontFamily: "'IBM Plex Mono', monospace", fontSize: 13 }}>← Retour aux Books</Link>
        </div>
      </div>
    );
  }

  const similarTitles = (book.similarBooks || "").split("\n").map(t => t.trim()).filter(Boolean);
  const similarBookObjs = similarTitles.map(title => books.find(b => b.title === title)).filter(Boolean);
  // "À lire aussi" = la sélection manuelle de l'admin ; à défaut, on complète
  // avec des ouvrages de la même catégorie plutôt que de laisser la section vide.
  const readAlso = similarBookObjs.length > 0
    ? similarBookObjs
    : books.filter(item => item.id !== book.id && item.category === book.category).slice(0, 4);
  const priceP = formatPrice(book.pricePaperback);
  const priceE = formatPrice(book.priceEbook);
  const dateLabel = publishedLabel(book);
  const components = Array.isArray(book.components) && book.components.length
    ? book.components
    : ["Page de titre", "Auteur", "Avant-propos", "Préface", "Références"];
  const chapterSamples = Array.isArray(book.chapterSamples) ? book.chapterSamples : [];

  async function submitReview(event) {
    event.preventDefault();
    if (!reviewAuthor.trim() || !reviewText.trim()) return;
    await publishReview({ author: reviewAuthor.trim(), text: reviewText.trim() });
    setReviewAuthor("");
    setReviewText("");
  }

  return (
    <div className="ytd-book-detail-page" style={{ background: "#fbfbfb", minHeight: "100vh" }}>
      {PurchaseHeader}

      <div style={{ maxWidth: 1180, margin: "0 auto", padding: "32px 24px 100px" }}>
        <div style={{ background: "#fff", border: `1px solid ${PE.border}`, borderRadius: 6, padding: 32 }}>
          <h1 style={{ fontFamily: "'Inter', sans-serif", fontSize: "clamp(22px, 3vw, 28px)", fontWeight: 700, lineHeight: 1.3, margin: "0 0 24px", color: PE.text }}>
            {book.title}
          </h1>

          <div className="ytd-book-detail-grid" style={{ display: "grid", gridTemplateColumns: "230px minmax(0, 1fr)", gap: 40, alignItems: "start" }}>
            {/* Colonne latérale : This Book */}
            <aside className="ytd-book-sidebar-box">
              <h2 style={{ fontFamily: "'Inter', sans-serif", fontSize: 18, fontWeight: 700, margin: "0 0 6px", color: PE.text, borderBottom: `2px solid ${PE.red}`, paddingBottom: 10 }}>
                This Book
              </h2>
              {book.coverImage && (
                <img
                  src={book.coverImage}
                  alt={`Couverture de ${book.title}`}
                  style={{ width: "100%", border: `1px solid ${PE.border}`, boxShadow: "0 6px 16px -10px rgba(0,0,0,0.35)", margin: "16px 0 18px" }}
                />
              )}

              <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: PE.text, margin: "0 0 4px" }}>
                {book.pageCount ? `${book.pageCount}pp.` : ""}{dateLabel ? ` Published ${dateLabel}` : ""}
              </p>
              {book.doi && (
                <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: PE.text, margin: "0 0 4px" }}>
                  DOI: {book.doi}
                </p>
              )}
              {book.isbnPaperback && (
                <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: PE.text, margin: "0 0 16px" }}>
                  ISBN: {book.isbnPaperback}
                </p>
              )}

              {priceP && (
                <div style={{ marginBottom: 16 }}>
                  <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: PE.text, margin: "0 0 8px" }}>
                    (Paperback) <strong>USD {priceP}</strong>
                  </p>
                  <a
                    href={book.buyPaperbackUrl || "#"}
                    className="pe-buy-btn"
                    style={{ display: "block", textAlign: "center", background: PE.gold, color: "#fff", fontFamily: "'Inter', sans-serif", fontWeight: 700, fontSize: 14, padding: "10px 0", borderRadius: 4, textDecoration: "none" }}
                  >
                    Buy Now
                  </a>
                </div>
              )}

              {book.isbnEbook && (
                <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: PE.text, margin: "0 0 16px" }}>
                  ISBN: {book.isbnEbook}
                </p>
              )}

              {priceE && (
                <div>
                  <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: PE.text, margin: "0 0 8px" }}>
                    (E-Book) <strong>USD {priceE}</strong>
                  </p>
                  <a
                    href={book.buyEbookUrl || "#"}
                    className="pe-buy-btn"
                    style={{ display: "block", textAlign: "center", background: PE.gold, color: "#fff", fontFamily: "'Inter', sans-serif", fontWeight: 700, fontSize: 14, padding: "10px 0", borderRadius: 4, textDecoration: "none" }}
                  >
                    Buy Now
                  </a>
                </div>
              )}
            </aside>

            {/* Colonne principale : onglets */}
            <div>
              <div className="ytd-book-tabs-row" style={{ display: "flex", flexWrap: "wrap", gap: 8, borderBottom: `1px solid ${PE.border}`, marginBottom: 24 }}>
                {TABS.map((tab) => (
                  <button
                    key={tab.key}
                    onClick={() => setActiveTab(tab.key)}
                    className="pe-tab-btn ytd-book-tab-btn"
                    style={{
                      background: "none",
                      border: "none",
                      borderBottom: activeTab === tab.key ? `2px solid ${PE.red}` : "2px solid transparent",
                      cursor: "pointer",
                      fontFamily: "'Inter', sans-serif",
                      fontSize: 15,
                      fontWeight: 700,
                      whiteSpace: "nowrap",
                      color: activeTab === tab.key ? PE.red : PE.textSoft,
                      padding: "10px 16px",
                      marginBottom: -1,
                    }}
                  >
                    {tab.label}
                  </button>
                ))}
              </div>

              {activeTab === "description" && (
                <div>
                  <h3 style={{ fontFamily: "'Inter', sans-serif", fontSize: 15, fontWeight: 700, margin: "0 0 10px", color: PE.text }}>
                    Résumé
                  </h3>
                  <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15.5, lineHeight: 1.85, color: PE.text, margin: "0 0 28px" }}>
                    {book.description || book.summary || book.personalReview || "Description à venir."}
                  </p>

                  {chapterSamples.length > 0 && (
                    <div style={{ marginBottom: 28 }}>
                      <h3 style={{ fontFamily: "'Inter', sans-serif", fontSize: 15, fontWeight: 700, margin: "0 0 10px", color: PE.text }}>
                        Exemple de chapitre(s)
                      </h3>
                      {chapterSamples.map((chapter, i) => (
                        <a
                          key={i}
                          href={chapter.url || "#"}
                          className="pe-chapter-link"
                          style={{ display: "block", color: PE.blueLink, fontFamily: "'Inter', sans-serif", fontSize: 14.5, textDecoration: "none", marginBottom: 6 }}
                        >
                          {chapter.label}{chapter.sizeLabel ? ` (${chapter.sizeLabel})` : ""}
                        </a>
                      ))}
                    </div>
                  )}

                  <h3 style={{ fontFamily: "'Inter', sans-serif", fontSize: 15, fontWeight: 700, margin: "0 0 12px", color: PE.text }}>
                    Composition de l'ouvrage
                  </h3>
                  <ul style={{ margin: 0, paddingLeft: 22 }}>
                    {components.map((item, i) => (
                      <li key={i} style={{ fontFamily: "'Inter', sans-serif", fontSize: 14.5, fontWeight: 700, color: PE.text, marginBottom: 8 }}>
                        {item}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {activeTab === "ebook" && (
                <div>
                  <h3 style={{ fontFamily: "'Inter', sans-serif", fontSize: 15, fontWeight: 700, margin: "0 0 10px", color: PE.text }}>
                    Version électronique
                  </h3>
                  <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15.5, lineHeight: 1.85, color: PE.text, margin: "0 0 20px" }}>
                    {book.ebookDescription || "La version électronique de cet ouvrage est disponible au format PDF."}
                  </p>
                  {book.isbnEbook && (
                    <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: PE.textSoft, margin: "0 0 20px" }}>
                      ISBN (ebook) : {book.isbnEbook}
                    </p>
                  )}
                  <div style={{ display: "flex", gap: 12, flexWrap: "wrap" }}>
                    {priceE && (
                      <a
                        href={book.buyEbookUrl || "#"}
                        className="pe-buy-btn"
                        style={{ display: "inline-block", background: PE.gold, color: "#fff", fontFamily: "'Inter', sans-serif", fontWeight: 700, fontSize: 14, padding: "10px 22px", borderRadius: 4, textDecoration: "none" }}
                      >
                        Acheter — USD {priceE}
                      </a>
                    )}
                    {book.ebookFile && (
                      <a
                        href={book.ebookFile}
                        target="_blank" rel="noreferrer"
                        className="pe-buy-btn"
                        style={{ display: "inline-block", background: "none", border: `1px solid ${PE.text}`, color: PE.text, fontFamily: "'Inter', sans-serif", fontWeight: 700, fontSize: 14, padding: "10px 22px", borderRadius: 4, textDecoration: "none" }}
                      >
                        Lire / télécharger le PDF
                      </a>
                    )}
                    {!priceE && !book.ebookFile && (
                      <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: PE.textSoft, fontStyle: "italic" }}>
                        Aucune version électronique disponible pour l'instant.
                      </p>
                    )}
                  </div>
                </div>
              )}

              {activeTab === "author" && (
                <div>
                  <h3 style={{ fontFamily: "'Inter', sans-serif", fontSize: 15, fontWeight: 700, margin: "0 0 10px", color: PE.text }}>
                    À propos de l'auteur·e
                  </h3>
                  <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 16, fontWeight: 700, margin: "0 0 8px", color: PE.text }}>
                    {book.author}
                  </p>
                  <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15, lineHeight: 1.8, color: PE.text, margin: 0 }}>
                    {book.authorBio || "Biographie de l'auteur·e à venir."}
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Contenu Yewtod conservé : avis personnel, citations, avis des lecteurs, livres liés */}
        {(book.personalReview || book.favoriteQuotes) && (
          <div style={{ background: "#fff", border: `1px solid ${PE.border}`, borderRadius: 6, padding: 32, marginTop: 24 }}>
            {book.personalReview && (
              <div style={{ borderLeft: `3px solid ${T.red}`, padding: "4px 0 4px 18px", marginBottom: 24 }}>
                <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10, color: T.red, textTransform: "uppercase" }}>Avis de Yewtod</span>
                <p style={{ fontFamily: "'Newsreader', serif", fontSize: 20, lineHeight: 1.45, margin: "8px 0 0" }}>{book.personalReview}</p>
              </div>
            )}
            {book.favoriteQuotes && (
              <section className="ytd-book-detail-section">
                <h2 style={{ fontFamily: "'Inter', sans-serif", fontSize: 15, fontWeight: 700 }}>Citations favorites</h2>
                <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14.5, color: PE.textSoft, lineHeight: 1.7 }}>{book.favoriteQuotes}</p>
              </section>
            )}
            {book.purchaseOrReadLink && (
              <a href={book.purchaseOrReadLink} target="_blank" rel="noreferrer" style={{ display: "inline-flex", alignItems: "center", gap: 8, color: T.green, fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, marginTop: 12 }}>
                <ExternalLink size={15} /> Ressource externe
              </a>
            )}
          </div>
        )}

        <section className="ytd-book-reviews" style={{ marginTop: 24 }}>
          <div className="ytd-book-reviews-heading">
            <div>
              <span className="ytd-dashboard-eyebrow">Lectures partagées</span>
              <h2>Avis sur ce livre</h2>
            </div>
            <strong>{reviews.length}</strong>
          </div>
          <div className="ytd-book-review-list">
            {reviews.length ? (
              reviews.map((review) => (
                <article className="ytd-book-review" key={review.id}>
                  <div>
                    <strong>{review.author}</strong>
                    <small>{new Date(review.date).toLocaleDateString("fr-FR")}</small>
                  </div>
                  <p>{review.text}</p>
                </article>
              ))
            ) : (
              <p className="ytd-book-empty-review">Aucun avis pour le moment. Soyez le premier à partager votre lecture.</p>
            )}
          </div>
          <form className="ytd-book-review-form" onSubmit={submitReview}>
            <input required value={reviewAuthor} onChange={(event) => setReviewAuthor(event.target.value)} placeholder="Votre nom" />
            <textarea required rows={4} value={reviewText} onChange={(event) => setReviewText(event.target.value)} placeholder="Votre avis sur ce livre" />
            <Btn type="submit" variant="green"><Send size={15} /> Publier l'avis</Btn>
          </form>
        </section>

        {readAlso.length > 0 && (
          <div className="ytd-book-related" style={{ marginTop: 24, background: "#fff", border: `1px solid ${PE.border}`, borderRadius: 6, padding: 32 }}>
            <span className="ytd-dashboard-eyebrow">Continuer la lecture</span>
            <h2 style={{ fontFamily: "'Inter', sans-serif", fontSize: 15, fontWeight: 700, margin: "4px 0 14px" }}>À lire aussi</h2>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(140px, 1fr))", gap: 18 }}>
              {readAlso.map(item => <BookCard key={item.id} book={item} onClick={() => navigate(bookPath(item.slug))} />)}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
