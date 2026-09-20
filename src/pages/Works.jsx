import React, { useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { Search } from "lucide-react";
import { T } from "../theme.js";
import { CATEGORIES, ARTICLE_THEMES, fmtDate } from "../lib/contentTypes.js";
import { workPath } from "../lib/paths.js";
import { useWorks } from "../hooks/useWorks.js";
import useDocumentMeta from "../hooks/useDocumentMeta.js";
import Reveal from "../components/Reveal.jsx";
import Cover from "../components/Cover.jsx";
import Tag from "../components/Tag.jsx";

const SORTS = {
  recent: { label: "Plus récent", fn: (a, b) => new Date(b.date || 0) - new Date(a.date || 0) },
  old: { label: "Plus ancien", fn: (a, b) => new Date(a.date || 0) - new Date(b.date || 0) },
  az: { label: "Titre (A–Z)", fn: (a, b) => a.title.localeCompare(b.title) },
};

function toggleInSet(set, value) {
  const next = new Set(set);
  if (next.has(value)) next.delete(value); else next.add(value);
  return next;
}

function FilterGroup({ title, children, right }) {
  return (
    <div className="ytd-works-filter-group">
      <div className="ytd-works-filter-group-head">
        <span>{title}</span>
        {right}
      </div>
      {children}
    </div>
  );
}

function CheckRow({ checked, onChange, label, count, disabled }) {
  return (
    <label className={`ytd-works-check-row ${disabled ? "is-disabled" : ""}`}>
      <input type="checkbox" checked={checked} onChange={onChange} disabled={disabled} />
      <span>{label}</span>
      {count != null && <em>{count}</em>}
    </label>
  );
}

export default function Works() {
  const navigate = useNavigate();
  const { works, loading } = useWorks();
  useDocumentMeta("Publications", "Articles, rapports, études, notes de recherche, séries documentaires, expérimentations et visualisations de données publiés par Yewtod SS.");
  const [searchParams, setSearchParams] = useSearchParams();
  const categorieParam = searchParams.get("categorie");
  const [types, setTypes] = useState(() => new Set(categorieParam && CATEGORIES.includes(categorieParam) ? [categorieParam] : []));
  const [themes, setThemes] = useState(new Set());
  const [series, setSeries] = useState(new Set());
  const [author, setAuthor] = useState("Tous");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [query, setQuery] = useState("");
  const [sort, setSort] = useState("recent");

  function toggleType(label) {
    setTypes(prev => toggleInSet(prev, label));
    setSearchParams(prev => { const p = new URLSearchParams(prev); p.delete("categorie"); return p; }, { replace: true });
  }

  const seriesOptions = works.filter(w => w.table === "documentary_series");
  const authors = [...new Set(works.map(w => w.author).filter(Boolean))].sort((a, b) => a.localeCompare(b));

  const q = query.trim().toLowerCase();
  const filtered = works
    .filter(w => types.size === 0 || types.has(w.category))
    .filter(w => themes.size === 0 || [...themes].some(t => (w.themes || "").split("\n").includes(t)))
    .filter(w => series.size === 0 || (w.table === "documentary_episodes" && [...series].some(s => seriesOptions.find(so => so.title === s)?.id === w.seriesId)))
    .filter(w => author === "Tous" || w.author === author)
    .filter(w => !dateFrom || new Date(w.date || 0) >= new Date(dateFrom))
    .filter(w => !dateTo || new Date(w.date || 0) <= new Date(dateTo))
    .filter(w => !q || `${w.title} ${w.excerpt} ${w.tags || ""}`.toLowerCase().includes(q))
    .sort(SORTS[sort].fn);

  const openWork = work => navigate(workPath(work.routeSlug, work.slug));
  const countFor = label => works.filter(w => w.category === label).length;
  const countForTheme = theme => works.filter(w => (w.themes || "").split("\n").includes(theme)).length;

  return (
    <div>
      <div className="ytd-works-hero-band">
        <p className="ytd-works-breadcrumb">Accueil › Publications</p>
        <h1>Publications</h1>
        <p>{works.length} analyses, rapports et travaux — la bibliothèque de réflexion de Yewtod SS.</p>
      </div>

      <div className="ytd-works-pills-band">
        <button className={types.size === 0 ? "is-active" : ""} onClick={() => setTypes(new Set())}>
          Toutes <em>{works.length}</em>
        </button>
        {CATEGORIES.map(c => (
          <button key={c} className={types.has(c) ? "is-active" : ""} onClick={() => toggleType(c)}>
            {c} <em>{countFor(c)}</em>
          </button>
        ))}
      </div>

      <div className="ytd-works-layout">
        <aside className="ytd-works-sidebar">
          <div className="ytd-works-sidebar-head">
            <span>Filtrer</span>
            <button type="button" onClick={() => { setTypes(new Set()); setThemes(new Set()); setSeries(new Set()); setAuthor("Tous"); setDateFrom(""); setDateTo(""); setQuery(""); }}>
              Réinitialiser
            </button>
          </div>

          <FilterGroup title="Recherche">
            <label className="ytd-works-search">
              <Search size={14} color={T.inkSoft} />
              <input value={query} onChange={e => setQuery(e.target.value)} placeholder="Mot-clé, auteur, tag…" />
            </label>
          </FilterGroup>

          <FilterGroup title="Axe thématique">
            {ARTICLE_THEMES.map(t => (
              <CheckRow key={t} checked={themes.has(t)} onChange={() => setThemes(prev => toggleInSet(prev, t))} label={t} count={countForTheme(t)} />
            ))}
          </FilterGroup>

          <FilterGroup title="Type">
            {CATEGORIES.map(c => (
              <CheckRow key={c} checked={types.has(c)} onChange={() => toggleType(c)} label={c} count={countFor(c)} />
            ))}
          </FilterGroup>

          <FilterGroup title="Pays">
            <p className="ytd-works-filter-empty">Bientôt disponible — Yewtod SS ne renseigne pas encore le pays par publication.</p>
          </FilterGroup>

          <FilterGroup title="Langue">
            <CheckRow checked disabled label="Français" />
            <p className="ytd-works-filter-empty">Toutes les publications sont en français pour l'instant.</p>
          </FilterGroup>

          <FilterGroup title="Série / Collection">
            {seriesOptions.length > 0 ? seriesOptions.map(s => (
              <CheckRow key={s.id} checked={series.has(s.title)} onChange={() => setSeries(prev => toggleInSet(prev, s.title))} label={s.title} />
            )) : <p className="ytd-works-filter-empty">Aucune série documentaire publiée pour l'instant.</p>}
          </FilterGroup>

          <FilterGroup title="Auteur">
            <select value={author} onChange={e => setAuthor(e.target.value)} className="ytd-works-select">
              <option value="Tous">Tous les auteurs</option>
              {authors.map(a => <option key={a} value={a}>{a}</option>)}
            </select>
          </FilterGroup>

          <FilterGroup title="Période">
            <div className="ytd-works-date-range">
              <label>Du <input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} /></label>
              <label>Au <input type="date" value={dateTo} onChange={e => setDateTo(e.target.value)} /></label>
            </div>
          </FilterGroup>
        </aside>

        <main className="ytd-works-main">
          <div className="ytd-works-results-bar">
            <span>{loading ? "Chargement…" : `${filtered.length} résultat${filtered.length === 1 ? "" : "s"} sur ${works.length}`}</span>
            <label>
              Trier par
              <select value={sort} onChange={e => setSort(e.target.value)}>
                {Object.entries(SORTS).map(([key, s]) => <option key={key} value={key}>{s.label}</option>)}
              </select>
            </label>
          </div>

          {loading ? (
            <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft }}>Chargement…</p>
          ) : (
            <div className="ytd-works-grid">
              {filtered.map((w, i) => (
                <Reveal key={w.id} delay={(i % 4) * 60} as="div">
                  <article onClick={() => openWork(w)} onKeyDown={e => e.key === "Enter" && openWork(w)} className="ytd-works-card" role="link" tabIndex={0}>
                    <Cover tone={w.tone} label={w.category} image={w.coverImage} />
                    <div className="ytd-works-card-body">
                      <span className="ytd-works-card-axis">{(w.themes || "").split("\n").filter(Boolean)[0] || w.category}</span>
                      <h3>{w.title}</h3>
                      {w.excerpt && <p>{w.excerpt.length > 140 ? `${w.excerpt.slice(0, 140).trimEnd()}…` : w.excerpt}</p>}
                      <div className="ytd-works-card-meta">
                        <span className="ytd-works-card-avatar">{(w.author || "Y").charAt(0)}</span>
                        <span>{w.author}</span>
                        <span>·</span>
                        <span>{fmtDate(w.date)}</span>
                        {w.readTime && <><span>·</span><span>{w.readTime}</span></>}
                      </div>
                      <div className="ytd-works-card-tags">
                        {(w.tags || "").split("\n").filter(Boolean).slice(0, 3).map(tag => <Tag key={tag}>{tag}</Tag>)}
                      </div>
                    </div>
                  </article>
                </Reveal>
              ))}
              {filtered.length === 0 && <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft }}>Aucune publication ne correspond à ces filtres.</p>}
            </div>
          )}
        </main>
      </div>
    </div>
  );
}
