import React from "react";
import { createPortal } from "react-dom";
import { X, ExternalLink, Edit3, Trash2 } from "lucide-react";
import { T } from "../../theme.js";
import { BOOK_DIFFICULTY_LABELS } from "../../lib/contentTypes.js";
import { fmtDate } from "../../lib/contentTypes.js";
import { useBookReviews } from "../../hooks/useBooks.js";
import Btn from "../Btn.jsx";
import StatusPill from "../StatusPill.jsx";

const STATUS_LABELS = { draft: "Brouillon", published: "Publié", scheduled: "Programmé" };

export default function BookPreviewModal({ book, onEdit, onClose }) {
  const { reviews, loading, deleteReview } = useBookReviews(book.id);

  async function handleDeleteReview(id) {
    if (!window.confirm("Supprimer cet avis ?")) return;
    await deleteReview(id);
  }

  return createPortal(
    <div className="ytd-admin-modal-backdrop" onMouseDown={e => e.target === e.currentTarget && onClose()}>
      <section className="ytd-admin-book-preview ytd-admin-book-modal" role="dialog" aria-modal="true" aria-labelledby="book-preview-title">
        <div className="ytd-admin-book-preview-head">
          <span className="ytd-admin-kicker">Aperçu de la fiche</span>
          <button onClick={onClose} aria-label="Fermer"><X size={18} /></button>
        </div>

        {book.coverImage && <img src={book.coverImage} alt={`Couverture de ${book.title}`} className="ytd-admin-book-preview-cover" />}
        <div style={{ display: "flex", alignItems: "center", gap: 10, flexWrap: "wrap" }}>
          <span className="ytd-admin-kicker">{book.category} · {book.difficulty || BOOK_DIFFICULTY_LABELS[book.difficultyLevel]}</span>
          <StatusPill statut={STATUS_LABELS[book.status]} />
        </div>
        <h2 id="book-preview-title">{book.title}</h2>
        <p className="ytd-admin-book-preview-author">{book.author}{book.publisher ? ` · ${book.publisher}` : ""}{book.publicationYear ? ` · ${book.publicationYear}` : ""}</p>
        <p className="ytd-admin-book-preview-description">{book.summary || "Aucune description renseignée."}</p>
        <div className="ytd-admin-book-preview-note"><span>Avis personnel</span><p>{book.personalReview || "—"}</p></div>
        {book.favoriteQuotes && <div className="ytd-admin-book-preview-note"><span>Citations favorites</span><p style={{ whiteSpace: "pre-line" }}>{book.favoriteQuotes}</p></div>}
        {book.similarBooks && <div className="ytd-admin-book-preview-note"><span>Ouvrages similaires</span><p style={{ whiteSpace: "pre-line" }}>{book.similarBooks}</p></div>}

        <div className="ytd-admin-book-preview-reviews"><span>Avis visiteurs</span><strong>{loading ? "…" : reviews.length}</strong></div>
        <div className="ytd-admin-book-preview-review-list">
          {reviews.length ? reviews.map(review => (
            <article key={review.id}>
              <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 10 }}>
                <div><strong>{review.author}</strong><small>{fmtDate(review.date)}</small></div>
                <button onClick={() => handleDeleteReview(review.id)} aria-label="Supprimer cet avis" style={{ border: 0, background: "none", color: T.inkSoft, cursor: "pointer", flexShrink: 0 }}><Trash2 size={14} /></button>
              </div>
              <p>{review.text}</p>
            </article>
          )) : <p>Aucun avis visiteur pour le moment.</p>}
        </div>

        <div className="ytd-admin-book-preview-actions">
          <Btn variant="green" onClick={onEdit}><Edit3 size={15} /> Modifier la fiche</Btn>
          {book.purchaseOrReadLink && <a href={book.purchaseOrReadLink} target="_blank" rel="noreferrer"><ExternalLink size={15} /> Ouvrir le lien</a>}
        </div>
      </section>
    </div>,
    document.body
  );
}
