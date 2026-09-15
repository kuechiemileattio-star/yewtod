import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { FileText, Newspaper, BarChart3, BookOpen, Plus, Eye } from "lucide-react";
import { T } from "../../theme.js";
import { supabase } from "../../lib/supabaseClient.js";
import { fmtDate } from "../../lib/contentTypes.js";
import { SIMPLE_WORK_TYPES } from "../../lib/simpleWorkTypes.js";
import { useAuth } from "../../contexts/AuthContext.jsx";
import { useAdminWorks } from "../../hooks/useAdminWorks.js";
import { useAdminBooks } from "../../hooks/useAdminBooks.js";
import StatusPill from "../../components/StatusPill.jsx";
import Btn from "../../components/Btn.jsx";
import MonthlyChart from "../../components/dashboard/MonthlyChart.jsx";

const STATUS_LABELS = { draft: "Brouillon", published: "Publié", scheduled: "Programmé" };
const MONTH_ABBR = ["Jan", "Fév", "Mars", "Avr", "Mai", "Juin", "Juil", "Août", "Sept", "Oct", "Nov", "Déc"];
const MONTHS_BACK = 12;
const ALL_TABLES = ["reports", "articles", "studies", "research_notes", "documentary_series", "documentary_episodes", "experiments", "data_visualizations", "books"];

const HIGHLIGHT_TYPES = [
  { table: "reports", label: "Rapports publiés", icon: FileText },
  { table: "articles", label: "Articles publiés", icon: Newspaper },
  { table: "data_visualizations", label: "Visualisations publiées", icon: BarChart3 },
  { table: "books", label: "Livres publiés", icon: BookOpen },
];

function lastNMonths(n) {
  const months = [];
  const now = new Date();
  for (let i = n - 1; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    months.push({ key: `${d.getFullYear()}-${d.getMonth()}`, label: MONTH_ABBR[d.getMonth()] });
  }
  return months;
}

async function countPublished(table, sinceIso) {
  let query = supabase.from(table).select("*", { count: "exact", head: true }).eq("status", "published");
  if (sinceIso) query = query.gte("published_at", sinceIso);
  const { count } = await query;
  return count || 0;
}

async function fetchPublishedDates(table, sinceIso) {
  const { data } = await supabase.from(table).select("published_at").eq("status", "published").gte("published_at", sinceIso);
  return (data || []).map(r => r.published_at);
}

export default function Overview() {
  const { profile, hasPermission } = useAuth();
  const navigate = useNavigate();
  const { works } = useAdminWorks();
  const { books } = useAdminBooks();
  const [stats, setStats] = useState(null);
  const [chartData, setChartData] = useState(null);
  const [topViewed, setTopViewed] = useState(null);
  const [topViewedError, setTopViewedError] = useState(false);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const startOfMonth = new Date();
      startOfMonth.setDate(1);
      startOfMonth.setHours(0, 0, 0, 0);
      const monthIso = startOfMonth.toISOString();

      const highlightStats = await Promise.all(HIGHLIGHT_TYPES.map(async t => ({
        table: t.table,
        total: await countPublished(t.table),
        thisMonth: await countPublished(t.table, monthIso),
      })));
      if (!cancelled) setStats(Object.fromEntries(highlightStats.map(s => [s.table, s])));

      const months = lastNMonths(MONTHS_BACK);
      const windowStart = new Date();
      windowStart.setMonth(windowStart.getMonth() - (MONTHS_BACK - 1));
      windowStart.setDate(1);
      windowStart.setHours(0, 0, 0, 0);
      const allDates = (await Promise.all(ALL_TABLES.map(t => fetchPublishedDates(t, windowStart.toISOString())))).flat();
      const counts = Object.fromEntries(months.map(m => [m.key, 0]));
      allDates.forEach(dateStr => {
        const d = new Date(dateStr);
        const key = `${d.getFullYear()}-${d.getMonth()}`;
        if (key in counts) counts[key]++;
      });
      if (!cancelled) setChartData(months.map(m => ({ label: m.label, count: counts[m.key] })));

      try {
        const since = new Date(Date.now() - 7 * 24 * 3600 * 1000).toISOString();
        const { data, error } = await supabase.rpc("get_top_viewed", { since, limit_count: 3 });
        if (error) throw error;
        if (!cancelled) setTopViewed(data || []);
      } catch {
        if (!cancelled) setTopViewedError(true);
      }
    })();
    return () => { cancelled = true; };
  }, []);

  const recent = [...works, ...books.map(b => ({ ...b, table: "books", category: "Livres" }))]
    .sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0))
    .slice(0, 6);

  function openRecent(item) {
    const type = SIMPLE_WORK_TYPES[item.table];
    if (type) navigate(`/dashboard/${type.adminPath}/${item.id}`);
    else if (item.table === "books") navigate("/dashboard/books");
    else navigate("/dashboard/publications");
  }

  return (
    <div className="ytd-dashboard-new ytd-admin-view">
      <div className="ytd-admin-section-heading">
        <div><span className="ytd-admin-kicker">Vue d'ensemble</span><h1>Bonjour, {profile?.full_name || "membre"}.</h1><p>Voici ce qui se passe sur la plateforme aujourd'hui.</p></div>
        {hasPermission("manage_articles") && <Btn variant="green" onClick={() => navigate("/dashboard/rapports/nouveau")}><Plus size={15} /> Nouveau contenu</Btn>}
      </div>

      <div className="ytd-dashboard-stat-grid">
        {HIGHLIGHT_TYPES.map((t, index) => {
          const s = stats?.[t.table];
          const Icon = t.icon;
          return (
            <article key={t.table} className="ytd-dashboard-stat" style={{ animationDelay: `${index * 70}ms` }}>
              <div className="ytd-dashboard-stat-top">
                <Icon size={18} color={T.green} />
                {s && s.thisMonth > 0 && <span className="ytd-dashboard-stat-delta">+{s.thisMonth} ce mois</span>}
              </div>
              <strong>{s ? s.total : "…"}</strong>
              <small>{t.label}</small>
            </article>
          );
        })}
      </div>

      <div className="ytd-dashboard-mid-grid">
        <div className="ytd-dashboard-card" style={{ animationDelay: "120ms" }}>
          <span className="ytd-admin-kicker">Publications par mois</span>
          {chartData ? <MonthlyChart data={chartData} /> : <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>Chargement…</p>}
        </div>

        <div className="ytd-dashboard-card" style={{ animationDelay: "180ms" }}>
          <span className="ytd-admin-kicker">Le plus consulté cette semaine</span>
          {topViewedError ? (
            <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 12.5, lineHeight: 1.6, marginTop: 10 }}>
              Le suivi des vues n'est pas encore actif : exécute la migration 009_content_views.sql dans Supabase.
            </p>
          ) : !topViewed ? (
            <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>Chargement…</p>
          ) : topViewed.length === 0 ? (
            <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>Aucune vue enregistrée cette semaine.</p>
          ) : (
            <ol className="ytd-dashboard-ranked-list">
              {topViewed.map((item, i) => (
                <li key={`${item.table_name}-${item.content_id}`}>
                  <span className="ytd-dashboard-rank">{i + 1}</span>
                  <div>
                    <strong>{item.title}</strong>
                    <span><Eye size={11} /> {item.views} vue{item.views > 1 ? "s" : ""}</span>
                  </div>
                </li>
              ))}
            </ol>
          )}
        </div>
      </div>

      <div className="ytd-dashboard-card" style={{ animationDelay: "240ms" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline" }}>
          <span className="ytd-admin-kicker">Contenu récent</span>
        </div>
        <div className="ytd-admin-table-scroll">
          <table className="ytd-admin-table">
            <thead><tr><th>Titre</th><th>Type</th><th>Statut</th><th>Date</th><th>Auteur</th></tr></thead>
            <tbody>
              {recent.map(item => (
                <tr key={`${item.table}-${item.id}`} onClick={() => openRecent(item)} style={{ cursor: "pointer" }}>
                  <td>{item.title}</td>
                  <td>{item.category}</td>
                  <td><StatusPill statut={STATUS_LABELS[item.status]} /></td>
                  <td>{fmtDate(item.publishedAt || item.createdAt)}</td>
                  <td>{item.author || item.authorName || "—"}</td>
                </tr>
              ))}
              {recent.length === 0 && <tr><td colSpan={5} style={{ color: T.inkSoft }}>Rien à afficher pour l'instant.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
