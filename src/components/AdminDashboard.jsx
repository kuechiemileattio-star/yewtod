import React from "react";
import { ArrowUpRight, BarChart3, BookOpen, FileText, Inbox, Plus, TrendingUp } from "lucide-react";
import { T } from "../theme.js";
import { ADMIN_COLLABS, CATEGORIES, WORKS } from "../data.js";
import StatusPill from "./StatusPill.jsx";
import Cover from "./Cover.jsx";

export default function AdminDashboard({ posts, onNewPost, onViewPosts, onViewCollabs, onViewBooks, onViewPost }) {
  const stats = [
    ["Publications", posts.length, FileText, "+12% ce mois"],
    ["Brouillons", posts.filter(post => post.statut === "Brouillon").length, TrendingUp, "À finaliser"],
    ["Collaborations", ADMIN_COLLABS.length, Inbox, "5 demandes actives"],
    ["Livres référencés", 9, BookOpen, "Bibliothèque à jour"],
  ];

  return (
    <section className="ytd-dashboard-new ytd-admin-view">
      <div className="ytd-dashboard-hero">
        <div>
          <span className="ytd-dashboard-eyebrow">Vue éditoriale · 19 août 2026</span>
          <h1>Bonjour, Yewtod.</h1>
          <p>Voici ce qui se passe dans votre espace de recherche et de publication.</p>
        </div>
        <button className="ytd-dashboard-action" onClick={onNewPost}><Plus size={16} /> Nouvelle publication</button>
      </div>

      <div className="ytd-dashboard-stat-grid">
        {stats.map(([label, value, Icon, note], index) => (
          <article className="ytd-dashboard-stat" key={label} style={{ animationDelay: `${index * 70}ms` }}>
            <div className="ytd-dashboard-stat-top"><span>{label}</span><Icon size={18} /></div>
            <strong>{value}</strong>
            <small>{note}</small>
          </article>
        ))}
      </div>

      <div className="ytd-dashboard-quick-actions">
        <span>Actions rapides</span>
        <button onClick={onNewPost}><Plus size={14} /> Créer un brouillon</button>
        <button onClick={onViewCollabs}><Inbox size={14} /> Voir les demandes</button>
        <button onClick={onViewBooks}><BookOpen size={14} /> Ouvrir la bibliothèque</button>
      </div>

      <div className="ytd-dashboard-grid">
        <section className="ytd-dashboard-panel ytd-dashboard-publications">
          <div className="ytd-dashboard-panel-heading"><div><span>Flux éditorial</span><h2>Dernières publications</h2></div><button onClick={onViewPosts}>Voir tout <ArrowUpRight size={14} /></button></div>
          <div className="ytd-dashboard-post-list ytd-dashboard-post-grid">
            {posts.slice(0, 5).map((post, index) => (
              <article className="ytd-dashboard-post" key={post.id} style={{ animationDelay: `${index * 70}ms` }} tabIndex={0} role="button" onClick={() => onViewPost(post)} onKeyDown={event => (event.key === "Enter" || event.key === " ") && onViewPost(post)}>
                <Cover tone={post.tone} label={post.category} title={post.title} image={post.coverImage} />
                <div className="ytd-dashboard-post-meta"><small>{post.date}</small><StatusPill statut={post.statut} /></div>
              </article>
            ))}
          </div>
        </section>

        <section className="ytd-dashboard-panel ytd-dashboard-collabs">
          <div className="ytd-dashboard-panel-heading"><div><span>À traiter</span><h2>Collaborations</h2></div><Inbox size={18} color={T.green} /></div>
          <div className="ytd-dashboard-collab-list">
            {ADMIN_COLLABS.slice(0, 4).map(collab => (
              <div className="ytd-dashboard-collab" key={collab.id}><div className="ytd-dashboard-avatar">{collab.nom.slice(0, 1)}</div><div><strong>{collab.nom}</strong><small>{collab.type}</small></div><StatusPill statut={collab.statut} /></div>
            ))}
          </div>
        </section>
      </div>

      <section className="ytd-dashboard-panel ytd-dashboard-chart">
        <div className="ytd-dashboard-panel-heading"><div><span>Répartition</span><h2>Votre activité par catégorie</h2></div><BarChart3 size={19} color={T.green} /></div>
        <div className="ytd-dashboard-bars">
          {CATEGORIES.map(category => {
            const count = WORKS.filter(work => work.category === category).length;
            return <div className="ytd-dashboard-bar-row" key={category}><span>{category}</span><div><i style={{ width: `${Math.max(12, (count / WORKS.length) * 100)}%` }} /></div><strong>{count}</strong></div>;
          })}
        </div>
      </section>
    </section>
  );
}
