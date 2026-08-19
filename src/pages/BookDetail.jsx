import React, { useState } from "react";
import { ArrowLeft, ExternalLink, Send } from "lucide-react";
import { T } from "../theme.js";
import { BOOK_COVERS, BOOKS } from "../data.js";
import Btn from "../components/Btn.jsx";

export default function BookDetail({ book, reviews = [], onPublishReview, back, openBook }) {
  const [reviewAuthor, setReviewAuthor] = useState("");
  const [reviewText, setReviewText] = useState("");
  if (!book) return null;
  const related = BOOKS.filter(item => item.id !== book.id && item.category === book.category).slice(0, 3);

  function publishReview(event) {
    event.preventDefault();
    if (!reviewAuthor.trim() || !reviewText.trim()) return;
    onPublishReview({ id: `r${Date.now()}`, author: reviewAuthor.trim(), text: reviewText.trim(), date: new Date().toISOString().slice(0, 10) });
    setReviewAuthor("");
    setReviewText("");
  }

  return (
    <div className="ytd-book-detail-page" style={{ maxWidth: 920, margin: "0 auto", padding: "56px 24px 110px" }}>
      <button onClick={back} style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 36 }}>
        <ArrowLeft size={15} /> Retour aux Books
      </button>
      <div style={{ display: "grid", gridTemplateColumns: "280px 1fr", gap: 44, alignItems: "start" }} className="ytd-book-detail-grid">
        <img className="ytd-book-detail-cover" src={BOOK_COVERS[book.title]} alt={`Couverture de ${book.title}`} style={{ width: "100%", aspectRatio: "2 / 3", objectFit: "cover", border: `1px solid ${T.line}` }} />
        <div className="ytd-book-detail-copy">
          <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.green, textTransform: "uppercase" }}>{book.category} · {book.difficulty}</span>
          <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(34px, 5vw, 54px)", fontWeight: 500, lineHeight: 1.08, margin: "12px 0 6px" }}>{book.title}</h1>
          <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15, color: T.inkSoft, margin: "0 0 28px" }}>{book.author}</p>
          <div style={{ borderLeft: `3px solid ${T.red}`, padding: "4px 0 4px 18px", marginBottom: 30 }}>
            <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10, color: T.red, textTransform: "uppercase" }}>Avis de Yewtod</span>
            <p style={{ fontFamily: "'Newsreader', serif", fontSize: 22, lineHeight: 1.45, margin: "8px 0 0" }}>{book.note}</p>
          </div>
          <p style={{ fontFamily: "'Newsreader', serif", fontSize: 19, lineHeight: 1.7 }}>{book.description || "Une fiche de lecture pour situer ce livre, préciser ce qu'il apporte et donner des pistes pour poursuivre la réflexion."}</p>
          {book.link ? <a href={book.link} target="_blank" rel="noreferrer" style={{ display: "inline-flex", alignItems: "center", gap: 8, color: T.green, fontFamily: "'IBM Plex Mono', monospace", fontSize: 11 }}><ExternalLink size={15} /> Ressource externe</a> : <Btn variant="outline" style={{ marginTop: 12 }}><ExternalLink size={15} /> Ressource à venir</Btn>}
        </div>
      </div>
      <section className="ytd-book-reviews">
        <div className="ytd-book-reviews-heading"><div><span className="ytd-dashboard-eyebrow">Lectures partagées</span><h2>Avis sur ce livre</h2></div><strong>{reviews.length}</strong></div>
        <div className="ytd-book-review-list">{reviews.length ? reviews.map(review => <article className="ytd-book-review" key={review.id}><div><strong>{review.author}</strong><small>{review.date}</small></div><p>{review.text}</p></article>) : <p className="ytd-book-empty-review">Aucun avis pour le moment. Soyez le premier à partager votre lecture.</p>}</div>
        <form className="ytd-book-review-form" onSubmit={publishReview}><input required value={reviewAuthor} onChange={event => setReviewAuthor(event.target.value)} placeholder="Votre nom" /><textarea required rows={4} value={reviewText} onChange={event => setReviewText(event.target.value)} placeholder="Votre avis sur ce livre" /><Btn type="submit" variant="green"><Send size={15} /> Publier l'avis</Btn></form>
      </section>
      {related.length > 0 && <div style={{ marginTop: 70 }}><h2 style={{ fontFamily: "'Newsreader', serif", fontSize: 28, fontWeight: 500 }}>À lire aussi</h2><div style={{ display: "grid", gap: 12 }}>{related.map(item => <button key={item.id} onClick={() => openBook(item)} style={{ padding: "14px 0", border: 0, borderBottom: `1px solid ${T.line}`, background: "none", textAlign: "left", cursor: "pointer", fontFamily: "'Newsreader', serif", fontSize: 18 }}>{item.title} <span style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>· {item.author}</span></button>)}</div></div>}
    </div>
  );
}
