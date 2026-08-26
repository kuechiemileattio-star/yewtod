import React, { useEffect } from "react";
import { createPortal } from "react-dom";
import { ArrowUpRight, Edit3, X } from "lucide-react";
import { T } from "../theme.js";
import { BOOK_COVERS } from "../data.js";
import Btn from "./Btn.jsx";

export default function BookPreviewModal({ book, reviews = [], onEdit, onClose }) {
  useEffect(() => {
    const closeOnEscape = event => event.key === "Escape" && onClose();
    document.addEventListener("keydown", closeOnEscape);
    return () => {
      document.removeEventListener("keydown", closeOnEscape);
    };
  }, [onClose]);

  return createPortal(
    (
    <div className="ytd-admin-modal-backdrop" onMouseDown={event => event.target === event.currentTarget && onClose()}>
      <section className="ytd-admin-book-preview ytd-admin-book-modal" role="dialog" aria-modal="true" aria-labelledby="book-preview-title">
        <div className="ytd-admin-book-preview-head"><span className="ytd-admin-kicker">Aperçu de la fiche</span><button onClick={onClose} aria-label="Fermer"><X size={18} /></button></div>
        <img src={book.coverImage || BOOK_COVERS[book.title]} alt={`Couverture de ${book.title}`} className="ytd-admin-book-preview-cover" />
        <span className="ytd-admin-kicker">{book.category} · {book.difficulty}</span>
        <h2 id="book-preview-title">{book.title}</h2>
        <p className="ytd-admin-book-preview-author">{book.author}</p>
        <p className="ytd-admin-book-preview-description">{book.summary || "Aucune description renseignée."}</p>
        <div className="ytd-admin-book-preview-note"><span>Avis personnel</span><p>{book.personalReview}</p></div>
        <div className="ytd-admin-book-preview-reviews"><span>Avis visiteurs · lecture seule</span><strong>{reviews.length}</strong></div>
        <div className="ytd-admin-book-preview-review-list">{reviews.length ? reviews.map(review => <article key={review.id}><strong>{review.author}</strong><small>{review.date}</small><p>{review.text}</p></article>) : <p>Aucun avis visiteur pour le moment.</p>}</div>
        <div className="ytd-admin-book-preview-actions"><Btn variant="green" onClick={onEdit}><Edit3 size={15} /> Modifier la fiche</Btn>{book.purchaseOrReadLink && <a href={book.purchaseOrReadLink} target="_blank" rel="noreferrer"><ArrowUpRight size={15} /> Ouvrir le lien</a>}</div>
      </section>
    </div>
    ),
    document.body,
  );
}