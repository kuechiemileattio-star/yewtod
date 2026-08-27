import React, { useEffect } from "react";
import { createPortal } from "react-dom";
import { Image as ImageIcon, Video, X } from "lucide-react";
import { CATEGORIES, CONTENT_FIELDS, DEFAULT_MEDIA, emptyContentFields } from "../data.js";
import Btn from "./Btn.jsx";
import Field, { inputStyle } from "./Field.jsx";

export default function PostEditorModal({ post, draftTitle, draftCat, draftFields = {}, draftStatus = "Brouillon", onChangePost, onChangeDraft, onChangeDraftCat, onChangeDraftFields, onChangeDraftStatus, onSave, onClose }) {
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

  function updateMedia(key, value) {
    updateField(key, value);
  }

  function selectMedia(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    const type = file.type.startsWith("video/") ? "video" : "image";
    updateMedia(type === "video" ? "mediaVideoUrl" : "mediaImageUrl", URL.createObjectURL(file));
  }

  const mediaType = values.mediaType || (values.mediaVideoUrl || values.mediaUrl && /video|youtube|youtu\.be|vimeo/i.test(values.mediaUrl) ? "video" : "image");
  const imageUrl = values.mediaImageUrl || (mediaType === "image" ? values.mediaUrl : "") || DEFAULT_MEDIA[category]?.image || "";
  const videoUrl = values.mediaVideoUrl || (mediaType === "video" ? values.mediaUrl : "") || DEFAULT_MEDIA[category]?.video || "";
  const mediaUrl = mediaType === "video" ? videoUrl : imageUrl;

  return createPortal(
    <div className="ytd-admin-modal-backdrop" onMouseDown={event => event.target === event.currentTarget && onClose()}>
      <form onSubmit={onSave} className="ytd-admin-panel ytd-admin-form ytd-admin-content-modal" role="dialog" aria-modal="true" aria-labelledby="post-modal-title">
        <div className="ytd-admin-book-editor-head"><div><span className="ytd-admin-kicker">{editing ? "Modification" : "Nouveau contenu"}</span><h2 id="post-modal-title">{editing ? "Modifier la publication" : "Créer un brouillon"}</h2></div><button type="button" onClick={onClose} aria-label="Fermer"><X size={18} /></button></div>
        <Field label="Titre"><input required value={editing ? post.title : draftTitle} onChange={event => editing ? onChangePost({ ...post, title: event.target.value }) : onChangeDraft(event.target.value)} style={inputStyle} placeholder="Titre de la publication" /></Field>
        <Field label="Catégorie"><select value={category} onChange={changeCategory} style={inputStyle}>{CATEGORIES.map(category => <option key={category}>{category}</option>)}</select></Field>
        <div className="ytd-admin-meta-fields">
          <Field label="Auteur"><input value={values.author || "Yewtod"} onChange={event => updateField("author", event.target.value)} style={inputStyle} /></Field>
          <Field label="Date de publication"><input type="date" value={values.date || ""} onChange={event => updateField("date", event.target.value)} style={inputStyle} /></Field>
          <Field label="Temps de lecture"><input value={values.readTime || ""} onChange={event => updateField("readTime", event.target.value)} style={inputStyle} placeholder="10 min" /></Field>
        </div>
        <Field label="Statut"><select value={editing ? post.statut : draftStatus} onChange={event => editing ? onChangePost({ ...post, statut: event.target.value }) : onChangeDraftStatus(event.target.value)} style={inputStyle}><option>Publié</option><option>Brouillon</option><option>Programmé</option></select></Field>
        <div className="ytd-admin-media-field">
          <span className="ytd-admin-media-label">Médias de la publication · facultatifs</span>
          <div className="ytd-admin-media-types">
            {[['image', ImageIcon, 'Image'], ['video', Video, 'Vidéo']].map(([type, Icon, label]) => <button key={type} type="button" className={mediaType === type ? "is-active" : ""} onClick={() => updateMedia("mediaType", type)}><Icon size={15} /> {label}</button>)}
          </div>
          <div className="ytd-admin-media-inputs">
            <input type="url" value={mediaUrl} onChange={event => updateMedia(mediaType === "video" ? "mediaVideoUrl" : "mediaImageUrl", event.target.value)} style={inputStyle} placeholder={mediaType === "video" ? "https://.../video.mp4 ou YouTube" : "https://.../image.jpg"} />
            <label className="ytd-admin-file-input">Choisir un fichier<input type="file" accept={mediaType === "video" ? "video/*" : "image/*"} onChange={selectMedia} /></label>
          </div>
          <span className="ytd-admin-media-help">Vous pouvez renseigner une image, une vidéo, les deux, ou aucun média.</span>
          {(imageUrl || videoUrl) && <div className="ytd-admin-media-previews">{imageUrl && <img src={imageUrl} alt="Aperçu de l'image" />}{videoUrl && <video src={videoUrl} controls />}</div>}
        </div>
        {fields.map(([key, label, type = "textarea"]) => <Field key={key} label={label}>{type === "select" ? <select value={values[key] || ""} onChange={event => updateField(key, event.target.value)} style={inputStyle}><option value="idée">Idée</option><option value="en cours">En cours</option><option value="avancée">Avancée</option><option value="finalisée">Finalisée</option><option value="bar">Bar</option><option value="line">Line</option><option value="map">Map</option><option value="network">Network</option></select> : type === "textarea" ? <textarea rows={key === "content" || key === "transcript" ? 7 : 3} value={values[key] || ""} onChange={event => updateField(key, event.target.value)} style={{ ...inputStyle, resize: "vertical" }} /> : <input type={type} value={values[key] || ""} onChange={event => updateField(key, event.target.value)} style={inputStyle} />}</Field>)}
        <div className="ytd-admin-book-editor-actions"><Btn type="submit" variant="green">Enregistrer</Btn><Btn type="button" variant="outline" onClick={onClose}>Annuler</Btn></div>
      </form>
    </div>,
    document.body,
  );
}
