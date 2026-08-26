import React, { useEffect } from "react";
import { createPortal } from "react-dom";
import { Save, X } from "lucide-react";
import { T } from "../theme.js";
import { BOOK_CATEGORIES } from "../data.js";
import Btn from "./Btn.jsx";
import Field, { inputStyle } from "./Field.jsx";

export default function BookEditorModal({ book, onChange, onSave, onClose }) {
  useEffect(() => {
    const closeOnEscape = event => event.key === "Escape" && onClose();
    document.addEventListener("keydown", closeOnEscape);
    return () => {
      document.removeEventListener("keydown", closeOnEscape);
    };
  }, [onClose]);

  const update = key => event => onChange({ ...book, [key]: event.target.value });

  return createPortal(
    (
    <div className="ytd-admin-modal-backdrop" onMouseDown={event => event.target === event.currentTarget && onClose()}>
      <form className="ytd-admin-book-editor ytd-admin-book-modal" role="dialog" aria-modal="true" aria-labelledby="book-editor-title" onSubmit={onSave}>
        <div className="ytd-admin-book-editor-head">
          <div><span className="ytd-admin-kicker">Fiche en édition</span><h2 id="book-editor-title">{book.title || "Nouveau livre"}</h2></div>
          <button type="button" onClick={onClose} aria-label="Fermer"><X size={18} /></button>
        </div>
        <div className="ytd-admin-book-fields">
          <Field label="Titre"><input required value={book.title} onChange={update("title")} style={inputStyle} /></Field>
          <Field label="Auteur"><input required value={book.author} onChange={update("author")} style={inputStyle} /></Field>
          <Field label="Maison d'édition"><input value={book.publisher || ""} onChange={update("publisher")} style={inputStyle} /></Field>
          <Field label="Année de publication"><input type="number" value={book.publicationYear || ""} onChange={update("publicationYear")} style={inputStyle} /></Field>
          <Field label="Catégorie"><select value={book.category} onChange={update("category")} style={inputStyle}>{BOOK_CATEGORIES.map(category => <option key={category}>{category}</option>)}</select></Field>
          <Field label="Niveau de difficulté"><select value={book.difficultyLevel || "débutant"} onChange={update("difficultyLevel")} style={inputStyle}><option>débutant</option><option>intermédiaire</option><option>avancé</option></select></Field>
        </div>
        <Field label="Résumé"><textarea rows={4} value={book.summary || ""} onChange={update("summary")} placeholder="Présenter le livre et son intérêt" style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Avis personnel"><textarea required rows={5} value={book.personalReview || ""} onChange={update("personalReview")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Citations favorites"><textarea rows={4} value={book.favoriteQuotes || ""} onChange={update("favoriteQuotes")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Ouvrages similaires"><textarea rows={3} value={book.similar || ""} onChange={update("similar")} placeholder="Un ouvrage par ligne" style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Couverture"><input type="url" value={book.coverImage || ""} onChange={update("coverImage")} placeholder="https://.../image.jpg" style={inputStyle} /></Field>
        <Field label="Lien d'achat ou de consultation"><input type="url" value={book.purchaseOrReadLink || ""} onChange={update("purchaseOrReadLink")} placeholder="https://..." style={inputStyle} /></Field>
        <div className="ytd-admin-book-editor-actions"><Btn type="submit" variant="green"><Save size={15} /> Enregistrer la fiche</Btn><Btn type="button" variant="outline" onClick={onClose}>Annuler</Btn></div>
      </form>
    </div>
    ),
    document.body,
  );
}
