import React, { useState } from "react";
import {
  ArrowLeft, LayoutGrid, FileText, BookOpen, Handshake, Image as ImageIcon,
  Settings, Archive, Filter, Plus, BarChart3, Inbox, Edit3, Trash2, Search,
} from "lucide-react";
import { T } from "../theme.js";
import { WORKS, CATEGORIES, BOOKS, BOOK_CATEGORIES, BOOK_COVERS, ADMIN_COLLABS } from "../data.js";
import NodeMark from "../components/NodeMark.jsx";
import BrandLogo from "../components/BrandLogo.jsx";
import Reveal from "../components/Reveal.jsx";
import Btn from "../components/Btn.jsx";
import Field, { inputStyle } from "../components/Field.jsx";
import StatusPill from "../components/StatusPill.jsx";
import AdminDashboard from "../components/AdminDashboard.jsx";
import BookEditorModal from "../components/BookEditorModal.jsx";
import BookPreviewModal from "../components/BookPreviewModal.jsx";
import PostEditorModal from "../components/PostEditorModal.jsx";

const ROLE_PERMISSIONS = {
  Propriétaire: ["Tout gérer", "Gérer les membres", "Modifier les paramètres", "Publier et supprimer"],
  Administrateur: ["Gérer les membres", "Modifier les paramètres", "Publier et supprimer"],
  Développeur: ["Gérer les médias", "Modifier les paramètres techniques", "Consulter les contenus"],
  Éditeur: ["Créer et modifier", "Publier les contenus", "Gérer les livres"],
  Lecteur: ["Consulter le dashboard", "Consulter les contenus"],
};

const ROLE_DESCRIPTIONS = {
  Propriétaire: "Accès complet au projet et à l’équipe.",
  Administrateur: "Gère le contenu, les réglages et les membres.",
  Développeur: "Intervient sur les médias et les réglages techniques.",
  Éditeur: "Produit et publie les contenus éditoriaux.",
  Lecteur: "Accès en lecture seule aux espaces autorisés.",
};

export default function Admin({ exitAdmin, visitorReviews = {} }) {
  const [tab, setTab] = useState("dashboard");
  const [posts, setPosts] = useState(WORKS.map(w => ({ ...w, statut: "Publié" })));
  const [collabFilter, setCollabFilter] = useState("Tous");
  const [draftTitle, setDraftTitle] = useState("");
  const [draftCat, setDraftCat] = useState(CATEGORIES[0]);
  const [isPostComposerOpen, setIsPostComposerOpen] = useState(false);
  const [editingPost, setEditingPost] = useState(null);
  const [books, setBooks] = useState(BOOKS);
  const [bookFilter, setBookFilter] = useState("Toutes");
  const [bookSearch, setBookSearch] = useState("");
  const [editingBook, setEditingBook] = useState(null);
  const [previewBook, setPreviewBook] = useState(null);
  const [selectedCollab, setSelectedCollab] = useState(null);
  const [siteName, setSiteName] = useState("Yewtod SS");
  const [siteDescription, setSiteDescription] = useState("Plateforme de réflexion sur les sciences sociales, les systèmes complexes et la politique publique.");
  const [newMemberEmail, setNewMemberEmail] = useState("");
  const [newMemberRole, setNewMemberRole] = useState("Éditeur");
  const [teamMembers, setTeamMembers] = useState([{ id: "m1", name: "Yewtod", email: "owner@yewtod.ss", role: "Propriétaire", status: "Actif" }]);

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
    setIsPostComposerOpen(false);
  }

  function savePost(e) {
    e.preventDefault();
    setPosts(posts.map(post => post.id === editingPost.id ? editingPost : post));
    setEditingPost(null);
  }

  function saveBook(e) {
    e.preventDefault();
    setBooks(books.some(book => book.id === editingBook.id) ? books.map(book => book.id === editingBook.id ? editingBook : book) : [editingBook, ...books]);
    setEditingBook(null);
  }

  function addMember(e) {
    e.preventDefault();
    if (!newMemberEmail.trim() || teamMembers.some(member => member.email === newMemberEmail.trim())) return;
    setTeamMembers([...teamMembers, { id: "m" + Date.now(), name: newMemberEmail.split("@")[0], email: newMemberEmail.trim(), role: newMemberRole, status: "Invitation envoyée" }]);
    setNewMemberEmail("");
  }

  return (
    <div className="ytd-admin-shell" style={{ display: "flex", minHeight: "100vh", background: T.paper }}>
      <aside style={{ width: 250, flexShrink: 0, borderRight: `1px solid ${T.line}`, padding: "28px 18px", position: "sticky", top: 0, height: "100vh", background: T.greenDeep }} className="ytd-admin-sidebar">
        <button onClick={exitAdmin} className="ytd-admin-back" style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 13, color: `${T.paper}AA`, marginBottom: 30 }}>
          <ArrowLeft size={14} /> Retour au site
        </button>
        <div style={{ display: "flex", alignItems: "center", gap: 9, marginBottom: 34, padding: "0 4px" }}>
          <BrandLogo dark />
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
        <nav className="ytd-admin-mobile-tabs" aria-label="Navigation du CMS">
          {tabs.map(([key, label, Icon]) => (
            <button
              key={key}
              onClick={() => setTab(key)}
              className="ytd-admin-mobile-tab"
              aria-current={tab === key ? "page" : undefined}
            >
              <Icon size={15} />
              {label}
            </button>
          ))}
        </nav>
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

            <div className="ytd-admin-add-bar"><span>Ajouter un contenu éditorial</span><Btn variant="green" onClick={() => setIsPostComposerOpen(true)}><Plus size={15} /> Nouvelle publication</Btn></div>

            {(isPostComposerOpen || editingPost) && <PostEditorModal post={editingPost} draftTitle={draftTitle} draftCat={draftCat} onChangePost={setEditingPost} onChangeDraft={setDraftTitle} onSave={editingPost ? savePost : addDraft} onClose={() => { setIsPostComposerOpen(false); setEditingPost(null); }} />}

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
                      <Edit3 size={14} color={T.inkSoft} style={{ marginRight: 12, cursor: "pointer" }} onClick={() => setEditingPost({ ...p })} />
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
            <div className="ytd-admin-section-heading"><div><span className="ytd-admin-kicker">Bibliothèque éditoriale</span><h1>Livres</h1><p>Organiser les références, enrichir les avis et préparer les fiches publiques.</p></div><Btn variant="green" onClick={() => setEditingBook({ id: "b" + Date.now(), title: "", author: "", category: BOOK_CATEGORIES[0], difficulty: "Accessible", description: "", note: "", reviews: [], link: "", tone: T.green })}><Plus size={15} /> Ajouter un livre</Btn></div>
            <div className="ytd-admin-book-toolbar">
              <label className="ytd-admin-search"><Search size={15} /><input value={bookSearch} onChange={e => setBookSearch(e.target.value)} placeholder="Rechercher un livre ou un auteur" /></label>
              <div className="ytd-admin-filter-scroll">{["Toutes", ...BOOK_CATEGORIES].map(category => <button key={category} onClick={() => setBookFilter(category)} className={bookFilter === category ? "is-active" : ""}>{category}</button>)}</div>
            </div>
            <div className="ytd-admin-books">
              {books.filter(book => (bookFilter === "Toutes" || book.category === bookFilter) && `${book.title} ${book.author}`.toLowerCase().includes(bookSearch.toLowerCase())).map((book, index) => <article key={book.id} className="ytd-admin-book-card" style={{ animationDelay: `${index * 55}ms` }} onClick={() => setPreviewBook({ ...book, cover: book.cover || BOOK_COVERS[book.title] || "", reviews: visitorReviews[book.id] || [] })} onKeyDown={event => event.key === "Enter" && setPreviewBook({ ...book, cover: book.cover || BOOK_COVERS[book.title] || "", reviews: visitorReviews[book.id] || [] })} role="button" tabIndex={0}><div className="ytd-admin-book-cover" style={{ background: `linear-gradient(145deg, ${book.tone}, ${T.greenDeep})` }}><img src={book.cover || BOOK_COVERS[book.title]} alt={`Couverture de ${book.title}`} onError={e => { e.currentTarget.style.display = "none"; }} /><BookOpen size={22} /></div><div className="ytd-admin-book-copy"><span>{book.category} · {book.difficulty}</span><h2>{book.title}</h2><small>{book.author}</small><p>{book.note}</p></div><button className="ytd-admin-icon-button" onClick={event => { event.stopPropagation(); setEditingBook({ ...book, cover: book.cover || BOOK_COVERS[book.title] || "" }); }} aria-label={`Modifier ${book.title}`}><Edit3 size={16} /></button></article>)}
            </div>
            {editingBook && <BookEditorModal book={editingBook} onChange={setEditingBook} onSave={saveBook} onClose={() => setEditingBook(null)} />}
            {previewBook && <BookPreviewModal book={previewBook} reviews={visitorReviews[previewBook.id] || []} onEdit={() => { setEditingBook(previewBook); setPreviewBook(null); }} onClose={() => setPreviewBook(null)} />}
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
                    <td style={{ padding: "12px 8px" }}><button onClick={() => setSelectedCollab(c)} style={{ border: 0, padding: 0, background: "none", color: T.ink, cursor: "pointer", font: "inherit", textDecoration: "underline" }}>{c.nom}</button></td>
                    <td style={{ padding: "12px 8px", color: T.inkSoft }}>{c.org}</td>
                    <td style={{ padding: "12px 8px", color: T.inkSoft }}>{c.type}</td>
                    <td style={{ padding: "12px 8px", color: T.inkSoft, fontFamily: "'IBM Plex Mono', monospace", fontSize: 12.5 }}>{c.date}</td>
                    <td style={{ padding: "12px 8px" }}><StatusPill statut={c.statut} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
            {selectedCollab && <div className="ytd-admin-panel" style={{ marginTop: 24, padding: 22, border: `1px solid ${T.green}`, background: T.paper }}>
              <div style={{ display: "flex", justifyContent: "space-between", gap: 16, alignItems: "start" }}>
                <div><span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10, color: T.green, textTransform: "uppercase" }}>Fiche de collaboration</span><h2 style={{ fontFamily: "'Newsreader', serif", fontSize: 24, margin: "7px 0 4px" }}>{selectedCollab.nom}</h2><p style={{ margin: 0, color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>{selectedCollab.org} · {selectedCollab.type} · {selectedCollab.date}</p></div>
                <button onClick={() => setSelectedCollab(null)} style={{ border: 0, background: "none", cursor: "pointer", color: T.inkSoft }}>Fermer</button>
              </div>
              <p style={{ fontFamily: "'Newsreader', serif", fontSize: 18, lineHeight: 1.55, marginBottom: 0 }}>Cette demande peut maintenant être consultée depuis l'administration. Les notes internes et les prochaines étapes pourront être ajoutées ici.</p>
            </div>}
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
          <div className="ytd-admin-view ytd-admin-settings">
            <div className="ytd-admin-section-heading"><div><span className="ytd-admin-kicker">Configuration du projet</span><h1>Paramètres</h1><p>Organiser l’identité du site, ses accès et les responsabilités de l’équipe.</p></div></div>
            <section className="ytd-admin-settings-card"><div className="ytd-admin-settings-card-heading"><div><span>Identité éditoriale</span><h2>Informations du site</h2></div><span className="ytd-admin-settings-index">01</span></div><div className="ytd-admin-settings-fields"><Field label="Nom du site"><input value={siteName} onChange={e => setSiteName(e.target.value)} style={inputStyle} /></Field><Field label="Description SEO"><textarea rows={3} value={siteDescription} onChange={e => setSiteDescription(e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field><Field label="Réseaux sociaux"><input placeholder="X, LinkedIn, YouTube…" style={inputStyle} /></Field></div><Btn type="button" variant="green" style={{ alignSelf: "flex-start" }}>Enregistrer les paramètres</Btn></section>
            <div>
              <section className="ytd-admin-team-section">
                <div className="ytd-admin-team-heading"><div><span className="ytd-admin-kicker">Organisation</span><h2>Équipe et accès</h2><p>Inviter des collaborateurs et contrôler les actions disponibles selon leur rôle.</p></div><span className="ytd-admin-team-count">{teamMembers.length} membre{teamMembers.length > 1 ? "s" : ""}</span></div>
                <div className="ytd-admin-role-legend">{Object.entries(ROLE_DESCRIPTIONS).map(([role, description]) => <div key={role}><strong>{role}</strong><span>{description}</span></div>)}</div>
                <div className="ytd-admin-members">{teamMembers.map(member => <article key={member.id} className="ytd-admin-member"><div className="ytd-admin-member-avatar">{member.name.slice(0, 1).toUpperCase()}</div><div className="ytd-admin-member-main"><strong>{member.name}</strong><small>{member.email}</small><div className="ytd-admin-permissions">{ROLE_PERMISSIONS[member.role].map(permission => <span key={permission}>{permission}</span>)}</div></div><div className="ytd-admin-member-actions"><select value={member.role} onChange={e => setTeamMembers(teamMembers.map(item => item.id === member.id ? { ...item, role: e.target.value } : item))} disabled={member.role === "Propriétaire"}>{Object.keys(ROLE_PERMISSIONS).map(role => <option key={role}>{role}</option>)}</select><span className="ytd-admin-member-status">{member.status}</span></div></article>)}</div>
                <form onSubmit={addMember} className="ytd-admin-invite-form"><input required type="email" value={newMemberEmail} onChange={e => setNewMemberEmail(e.target.value)} placeholder="email@exemple.com" style={inputStyle} /><select value={newMemberRole} onChange={e => setNewMemberRole(e.target.value)} style={inputStyle}>{Object.keys(ROLE_PERMISSIONS).filter(role => role !== "Propriétaire").map(role => <option key={role}>{role}</option>)}</select><Btn type="submit" variant="green"><Plus size={14} /> Inviter</Btn></form>
              </section>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
