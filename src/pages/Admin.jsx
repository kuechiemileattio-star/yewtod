import React, { useState } from "react";
import {
  ArrowLeft, LayoutGrid, FileText, BookOpen, Handshake, Image as ImageIcon,
  Settings, Archive, Filter, Plus, BarChart3, Inbox, Edit3, Trash2,
} from "lucide-react";
import { T } from "../theme.js";
import { WORKS, CATEGORIES, BOOKS, ADMIN_COLLABS } from "../data.js";
import NodeMark from "../components/NodeMark.jsx";
import Reveal from "../components/Reveal.jsx";
import Btn from "../components/Btn.jsx";
import Field, { inputStyle } from "../components/Field.jsx";
import StatusPill from "../components/StatusPill.jsx";
import AdminDashboard from "../components/AdminDashboard.jsx";

export default function Admin({ exitAdmin }) {
  const [tab, setTab] = useState("dashboard");
  const [posts, setPosts] = useState(WORKS.map(w => ({ ...w, statut: "Publié" })));
  const [collabFilter, setCollabFilter] = useState("Tous");
  const [draftTitle, setDraftTitle] = useState("");
  const [draftCat, setDraftCat] = useState(CATEGORIES[0]);

  const tabs = [
    ["dashboard", "Dashboard", LayoutGrid],
    ["posts", "Publications", FileText],
    ["books", "Livres", BookOpen],
    ["collabs", "Collaborations", Handshake],
    ["media", "Médias", ImageIcon],
    ["settings", "Paramètres", Settings],
  ];

  const filteredCollabs = collabFilter === "Tous" ? ADMIN_COLLABS : ADMIN_COLLABS.filter(c => c.statut === collabFilter);

  function addDraft(e) {
    e.preventDefault();
    if (!draftTitle.trim()) return;
    setPosts([{ id: "d" + Date.now(), title: draftTitle, category: draftCat, date: new Date().toISOString().slice(0, 10), author: "Yewtod", readTime: "—", tags: [], tone: T.green, statut: "Brouillon" }, ...posts]);
    setDraftTitle("");
  }

  return (
    <div className="ytd-admin-shell" style={{ display: "flex", minHeight: "100vh", background: T.paper }}>
      <aside style={{ width: 250, flexShrink: 0, borderRight: `1px solid ${T.line}`, padding: "28px 18px", position: "sticky", top: 0, height: "100vh", background: T.greenDeep }} className="ytd-admin-sidebar">
        <button onClick={exitAdmin} className="ytd-admin-back" style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 13, color: `${T.paper}AA`, marginBottom: 30 }}>
          <ArrowLeft size={14} /> Retour au site
        </button>
        <div style={{ display: "flex", alignItems: "center", gap: 9, marginBottom: 34, padding: "0 4px" }}>
          <NodeMark size={9} />
          <span style={{ fontFamily: "'DM Serif Display', serif", fontSize: 20, color: T.paper }}>Yewtod <i style={{ color: T.lime }}>SS</i></span>
          <span style={{ fontFamily: "'Space Mono', monospace", fontSize: 9.5, color: T.lime, border: `1px solid ${T.lime}66`, padding: "2px 6px", borderRadius: 10 }}>CMS</span>
        </div>
        {tabs.map(([key, label, Icon]) => (
          <button key={key} onClick={() => setTab(key)} className="ytd-admin-tab" style={{
            width: "100%", display: "flex", alignItems: "center", gap: 11, padding: "10px 12px", marginBottom: 4,
            background: tab === key ? T.lime : "transparent", border: "none", borderLeft: tab === key ? `3px solid ${T.paper}` : "3px solid transparent", borderRadius: 4, cursor: "pointer",
            fontFamily: "'Manrope', sans-serif", fontSize: 13, color: tab === key ? T.ink : `${T.paper}BB`, fontWeight: tab === key ? 800 : 600, textAlign: "left",
          }} aria-current={tab === key ? "page" : undefined}>
            <Icon size={16} /> {label}
          </button>
        ))}
      </aside>

      <main className="ytd-admin-main" style={{ flex: 1, padding: "28px 44px 70px", minWidth: 0 }}>
        <div className="ytd-admin-topbar">
          <div><span className="ytd-admin-kicker">Yewtod SS / CMS</span><strong>{tabs.find(([key]) => key === tab)?.[1]}</strong></div>
          <span className="ytd-admin-live"><span /> Système actif</span>
        </div>
        {tab === "dashboard" && <AdminDashboard posts={posts} onNewPost={() => setTab("posts")} onViewPosts={() => setTab("posts")} onViewCollabs={() => setTab("collabs")} onViewBooks={() => setTab("books")} />}
        {tab === "__legacy-dashboard" && (
          <div className="ytd-admin-view ytd-admin-view-dashboard">
            <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: 30, fontWeight: 500, margin: "0 0 6px" }}>Dashboard</h1>
            <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 32 }}>Vue d'ensemble de l'activité du site.</p>
            <div className="ytd-admin-welcome">
              <div>
                <span>ESPACE ÉDITORIAL</span>
                <h2>Donnez une forme claire à vos idées.</h2>
                <p>Suivez vos publications, vos lectures et les collaborations en un seul endroit.</p>
              </div>
              <button onClick={() => setTab("posts")}><Plus size={15} /> Nouvelle publication</button>
            </div>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 18, marginBottom: 40 }} className="ytd-stat-grid">
              {[
                ["Publications", posts.length, FileText],
                ["Brouillons", posts.filter(p => p.statut === "Brouillon").length, Edit3],
                ["Collaborations reçues", ADMIN_COLLABS.length, Inbox],
                ["Livres référencés", BOOKS.length, BookOpen],
              ].map(([label, val, Icon]) => (
                <div key={label} className="ytd-stat-card ytd-admin-panel" style={{ border: `1px solid ${T.line}`, padding: 20, background: T.paper }}>
                  <Icon size={16} color={T.green} />
                  <div style={{ fontFamily: "'Newsreader', serif", fontSize: 30, fontWeight: 600, margin: "10px 0 2px" }}>{val}</div>
                  <div style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: T.inkSoft }}>{label}</div>
                </div>
              ))}
            </div>

            <div style={{ display: "grid", gridTemplateColumns: "1.4fr 1fr", gap: 28 }} className="ytd-dash-grid">
              <div className="ytd-admin-panel ytd-admin-list-panel">
                <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 18, fontWeight: 600, marginBottom: 14 }}>Derniers articles</h3>
                {posts.slice(0, 5).map(p => (
                  <div key={p.id} style={{ display: "flex", justifyContent: "space-between", padding: "12px 0", borderBottom: `1px solid ${T.line}` }}>
                    <span style={{ fontFamily: "'Inter', sans-serif", fontSize: 14 }}>{p.title}</span>
                    <StatusPill statut={p.statut} />
                  </div>
                ))}
              </div>
              <div className="ytd-admin-panel ytd-admin-list-panel">
                <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 18, fontWeight: 600, marginBottom: 14 }}>Collaborations récentes</h3>
                {ADMIN_COLLABS.slice(0, 5).map(c => (
                  <div key={c.id} style={{ padding: "12px 0", borderBottom: `1px solid ${T.line}` }}>
                    <div style={{ display: "flex", justifyContent: "space-between" }}>
                      <span style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, fontWeight: 500 }}>{c.nom}</span>
                      <StatusPill statut={c.statut} />
                    </div>
                    <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft }}>{c.type}</span>
                  </div>
                ))}
              </div>
            </div>

            <Reveal className="ytd-admin-panel" style={{ marginTop: 40, border: `1px solid ${T.line}`, padding: 24, background: T.paper }}>
              <div style={{ display: "flex", alignItems: "center", gap: 8, marginBottom: 14 }}>
                <BarChart3 size={16} color={T.green} />
                <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 16, fontWeight: 600, margin: 0 }}>Statistiques simples — publications par catégorie</h3>
              </div>
              {CATEGORIES.map(c => {
                const count = WORKS.filter(w => w.category === c).length;
                return (
                  <div key={c} style={{ display: "flex", alignItems: "center", gap: 12, marginBottom: 8 }}>
                    <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, width: 190, color: T.inkSoft }}>{c}</span>
                    <div style={{ flex: 1, background: T.paperAlt, height: 8 }}>
                      <div className="ytd-bar-fill" style={{ width: `${(count / WORKS.length) * 100}%`, background: T.green, height: 8, transition: "width 1s cubic-bezier(.16,1,.3,1)" }} />
                    </div>
                    <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: T.ink }}>{count}</span>
                  </div>
                );
              })}
            </Reveal>
          </div>
        )}

        {tab === "posts" && (
          <div className="ytd-admin-view">
            <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: 30, fontWeight: 500, margin: "0 0 6px" }}>Publications</h1>
            <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 28 }}>Créer, modifier et publier articles, rapports, études, vidéos et notes.</p>

            <form onSubmit={addDraft} className="ytd-admin-panel ytd-admin-form" style={{ display: "flex", gap: 10, marginBottom: 30, flexWrap: "wrap", alignItems: "flex-end", border: `1px solid ${T.line}`, padding: 18, background: T.paper }}>
              <Field label="Titre du nouveau brouillon">
                <input value={draftTitle} onChange={e => setDraftTitle(e.target.value)} style={{ ...inputStyle, width: 320 }} placeholder="Titre de la publication" />
              </Field>
              <Field label="Catégorie">
                <select value={draftCat} onChange={e => setDraftCat(e.target.value)} style={{ ...inputStyle, width: 200 }}>
                  {CATEGORIES.map(c => <option key={c}>{c}</option>)}
                </select>
              </Field>
              <Btn type="submit" variant="green"><Plus size={15} /> Créer le brouillon</Btn>
            </form>

            <table style={{ width: "100%", borderCollapse: "collapse", fontFamily: "'Inter', sans-serif", fontSize: 14 }}>
              <thead>
                <tr style={{ borderBottom: `1px solid ${T.line}`, textAlign: "left" }}>
                  {["Titre", "Catégorie", "Date", "Statut", ""].map(h => (
                    <th key={h} style={{ padding: "10px 8px", fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft, textTransform: "uppercase", fontWeight: 500 }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {posts.map(p => (
                  <tr key={p.id} className="ytd-table-row" style={{ borderBottom: `1px solid ${T.line}` }}>
                    <td style={{ padding: "12px 8px" }}>{p.title}</td>
                    <td style={{ padding: "12px 8px", color: T.inkSoft }}>{p.category}</td>
                    <td style={{ padding: "12px 8px", color: T.inkSoft, fontFamily: "'IBM Plex Mono', monospace", fontSize: 12.5 }}>{p.date}</td>
                    <td style={{ padding: "12px 8px" }}><StatusPill statut={p.statut} /></td>
                    <td style={{ padding: "12px 8px", textAlign: "right" }}>
                      <Edit3 size={14} color={T.inkSoft} style={{ marginRight: 12, cursor: "pointer" }} />
                      <Trash2 size={14} color={T.inkSoft} style={{ cursor: "pointer" }} onClick={() => setPosts(posts.filter(x => x.id !== p.id))} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {tab === "books" && (
          <div className="ytd-admin-view">
            <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: 30, fontWeight: 500, margin: "0 0 6px" }}>Livres</h1>
            <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 28 }}>Gérer les fiches de la bibliothèque personnelle.</p>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(2, 1fr)", gap: 16 }} className="ytd-admin-books">
              {BOOKS.map(b => (
                <div key={b.id} style={{ border: `1px solid ${T.line}`, background: "#fff", padding: 16, display: "flex", gap: 14, alignItems: "center" }}>
                  <div style={{ width: 44, height: 60, background: T.paperAlt, flexShrink: 0, display: "flex", alignItems: "center", justifyContent: "center" }}><BookOpen size={16} color={b.tone} /></div>
                  <div style={{ flex: 1 }}>
                    <div style={{ fontFamily: "'Newsreader', serif", fontSize: 15, fontWeight: 600 }}>{b.title}</div>
                    <div style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft }}>{b.category} · {b.difficulty}</div>
                  </div>
                  <Edit3 size={14} color={T.inkSoft} style={{ cursor: "pointer" }} />
                </div>
              ))}
            </div>
          </div>
        )}

        {tab === "collabs" && (
          <div className="ytd-admin-view">
            <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: 30, fontWeight: 500, margin: "0 0 6px" }}>Collaborations</h1>
            <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 24 }}>Filtrer, changer le statut, archiver ou exporter les demandes reçues.</p>
            <div style={{ display: "flex", gap: 8, marginBottom: 22, alignItems: "center" }}>
              <Filter size={14} color={T.inkSoft} />
              {["Tous", "Nouveau", "En cours", "Archivé"].map(s => (
                <button key={s} onClick={() => setCollabFilter(s)} style={{
                  fontFamily: "'IBM Plex Mono', monospace", fontSize: 12, padding: "6px 12px", cursor: "pointer", borderRadius: 20,
                  border: `1px solid ${collabFilter === s ? T.ink : T.line}`, background: collabFilter === s ? T.ink : "transparent",
                  color: collabFilter === s ? T.paper : T.inkSoft,
                }}>{s}</button>
              ))}
              <span style={{ flex: 1 }} />
              <Btn variant="outline" style={{ fontSize: 12.5, padding: "8px 14px" }}><Archive size={13} /> Exporter</Btn>
            </div>
            <table style={{ width: "100%", borderCollapse: "collapse", fontFamily: "'Inter', sans-serif", fontSize: 14 }}>
              <thead>
                <tr style={{ borderBottom: `1px solid ${T.line}`, textAlign: "left" }}>
                  {["Nom", "Organisation", "Type", "Date", "Statut"].map(h => (
                    <th key={h} style={{ padding: "10px 8px", fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft, textTransform: "uppercase", fontWeight: 500 }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {filteredCollabs.map(c => (
                  <tr key={c.id} className="ytd-table-row" style={{ borderBottom: `1px solid ${T.line}` }}>
                    <td style={{ padding: "12px 8px" }}>{c.nom}</td>
                    <td style={{ padding: "12px 8px", color: T.inkSoft }}>{c.org}</td>
                    <td style={{ padding: "12px 8px", color: T.inkSoft }}>{c.type}</td>
                    <td style={{ padding: "12px 8px", color: T.inkSoft, fontFamily: "'IBM Plex Mono', monospace", fontSize: 12.5 }}>{c.date}</td>
                    <td style={{ padding: "12px 8px" }}><StatusPill statut={c.statut} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {tab === "media" && (
          <div className="ytd-admin-view">
            <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: 30, fontWeight: 500, margin: "0 0 6px" }}>Médias</h1>
            <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 28 }}>Bibliothèque d'images, PDF, vidéos et documents réutilisables dans les publications.</p>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(6, 1fr)", gap: 12 }} className="ytd-media-grid">
              {Array.from({ length: 12 }).map((_, i) => (
                <div key={i} style={{ aspectRatio: "1", border: `1px solid ${T.line}`, background: T.paperAlt, display: "flex", alignItems: "center", justifyContent: "center" }}>
                  <ImageIcon size={18} color={T.inkSoft} />
                </div>
              ))}
            </div>
            <Btn variant="outline" style={{ marginTop: 20 }}><Plus size={15} /> Importer un média</Btn>
          </div>
        )}

        {tab === "settings" && (
          <div className="ytd-admin-view">
            <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: 30, fontWeight: 500, margin: "0 0 6px" }}>Paramètres</h1>
            <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 28 }}>Informations du site, réseaux sociaux, SEO, menu, favicon et logo.</p>
            <div style={{ display: "flex", flexDirection: "column", gap: 20, maxWidth: 520 }}>
              <Field label="Nom du site"><input defaultValue="Yewtod SS" style={inputStyle} /></Field>
              <Field label="Description SEO"><textarea rows={3} style={{ ...inputStyle, resize: "vertical" }} defaultValue="Plateforme de réflexion sur les sciences sociales, les systèmes complexes et la politique publique." /></Field>
              <Field label="Réseaux sociaux"><input placeholder="X, LinkedIn, YouTube…" style={inputStyle} /></Field>
              <Btn variant="green" style={{ alignSelf: "flex-start" }}>Enregistrer les paramètres</Btn>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
