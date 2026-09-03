import React, { useState } from "react";
import { createPortal } from "react-dom";
import { Plus, Search, Edit3, Trash2, Save, X, BookOpen } from "lucide-react";
import { T } from "../../theme.js";
import { BOOK_CATEGORIES, BOOK_DIFFICULTY_LABELS } from "../../lib/contentTypes.js";
import { useAdminBooks } from "../../hooks/useAdminBooks.js";
import Btn from "../../components/Btn.jsx";
import Field, { inputStyle } from "../../components/Field.jsx";
import StatusPill from "../../components/StatusPill.jsx";
import MediaField from "../../components/dashboard/MediaField.jsx";

const STATUS_LABELS = { draft: "Brouillon", published: "Publié", scheduled: "Programmé" };
const DIFFICULTY_OPTIONS = Object.entries(BOOK_DIFFICULTY_LABELS);

function emptyBook() {
  return {
    title: "", author: "", publisher: "", publicationYear: "", category: BOOK_CATEGORIES[0],
    difficultyLevel: "accessible", summary: "", personalReview: "", favoriteQuotes: "", similarBooks: "",
    coverImage: "", purchaseOrReadLink: "", status: "draft",
  };
}

function BookEditor({ book, onChange, onSave, onClose, saving }) {
  const update = key => e => onChange({ ...book, [key]: e.target.value });
  return createPortal(
    <div className="ytd-admin-modal-backdrop" onMouseDown={e => e.target === e.currentTarget && onClose()}>
      <form className="ytd-admin-book-editor ytd-admin-book-modal" role="dialog" aria-modal="true" onSubmit={onSave}>
        <div className="ytd-admin-book-editor-head">
          <div><span className="ytd-admin-kicker">Fiche en édition</span><h2>{book.title || "Nouveau livre"}</h2></div>
          <button type="button" onClick={onClose} aria-label="Fermer"><X size={18} /></button>
        </div>
        <div className="ytd-admin-book-fields">
          <Field label="Titre"><input required value={book.title} onChange={update("title")} style={inputStyle} /></Field>
          <Field label="Auteur"><input required value={book.author} onChange={update("author")} style={inputStyle} /></Field>
          <Field label="Maison d'édition"><input value={book.publisher || ""} onChange={update("publisher")} style={inputStyle} /></Field>
          <Field label="Année"><input type="number" value={book.publicationYear || ""} onChange={update("publicationYear")} style={inputStyle} /></Field>
          <Field label="Catégorie"><select value={book.category} onChange={update("category")} style={inputStyle}>{BOOK_CATEGORIES.map(c => <option key={c}>{c}</option>)}</select></Field>
          <Field label="Difficulté"><select value={book.difficultyLevel} onChange={update("difficultyLevel")} style={inputStyle}>{DIFFICULTY_OPTIONS.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
        </div>
        <Field label="Résumé"><textarea rows={4} value={book.summary || ""} onChange={update("summary")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Avis personnel"><textarea required rows={5} value={book.personalReview || ""} onChange={update("personalReview")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Citations favorites (une par ligne)"><textarea rows={3} value={book.favoriteQuotes || ""} onChange={update("favoriteQuotes")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Ouvrages similaires (un par ligne)"><textarea rows={3} value={book.similarBooks || ""} onChange={update("similarBooks")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Couverture"><MediaField kind="image" bucket="covers" accept="image/*" value={book.coverImage} onChange={url => onChange({ ...book, coverImage: url })} /></Field>
        <Field label="Lien d'achat ou de consultation"><input type="url" value={book.purchaseOrReadLink || ""} onChange={update("purchaseOrReadLink")} style={inputStyle} /></Field>
        <Field label="Statut"><select value={book.status} onChange={update("status")} style={inputStyle}>{Object.entries(STATUS_LABELS).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
        <div className="ytd-admin-book-editor-actions">
          <Btn type="submit" variant="green" style={{ opacity: saving ? 0.7 : 1 }}><Save size={15} /> {saving ? "Enregistrement…" : "Enregistrer la fiche"}</Btn>
          <Btn type="button" variant="outline" onClick={onClose}>Annuler</Btn>
        </div>
      </form>
    </div>,
    document.body
  );
}

export default function BooksPanel() {
  const { books, loading, createBook, updateBook, deleteBook } = useAdminBooks();
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState("Toutes");
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const filtered = books.filter(b => (filter === "Toutes" || b.category === filter) && `${b.title} ${b.author}`.toLowerCase().includes(search.toLowerCase()));

  async function handleSave(e) {
    e.preventDefault();
    setSaving(true);
    setError("");
    try {
      if (editing.id) await updateBook(editing.id, editing);
      else await createBook(editing);
      setEditing(null);
    } catch (err) {
      setError(err.message || "Erreur lors de l'enregistrement.");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(id) {
    if (!window.confirm("Supprimer définitivement ce livre ?")) return;
    await deleteBook(id);
  }

  return (
    <div className="ytd-admin-view">
      <div className="ytd-admin-section-heading">
        <div><span className="ytd-admin-kicker">Bibliothèque éditoriale</span><h1>Livres</h1><p>Créer, modifier et publier les fiches livres — visibles sur le site dès qu'elles sont "Publié".</p></div>
        <Btn variant="green" onClick={() => setEditing(emptyBook())}><Plus size={15} /> Ajouter un livre</Btn>
      </div>

      <div className="ytd-admin-book-toolbar">
        <label className="ytd-admin-search"><Search size={15} /><input value={search} onChange={e => setSearch(e.target.value)} placeholder="Rechercher un livre ou un auteur" /></label>
        <div className="ytd-admin-filter-scroll">{["Toutes", ...BOOK_CATEGORIES].map(c => <button key={c} onClick={() => setFilter(c)} className={filter === c ? "is-active" : ""}>{c}</button>)}</div>
      </div>

      {error && <p style={{ color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>{error}</p>}

      {loading ? <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</p> : (
        <div className="ytd-admin-books">
          {filtered.map((book, index) => (
            <article key={book.id} className="ytd-admin-book-card" style={{ animationDelay: `${index * 55}ms` }}>
              <div className="ytd-admin-book-cover" style={{ background: `linear-gradient(145deg, ${T.green}, ${T.greenDeep})` }}>
                {book.coverImage && <img src={book.coverImage} alt="" onError={e => { e.currentTarget.style.display = "none"; }} />}
                <BookOpen size={22} />
              </div>
              <div className="ytd-admin-book-copy">
                <span>{book.category} · {BOOK_DIFFICULTY_LABELS[book.difficultyLevel] || book.difficultyLevel}</span>
                <h2>{book.title}</h2>
                <small>{book.author}</small>
                <p>{book.personalReview}</p>
              </div>
              <div style={{ position: "absolute", top: 12, right: 12, display: "flex", gap: 6 }}>
                <StatusPill statut={STATUS_LABELS[book.status]} />
              </div>
              <div style={{ position: "absolute", bottom: 12, right: 12, display: "flex", gap: 6 }}>
                <button className="ytd-admin-icon-button" onClick={() => setEditing(book)} aria-label={`Modifier ${book.title}`}><Edit3 size={15} /></button>
                <button className="ytd-admin-icon-button" onClick={() => handleDelete(book.id)} aria-label={`Supprimer ${book.title}`}><Trash2 size={15} /></button>
              </div>
            </article>
          ))}
          {filtered.length === 0 && <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Aucun livre ne correspond à cette recherche.</p>}
        </div>
      )}

      {editing && <BookEditor book={editing} onChange={setEditing} onSave={handleSave} onClose={() => setEditing(null)} saving={saving} />}
    </div>
  );
}
