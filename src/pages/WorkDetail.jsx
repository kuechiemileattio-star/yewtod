import React, { useState } from "react";
import { useNavigate, useParams, Link } from "react-router-dom";
import { ArrowLeft, Download, Share2 } from "lucide-react";
import { T } from "../theme.js";
import { fmtDate, getTypeByRouteSlug } from "../lib/contentTypes.js";
import { workPath, PATHS } from "../lib/paths.js";
import { useWork, useWorks } from "../hooks/useWorks.js";
import useDocumentMeta from "../hooks/useDocumentMeta.js";
import useLogView from "../hooks/useLogView.js";
import Divider from "../components/Divider.jsx";
import Reveal from "../components/Reveal.jsx";
import Cover from "../components/Cover.jsx";
import SectionLabel from "../components/SectionLabel.jsx";
import Breadcrumb from "../components/Breadcrumb.jsx";
import { Pill } from "../components/work-details/shared.jsx";
import { WORK_DETAIL_COMPONENTS } from "../components/work-details/index.js";

const DOWNLOADABLE = [["pdfFile", "le PDF"], ["csvFile", "le CSV"], ["imageFile", "l'image"]];

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
  const BodyComponent = WORK_DETAIL_COMPONENTS[work.table];
  const [downloadField, downloadLabel] = DOWNLOADABLE.find(([field]) => work[field]) || [];
  const downloadUrl = downloadField ? work[downloadField] : "";

  return (
    <div className="ytd-work-detail-page" style={{ maxWidth: 1000, margin: "0 auto", padding: "56px 24px 110px" }}>
      <button onClick={() => navigate(-1)} style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 16 }}>
        <ArrowLeft size={15} /> Retour
      </button>
      <Breadcrumb items={[{ label: "Works", to: PATHS.works }, { label: work.category }]} />
      <div className="ytd-work-detail-hero">
        <div>
          <Pill tone={T.green}>{work.category}</Pill>
          <h1>{work.title}</h1>
          <div className="ytd-work-detail-meta">
            <span>{work.author}</span><span>·</span><span>{fmtDate(work.date)}</span>
            {work.pageCount && <><span>·</span><span>{work.pageCount} pages</span></>}
            {work.readTime && <><span>·</span><span>{work.readTime}</span></>}
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
