import React, { useEffect, useState } from "react";
import { Plus, Trash2 } from "lucide-react";
import { T } from "../../theme.js";
import { useAdminSettings } from "../../hooks/useAdminSettings.js";
import Field, { inputStyle } from "../../components/Field.jsx";
import Btn from "../../components/Btn.jsx";

export default function SettingsPanel() {
  const { settings, socialLinks, loading, saveSetting, addSocialLink, removeSocialLink } = useAdminSettings();
  const [siteName, setSiteName] = useState("");
  const [seoDescription, setSeoDescription] = useState("");
  const [logoUrl, setLogoUrl] = useState("");
  const [faviconUrl, setFaviconUrl] = useState("");
  const [reflectionText, setReflectionText] = useState("");
  const [reflectionAuthor, setReflectionAuthor] = useState("Yewtod");
  const [newPlatform, setNewPlatform] = useState("");
  const [newUrl, setNewUrl] = useState("");
  const [saved, setSaved] = useState("");

  useEffect(() => {
    if (loading) return;
    setSiteName(settings.site_name || "");
    setSeoDescription(settings.seo_description || "");
    setLogoUrl(settings.logo_url || "");
    setFaviconUrl(settings.favicon_url || "");
    setReflectionText(settings.reflection_of_week?.text || "");
    setReflectionAuthor(settings.reflection_of_week?.author || "Yewtod");
  }, [loading, settings]);

  async function flashSaved(label) {
    setSaved(label);
    setTimeout(() => setSaved(""), 2000);
  }

  async function saveIdentity(e) {
    e.preventDefault();
    await Promise.all([
      saveSetting("site_name", siteName),
      saveSetting("seo_description", seoDescription),
      saveSetting("logo_url", logoUrl),
      saveSetting("favicon_url", faviconUrl),
    ]);
    flashSaved("Informations du site enregistrées.");
  }

  async function saveReflection(e) {
    e.preventDefault();
    await saveSetting("reflection_of_week", { text: reflectionText, author: reflectionAuthor, date: new Date().toISOString().slice(0, 10) });
    flashSaved("Réflexion de la semaine mise à jour sur la Home.");
  }

  async function submitSocialLink(e) {
    e.preventDefault();
    if (!newPlatform.trim() || !newUrl.trim()) return;
    await addSocialLink(newPlatform.trim(), newUrl.trim());
    setNewPlatform("");
    setNewUrl("");
  }

  if (loading) return <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</p>;

  return (
    <div className="ytd-admin-view ytd-admin-settings">
      <div className="ytd-admin-section-heading">
        <div><span className="ytd-admin-kicker">Configuration du projet</span><h1>Paramètres</h1><p>Identité du site, réflexion de la semaine et réseaux sociaux — tout est lu en direct par le site public.</p></div>
      </div>

      {saved && <p style={{ color: T.green, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>{saved}</p>}

      <section className="ytd-admin-settings-card">
        <div className="ytd-admin-settings-card-heading"><div><span>Identité éditoriale</span><h2>Informations du site</h2></div><span className="ytd-admin-settings-index">01</span></div>
        <form onSubmit={saveIdentity} style={{ display: "grid", gap: 18 }}>
          <div className="ytd-admin-settings-fields">
            <Field label="Nom du site"><input value={siteName} onChange={e => setSiteName(e.target.value)} style={inputStyle} /></Field>
            <Field label="Description SEO"><input value={seoDescription} onChange={e => setSeoDescription(e.target.value)} style={inputStyle} /></Field>
            <Field label="Logo (URL)"><input type="url" value={logoUrl} onChange={e => setLogoUrl(e.target.value)} style={inputStyle} /></Field>
          </div>
          <Field label="Favicon (URL)"><input type="url" value={faviconUrl} onChange={e => setFaviconUrl(e.target.value)} style={inputStyle} /></Field>
          <Btn type="submit" variant="green" style={{ alignSelf: "flex-start" }}>Enregistrer</Btn>
        </form>
      </section>

      <section className="ytd-admin-settings-card">
        <div className="ytd-admin-settings-card-heading"><div><span>Home</span><h2>Réflexion de la semaine</h2></div><span className="ytd-admin-settings-index">02</span></div>
        <form onSubmit={saveReflection} style={{ display: "grid", gap: 18 }}>
          <Field label="Citation"><textarea rows={3} value={reflectionText} onChange={e => setReflectionText(e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>
          <Field label="Auteur"><input value={reflectionAuthor} onChange={e => setReflectionAuthor(e.target.value)} style={inputStyle} /></Field>
          <Btn type="submit" variant="green" style={{ alignSelf: "flex-start" }}>Publier sur la Home</Btn>
        </form>
      </section>

      <section className="ytd-admin-settings-card">
        <div className="ytd-admin-settings-card-heading"><div><span>Audience</span><h2>Réseaux sociaux</h2></div><span className="ytd-admin-settings-index">03</span></div>
        <div style={{ display: "grid", gap: 8 }}>
          {socialLinks.map(link => (
            <div key={link.id} style={{ display: "flex", alignItems: "center", gap: 10, padding: "10px 12px", border: `1px solid ${T.line}`, background: T.paperAlt }}>
              <strong style={{ fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 13, minWidth: 100 }}>{link.platform}</strong>
              <span style={{ flex: 1, color: T.inkSoft, fontFamily: "'JetBrains Mono', monospace", fontSize: 12, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{link.url}</span>
              <button onClick={() => removeSocialLink(link.id)} aria-label={`Retirer ${link.platform}`} style={{ border: 0, background: "none", color: T.inkSoft, cursor: "pointer" }}><Trash2 size={15} /></button>
            </div>
          ))}
          {socialLinks.length === 0 && <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>Aucun réseau social configuré.</p>}
        </div>
        <form onSubmit={submitSocialLink} style={{ display: "grid", gridTemplateColumns: "1fr 2fr auto", gap: 10, marginTop: 14 }}>
          <input value={newPlatform} onChange={e => setNewPlatform(e.target.value)} placeholder="LinkedIn" style={inputStyle} />
          <input type="url" value={newUrl} onChange={e => setNewUrl(e.target.value)} placeholder="https://..." style={inputStyle} />
          <Btn type="submit" variant="green"><Plus size={14} /> Ajouter</Btn>
        </form>
      </section>
    </div>
  );
}
