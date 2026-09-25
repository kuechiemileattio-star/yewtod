import React, { useEffect, useState } from "react";
import { Check } from "lucide-react";
import { T } from "../../theme.js";
import { useAdminPages } from "../../hooks/useAdminPages.js";
import { MEET_DEFAULTS } from "../../lib/pageDefaults.js";
import Field, { inputStyle } from "../../components/Field.jsx";
import Btn from "../../components/Btn.jsx";
import DropzoneField from "../../components/dashboard/DropzoneField.jsx";

export default function PagesPanel() {
  const { content, loading, save } = useAdminPages("meet", MEET_DEFAULTS);
  const [form, setForm] = useState(MEET_DEFAULTS);
  const [saved, setSaved] = useState(false);
  const [imageUploading, setImageUploading] = useState(false);

  useEffect(() => { if (!loading) setForm(content); }, [loading, content]);

  function set(key, value) {
    setForm(prev => ({ ...prev, [key]: value }));
  }

  async function handleSave(e) {
    e.preventDefault();
    if (imageUploading) return;
    await save(form);
    setSaved(true);
    setTimeout(() => setSaved(false), 2000);
  }

  if (loading) return <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</p>;

  return (
    <div className="ytd-dashboard-new ytd-admin-view ytd-admin-settings">
      <div className="ytd-admin-section-heading">
        <div><span className="ytd-admin-kicker">Contenu éditorial</span><h1>Pages</h1><p>Modifie les textes des pages statiques du site sans toucher au code — visible immédiatement sur la page publique.</p></div>
      </div>

      {saved && <p className="ytd-form-message" style={{ color: T.green, fontSize: 13, marginBottom: 18 }}><Check size={14} /> Page "Meet Yewtod" mise à jour.</p>}

      <section className="ytd-admin-settings-card">
        <div className="ytd-admin-settings-card-heading"><div><span>Page publique</span><h2>Meet Yewtod</h2></div><span className="ytd-admin-settings-index">01</span></div>
        <form onSubmit={handleSave} style={{ display: "grid", gap: 18 }}>
          <Field label="Titre de l'accroche"><input value={form.heroTitle} onChange={e => set("heroTitle", e.target.value)} style={inputStyle} /></Field>
          <Field label="Texte de l'accroche"><textarea rows={2} value={form.heroLead} onChange={e => set("heroLead", e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>

          <Field label="Photo du fondateur" hint="Optionnel — sans image, un symbole s'affiche à la place.">
            <DropzoneField kind="image" bucket="avatars" accept="image/*" value={form.founderPortrait} onChange={v => set("founderPortrait", v)} onUploadingChange={setImageUploading} />
          </Field>
          <Field label="Bio — premier paragraphe"><textarea rows={3} value={form.founderBio1} onChange={e => set("founderBio1", e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>
          <Field label="Bio — second paragraphe"><textarea rows={3} value={form.founderBio2} onChange={e => set("founderBio2", e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>
          <Field label="Domaines d'expertise" hint="Un par ligne — affichés comme des étiquettes.">
            <textarea rows={4} value={form.founderTags} onChange={e => set("founderTags", e.target.value)} style={{ ...inputStyle, resize: "vertical", fontFamily: "'IBM Plex Mono', monospace", fontSize: 13 }} />
          </Field>

          <Btn type="submit" variant="green" style={{ alignSelf: "flex-start", opacity: imageUploading ? 0.7 : 1, cursor: imageUploading ? "wait" : "pointer" }}>
            {imageUploading ? "Envoi de l'image…" : "Enregistrer"}
          </Btn>
        </form>
      </section>
    </div>
  );
}
