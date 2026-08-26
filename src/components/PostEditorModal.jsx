import React, { useEffect } from "react";
import { createPortal } from "react-dom";
import { X } from "lucide-react";
import { CATEGORIES, CONTENT_FIELDS, emptyContentFields } from "../data.js";
import Btn from "./Btn.jsx";
import Field, { inputStyle } from "./Field.jsx";

export default function PostEditorModal({ post, draftTitle, draftCat, draftFields = {}, onChangePost, onChangeDraft, onChangeDraftCat, onChangeDraftFields, onSave, onClose }) {
  useEffect(() => {
    const closeOnEscape = event => event.key === "Escape" && onClose();
    document.addEventListener("keydown", closeOnEscape);
    return () => document.removeEventListener("keydown", closeOnEscape);
  }, [onClose]);

  const editing = Boolean(post);
  const category = editing ? post.category : draftCat;
  const fields = CONTENT_FIELDS[category] || CONTENT_FIELDS.Articles;
  const values = editing ? post : draftFields;

  function updateField(key, value) {
    if (editing) onChangePost({ ...post, [key]: value });
    else onChangeDraftFields({ ...draftFields, [key]: value });
  }

  function changeCategory(event) {
    const value = event.target.value;
    if (editing) onChangePost({ ...post, category: value, ...emptyContentFields(value) });
    else {
      onChangeDraftCat(value);
      onChangeDraftFields(emptyContentFields(value));
    }
  }

  return createPortal(
    <div className="ytd-admin-modal-backdrop" onMouseDown={event => event.target === event.currentTarget && onClose()}>
      <form onSubmit={onSave} className="ytd-admin-panel ytd-admin-form ytd-admin-content-modal" role="dialog" aria-modal="true" aria-labelledby="post-modal-title">
        <div className="ytd-admin-book-editor-head"><div><span className="ytd-admin-kicker">{editing ? "Modification" : "Nouveau contenu"}</span><h2 id="post-modal-title">{editing ? "Modifier la publication" : "Créer un brouillon"}</h2></div><button type="button" onClick={onClose} aria-label="Fermer"><X size={18} /></button></div>
        <Field label="Titre"><input required value={editing ? post.title : draftTitle} onChange={event => editing ? onChangePost({ ...post, title: event.target.value }) : onChangeDraft(event.target.value)} style={inputStyle} placeholder="Titre de la publication" /></Field>
        <Field label="Catégorie"><select value={category} onChange={changeCategory} style={inputStyle}>{CATEGORIES.map(category => <option key={category}>{category}</option>)}</select></Field>
        {editing && <Field label="Statut"><select value={post.statut} onChange={event => onChangePost({ ...post, statut: event.target.value })} style={inputStyle}><option>Publié</option><option>Brouillon</option></select></Field>}
        {fields.map(([key, label, type = "textarea"]) => <Field key={key} label={label}>{type === "select" ? <select value={values[key] || ""} onChange={event => updateField(key, event.target.value)} style={inputStyle}><option value="idée">Idée</option><option value="en cours">En cours</option><option value="avancée">Avancée</option><option value="finalisée">Finalisée</option><option value="bar">Bar</option><option value="line">Line</option><option value="map">Map</option><option value="network">Network</option></select> : type === "textarea" ? <textarea rows={key === "content" || key === "transcript" ? 7 : 3} value={values[key] || ""} onChange={event => updateField(key, event.target.value)} style={{ ...inputStyle, resize: "vertical" }} /> : <input type={type} value={values[key] || ""} onChange={event => updateField(key, event.target.value)} style={inputStyle} />}</Field>)}
        <div className="ytd-admin-book-editor-actions"><Btn type="submit" variant="green">Enregistrer</Btn><Btn type="button" variant="outline" onClick={onClose}>Annuler</Btn></div>
      </form>
    </div>,
    document.body,
  );
}
