import React, { useEffect } from "react";
import { createPortal } from "react-dom";
import { X } from "lucide-react";
import { CATEGORIES } from "../data.js";
import Btn from "./Btn.jsx";
import Field, { inputStyle } from "./Field.jsx";

export default function PostEditorModal({ post, draftTitle, draftCat, onChangePost, onChangeDraft, onSave, onClose }) {
  useEffect(() => {
    const closeOnEscape = event => event.key === "Escape" && onClose();
    document.addEventListener("keydown", closeOnEscape);
    return () => document.removeEventListener("keydown", closeOnEscape);
  }, [onClose]);

  const editing = Boolean(post);
  return createPortal(
    <div className="ytd-admin-modal-backdrop" onMouseDown={event => event.target === event.currentTarget && onClose()}>
      <form onSubmit={onSave} className="ytd-admin-panel ytd-admin-form ytd-admin-content-modal" role="dialog" aria-modal="true" aria-labelledby="post-modal-title">
        <div className="ytd-admin-book-editor-head"><div><span className="ytd-admin-kicker">{editing ? "Modification" : "Nouveau contenu"}</span><h2 id="post-modal-title">{editing ? "Modifier la publication" : "Créer un brouillon"}</h2></div><button type="button" onClick={onClose} aria-label="Fermer"><X size={18} /></button></div>
        <Field label="Titre"><input required value={editing ? post.title : draftTitle} onChange={event => editing ? onChangePost({ ...post, title: event.target.value }) : onChangeDraft(event.target.value)} style={inputStyle} placeholder="Titre de la publication" /></Field>
        <Field label="Catégorie"><select value={editing ? post.category : draftCat} onChange={event => editing ? onChangePost({ ...post, category: event.target.value }) : onChangeDraft(event.target.value)} style={inputStyle}>{CATEGORIES.map(category => <option key={category}>{category}</option>)}</select></Field>
        {editing && <Field label="Statut"><select value={post.statut} onChange={event => onChangePost({ ...post, statut: event.target.value })} style={inputStyle}><option>Publié</option><option>Brouillon</option></select></Field>}
        <div className="ytd-admin-book-editor-actions"><Btn type="submit" variant="green">Enregistrer</Btn><Btn type="button" variant="outline" onClick={onClose}>Annuler</Btn></div>
      </form>
    </div>,
    document.body,
  );
}
