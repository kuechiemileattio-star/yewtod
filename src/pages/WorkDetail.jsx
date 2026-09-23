import React, { useState } from "react";
import { useNavigate, useParams, Link } from "react-router-dom";
import { ArrowLeft, Download, Share2, Eye, Quote, Facebook, Linkedin, Twitter, Home, ChevronRight, FileText, Code2 } from "lucide-react";
import { T } from "../theme.js";
import { fmtDate, getTypeByRouteSlug } from "../lib/contentTypes.js";
import { workPath, PATHS } from "../lib/paths.js";
import { useWork, useWorks } from "../hooks/useWorks.js";
import useDocumentMeta from "../hooks/useDocumentMeta.js";
import useLogView, { useViewCount, useDownloadCount, logDownload } from "../hooks/useLogView.js";
import Divider from "../components/Divider.jsx";
import Reveal from "../components/Reveal.jsx";
import Cover from "../components/Cover.jsx";
import SectionLabel from "../components/SectionLabel.jsx";
import Breadcrumb from "../components/Breadcrumb.jsx";
import { Pill, InteractiveSommaire } from "../components/work-details/shared.jsx";
import { WORK_DETAIL_COMPONENTS } from "../components/work-details/index.js";
import ArticleFullPage from "../components/work-details/ArticleFullPage.jsx";

const DOWNLOADABLE = [["pdfFile", "le PDF"], ["csvFile", "le CSV"], ["imageFile", "l'image"]];

// Types de contenu "document" — mise en page façon revue scientifique.
// Les documentaires (vidéo) gardent l'affichage original : pas de DOI/PDF pertinent pour eux.
// Les articles ont leur propre mise en page dédiée (voir ArticleFullPage) — exclus d'ici.
const JOURNAL_STYLE_TABLES = ["reports", "studies", "research_notes", "experiments", "data_visualizations"];

// CSS injecté une seule fois — un seul fichier, rien d'autre à importer.
const STYLE_ID = "ytd-article-scientific-styles";
if (typeof document !== "undefined" && !document.getElementById(STYLE_ID)) {
  const style = document.createElement("style");
  style.id = STYLE_ID;
  style.textContent = `
.ytd-article-breadcrumb {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  font-family: 'Inter', sans-serif; font-size: 13.5px; margin-bottom: 20px;
}
.ytd-article-breadcrumb a { color: ${T.green}; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
.ytd-article-breadcrumb a:hover { text-decoration: underline; }
.ytd-article-breadcrumb span.sep { color: ${T.inkSoft}; }
.ytd-article-breadcrumb span.current { color: ${T.inkSoft}; }

.ytd-article-grid {
  display: grid; grid-template-columns: 230px minmax(0, 1fr); gap: 40px; align-items: start;
}
.ytd-article-sidebar {
  border: 1px solid ${T.line}; border-radius: 6px; padding: 16px; position: sticky; top: 90px;
}
.ytd-article-sidebar img {
  width: 100%; border: 1px solid ${T.line}; margin-bottom: 14px; transition: transform 280ms cubic-bezier(0.22,1,0.36,1);
}
.ytd-article-sidebar:hover img { transform: scale(1.02); }
.ytd-article-sidebar-line {
  font-family: 'Inter', sans-serif; font-size: 12.5px; color: ${T.inkSoft}; margin: 0 0 6px; line-height: 1.5;
}
.ytd-article-sidebar-stats {
  display: flex; flex-direction: column; gap: 6px; margin-top: 12px; padding-top: 12px; border-top: 1px solid ${T.line};
}
.ytd-article-sidebar-stats span {
  display: inline-flex; align-items: center; gap: 6px;
  font-family: 'IBM Plex Mono', monospace; font-size: 11.5px; color: ${T.ink};
}
.ytd-article-sidebar-stats strong { color: ${T.green}; }
.ytd-report-top {
  display: flex; gap: 28px; align-items: flex-start; margin-bottom: 40px;
}
.ytd-report-top-cover {
  width: 180px; flex-shrink: 0; border: 1px solid ${T.line}; border-radius: 6px; overflow: hidden;
}
.ytd-report-top-cover img { width: 100%; display: block; }
.ytd-report-top-meta { flex: 1; min-width: 0; }
.ytd-report-sommaire { margin-bottom: 40px; }
@media (max-width: 640px) {
  .ytd-report-top { flex-direction: column; }
  .ytd-report-top-cover { width: 140px; }
}

.ytd-article-kicker {
  font-family: 'IBM Plex Mono', monospace; font-size: 12px; color: ${T.inkSoft}; margin-bottom: 10px;
}
.ytd-article-kicker strong { color: ${T.red}; }
.ytd-article-title {
  font-family: 'Newsreader', serif; font-size: clamp(26px, 3.4vw, 38px); font-weight: 500; line-height: 1.18; margin: 0 0 16px;
}
.ytd-article-byline {
  font-family: 'Inter', sans-serif; font-size: 14.5px; color: ${T.ink}; margin: 0 0 4px; font-weight: 600;
}
.ytd-article-affiliation {
  font-family: 'Inter', sans-serif; font-size: 13px; color: ${T.inkSoft}; margin: 0 0 18px;
}
.ytd-article-formats {
  display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
  padding: 12px 0; border-top: 1px solid ${T.line}; border-bottom: 1px solid ${T.line}; margin-bottom: 28px;
  font-family: 'Inter', sans-serif; font-size: 13px;
}
.ytd-article-formats .doi { color: ${T.inkSoft}; }
.ytd-article-formats .doi a { color: ${T.green}; text-decoration: none; }
.ytd-article-formats .doi a:hover { text-decoration: underline; }
.ytd-article-format-btn {
  display: inline-flex; align-items: center; gap: 5px; font-weight: 700; cursor: pointer;
  background: none; border: none; padding: 0; color: ${T.green};
  transition: color 160ms ease, transform 160ms ease;
}
.ytd-article-format-btn:hover { color: ${T.ink}; transform: translateY(-1px); }
.ytd-article-format-btn:disabled { color: ${T.inkSoft}; cursor: default; }
.ytd-article-format-current { display: inline-flex; align-items: center; gap: 5px; font-weight: 700; color: ${T.ink}; }

@media (max-width: 780px) {
  .ytd-article-grid { grid-template-columns: 1fr; }
  .ytd-article-sidebar { position: static; max-width: 220px; margin: 0 auto; }
}
`;
  document.head.appendChild(style);
}

export default function WorkDetail() {
  const navigate = useNavigate();
  const { typeSlug, slug } = useParams();
  const { work, loading } = useWork(typeSlug, slug);
  const { works } = useWorks();
  const type = getTypeByRouteSlug(typeSlug);
  const [shared, setShared] = useState(false);
  const [downloading, setDownloading] = useState(false);

  useDocumentMeta(work?.title, work?.excerpt);
  useLogView(work?.table, work?.id);
  const viewCount = useViewCount(work?.table, work?.id);
  const downloadCount = useDownloadCount(work?.table, work?.id);

  // A plain <a download> is silently ignored by browsers when the file is
  // cross-origin (Supabase Storage is a different domain from the site) —
  // it just opens the PDF instead of saving it. Fetching it as a blob first
  // forces a real download regardless of origin.
  async function handleDownload(url, extensionFallback) {
    setDownloading(true);
    try {
      const res = await fetch(url);
      if (!res.ok) throw new Error("download failed");
      const blob = await res.blob();
      const ext = url.split(".").pop()?.split("?")[0] || extensionFallback;
      const filename = `${(work.title || "document").replace(/[^\w\- ]+/g, "").trim()}.${ext}`;
      const blobUrl = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = blobUrl;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(blobUrl);
      logDownload(work.table, work.id);
    } catch {
      window.open(url, "_blank");
    } finally {
      setDownloading(false);
    }
  }

  async function handleShare() {
    if (navigator.share) {
      try { await navigator.share({ title: work.title, text: work.excerpt, url: window.location.href }); } catch { /* user cancelled */ }
      return;
    }
    await navigator.clipboard.writeText(window.location.href);
    setShared(true);
    setTimeout(() => setShared(false), 2000);
  }

  function shareTo(network) {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent(work.title);
    const targets = {
      facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
      twitter: `https://twitter.com/intent/tweet?url=${url}&text=${text}`,
      linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${url}`,
    };
    window.open(targets[network], "_blank", "noopener,noreferrer,width=600,height=500");
  }

  if (loading) return <div style={{ maxWidth: 1000, margin: "0 auto", padding: "120px 24px", color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</div>;

  if (!work || !type) {
    return (
      <div style={{ maxWidth: 1000, margin: "0 auto", padding: "120px 24px 100px" }}>
        <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft, marginBottom: 20 }}>Cette publication est introuvable ou n'est plus disponible.</p>
        <Link to={PATHS.works} style={{ color: T.green, fontFamily: "'IBM Plex Mono', monospace", fontSize: 13 }}>← Retour aux Works</Link>
      </div>
    );
  }

  const related = works.filter(w => w.id !== work.id && w.category === work.category).slice(0, 3);

  if (work.table === "articles") {
    return <ArticleFullPage work={work} otherArticles={works.filter(w => w.id !== work.id && w.table === "articles")} shareTo={shareTo} />;
  }

  const BodyComponent = WORK_DETAIL_COMPONENTS[work.table];
  const [downloadField, downloadLabel] = DOWNLOADABLE.find(([field]) => work[field]) || [];
  const downloadUrl = downloadField ? work[downloadField] : "";
  const isJournalStyle = JOURNAL_STYLE_TABLES.includes(work.table);
  const isReportPage = work.table === "reports";
  const hasSommaireContent = isReportPage && work.tableOfContents?.some(item => item.content?.trim());

  return (
    <div className="ytd-work-detail-page" style={{ maxWidth: isJournalStyle ? 1080 : 1000, margin: "0 auto", padding: "56px 24px 110px" }}>
      <button onClick={() => navigate(-1)} style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 16 }}>
        <ArrowLeft size={15} /> Retour
      </button>

      {isReportPage ? (
        <>
          <nav className="ytd-article-breadcrumb" aria-label="Fil d'ariane">
            <Link to={PATHS.home}><Home size={14} /> Accueil</Link>
            <ChevronRight size={13} className="sep" />
            <Link to={PATHS.works}>Works</Link>
            <ChevronRight size={13} className="sep" />
            <span className="current">{work.category}</span>
          </nav>

          <div className="ytd-report-top">
            {work.coverImage && (
              <div className="ytd-report-top-cover">
                <img src={work.coverImage} alt={`Couverture de ${work.title}`} />
              </div>
            )}
            <div className="ytd-report-top-meta">
              <div className="ytd-article-kicker">
                <strong>{work.category}</strong>{(work.volume || work.issue) && ` · Vol.${work.volume || "—"} No.${work.issue || "—"}`}{work.date && `, ${fmtDate(work.date)}`}
              </div>
              <h1 className="ytd-article-title">{work.title}</h1>
              <p className="ytd-article-byline">{work.author}</p>
              {work.authorAffiliation && <p className="ytd-article-affiliation">{work.authorAffiliation}</p>}

              <div className="ytd-article-formats">
                {work.doi && <span className="doi">DOI: <a href={`https://doi.org/${work.doi}`} target="_blank" rel="noreferrer">{work.doi}</a></span>}
                {downloadUrl && (
                  <button type="button" className="ytd-article-format-btn" disabled={downloading} onClick={() => handleDownload(downloadUrl, "pdf")}>
                    <FileText size={14} /> {downloading ? "…" : "PDF"}
                  </button>
                )}
                <span className="ytd-article-format-current"><Code2 size={14} /> HTML</span>
              </div>

              {(work.pageCount || downloadCount != null || viewCount != null) && (
                <div className="ytd-article-sidebar-stats" style={{ flexDirection: "row", gap: 18, borderTop: "none", paddingTop: 0, marginTop: 4 }}>
                  {work.pageCount && <span>{work.pageCount} pages</span>}
                  {downloadCount != null && <span><Download size={12} /> <strong>{downloadCount}</strong> téléchargement{downloadCount === 1 ? "" : "s"}</span>}
                  {viewCount != null && <span><Eye size={12} /> <strong>{viewCount}</strong> vue{viewCount === 1 ? "" : "s"}</span>}
                </div>
              )}
            </div>
          </div>

          {hasSommaireContent && (
            <div className="ytd-report-sommaire">
              <InteractiveSommaire items={work.tableOfContents} />
            </div>
          )}

          <div className="ytd-work-detail-body" style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
            {BodyComponent ? <BodyComponent work={work} /> : <p style={{ color: T.inkSoft }}>Type de contenu non pris en charge.</p>}
          </div>

          <div style={{ display: "flex", alignItems: "center", gap: 10, flexWrap: "wrap", marginTop: 24 }}>
            <button type="button" onClick={handleShare} style={{ display: "inline-flex", alignItems: "center", gap: 8, padding: "12px 20px", borderRadius: 2, background: "transparent", color: T.ink, border: `1px solid ${T.ink}`, cursor: "pointer", fontFamily: "'Inter', sans-serif", fontSize: 13.5, fontWeight: 600 }}>
              <Share2 size={15} /> {shared ? "Lien copié !" : "Partager"}
            </button>
          </div>
        </>
      ) : isJournalStyle ? (
        <>
          <nav className="ytd-article-breadcrumb" aria-label="Fil d'ariane">
            <Link to={PATHS.home}><Home size={14} /> Accueil</Link>
            <ChevronRight size={13} className="sep" />
            <Link to={PATHS.works}>Works</Link>
            <ChevronRight size={13} className="sep" />
            <span className="current">{work.category}</span>
          </nav>

          <div className="ytd-article-grid">
            <aside className="ytd-article-sidebar">
              {work.coverImage && <img src={work.coverImage} alt={`Couverture de ${work.title}`} />}
              {work.journalName && <p className="ytd-article-sidebar-line"><strong>{work.journalName}</strong></p>}
              {(work.volume || work.issue || work.date) && (
                <p className="ytd-article-sidebar-line">
                  {work.volume ? `Vol.${work.volume}` : ""}{work.issue ? ` No.${work.issue}` : ""}{(work.volume || work.issue) && work.date ? ", " : ""}{work.date ? fmtDate(work.date) : ""}
                </p>
              )}
              {work.pageCount && <p className="ytd-article-sidebar-line">{work.pageCount} pages</p>}
              <div className="ytd-article-sidebar-stats">
                {downloadCount != null && <span><Download size={12} /> <strong>{downloadCount}</strong> téléchargement{downloadCount === 1 ? "" : "s"}</span>}
                {viewCount != null && <span><Eye size={12} /> <strong>{viewCount}</strong> vue{viewCount === 1 ? "" : "s"}</span>}
              </div>
            </aside>

            <div>
              <div className="ytd-article-kicker">
                <strong>{work.category}</strong>{(work.volume || work.issue) && ` · Vol.${work.volume || "—"} No.${work.issue || "—"}`}{work.date && `, ${fmtDate(work.date)}`}
              </div>
              <h1 className="ytd-article-title">{work.title}</h1>
              <p className="ytd-article-byline">{work.author}</p>
              {work.authorAffiliation && <p className="ytd-article-affiliation">{work.authorAffiliation}</p>}

              <div className="ytd-article-formats">
                {work.doi && <span className="doi">DOI: <a href={`https://doi.org/${work.doi}`} target="_blank" rel="noreferrer">{work.doi}</a></span>}
                {downloadUrl && (
                  <button type="button" className="ytd-article-format-btn" disabled={downloading} onClick={() => handleDownload(downloadUrl, "pdf")}>
                    <FileText size={14} /> {downloading ? "…" : "PDF"}
                  </button>
                )}
                <span className="ytd-article-format-current"><Code2 size={14} /> HTML</span>
              </div>

              <div className="ytd-work-detail-body" style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
                {BodyComponent ? <BodyComponent work={work} /> : <p style={{ color: T.inkSoft }}>Type de contenu non pris en charge.</p>}
              </div>

              <div style={{ display: "flex", alignItems: "center", gap: 10, flexWrap: "wrap", marginTop: 24 }}>
                <button type="button" onClick={handleShare} style={{ display: "inline-flex", alignItems: "center", gap: 8, padding: "12px 20px", borderRadius: 2, background: "transparent", color: T.ink, border: `1px solid ${T.ink}`, cursor: "pointer", fontFamily: "'Inter', sans-serif", fontSize: 13.5, fontWeight: 600 }}>
                  <Share2 size={15} /> {shared ? "Lien copié !" : "Partager"}
                </button>
              </div>
            </div>
          </div>
        </>
      ) : (
        <>
          <Breadcrumb items={[{ label: "Works", to: PATHS.works }, { label: work.category }]} />
          <div className="ytd-work-detail-hero">
            <div>
              <Pill tone={T.green}>{work.category}</Pill>
              <h1>{work.title}</h1>
              <div className="ytd-work-detail-meta">
                <span>{work.author}</span><span>·</span><span>{fmtDate(work.date)}</span>
                {work.pageCount && <><span>·</span><span>{work.pageCount} pages</span></>}
                {work.readTime && <><span>·</span><span>{work.readTime}</span></>}
                {viewCount != null && <><span>·</span><span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}><Eye size={13} /> {viewCount} vue{viewCount === 1 ? "" : "s"}</span></>}
                {downloadCount != null && downloadCount > 0 && <><span>·</span><span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}><Download size={13} /> {downloadCount} téléchargement{downloadCount === 1 ? "" : "s"}</span></>}
              </div>
              <div style={{ display: "flex", alignItems: "center", gap: 10, flexWrap: "wrap", marginTop: 18 }}>
                {downloadUrl && (
                  <button
                    type="button"
                    onClick={() => handleDownload(downloadUrl, downloadField === "pdfFile" ? "pdf" : downloadField === "csvFile" ? "csv" : "jpg")}
                    disabled={downloading}
                    style={{ display: "inline-flex", alignItems: "center", gap: 8, padding: "12px 20px", borderRadius: 2, background: T.green, color: "#fff", border: `1px solid ${T.green}`, cursor: downloading ? "wait" : "pointer", fontFamily: "'Inter', sans-serif", fontSize: 13.5, fontWeight: 600, opacity: downloading ? 0.7 : 1 }}
                  >
                    <Download size={15} /> {downloading ? "Téléchargement…" : `Télécharger ${downloadLabel}`}
                  </button>
                )}
                <button type="button" onClick={handleShare} style={{ display: "inline-flex", alignItems: "center", gap: 8, padding: "12px 20px", borderRadius: 2, background: "transparent", color: T.ink, border: `1px solid ${T.ink}`, cursor: "pointer", fontFamily: "'Inter', sans-serif", fontSize: 13.5, fontWeight: 600 }}>
                  <Share2 size={15} /> {shared ? "Lien copié !" : "Partager"}
                </button>
              </div>
            </div>
          </div>
          {work.coverImage && <div className="ytd-work-detail-cover"><Cover tone={work.tone} label={work.category} tall image={work.coverImage} /></div>}
          <div className="ytd-work-detail-body" style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
            {BodyComponent ? <BodyComponent work={work} /> : <p style={{ color: T.inkSoft }}>Type de contenu non pris en charge.</p>}
          </div>
        </>
      )}

      <div className="ytd-cite-block">
        <span className="ytd-cite-block-label">Partager et citer</span>
        <div className="ytd-cite-block-share">
          <button type="button" onClick={() => shareTo("facebook")} aria-label="Partager sur Facebook"><Facebook size={15} /></button>
          <button type="button" onClick={() => shareTo("twitter")} aria-label="Partager sur X"><Twitter size={15} /></button>
          <button type="button" onClick={() => shareTo("linkedin")} aria-label="Partager sur LinkedIn"><Linkedin size={15} /></button>
          <button type="button" onClick={handleShare} aria-label="Copier le lien"><Share2 size={15} /></button>
        </div>
        <p className="ytd-cite-block-text">
          <Quote size={13} /> {work.author} ({new Date(work.date || Date.now()).getFullYear()}). <em>{work.title}</em>. {work.category}, Yewtod SS.
        </p>
      </div>

      <Divider margin="68px 0 40px" />
      <SectionLabel>Publications associées</SectionLabel>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 24 }} className="ytd-grid-3">
        {related.map((w, i) => (
          <Reveal key={w.id} delay={i * 70}>
            <div onClick={() => navigate(workPath(w.routeSlug, w.slug))} className="ytd-card" style={{ cursor: "pointer" }}>
              <Cover tone={w.tone} label={w.category} image={w.coverImage} />
              <h4 style={{ fontFamily: "'Newsreader', serif", fontSize: 15.5, fontWeight: 600, margin: "10px 0 0", lineHeight: 1.3 }}>{w.title}</h4>
            </div>
          </Reveal>
        ))}
        {related.length === 0 && <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft }}>Aucune autre publication dans cette catégorie pour l'instant.</p>}
      </div>
    </div>
  );
}
