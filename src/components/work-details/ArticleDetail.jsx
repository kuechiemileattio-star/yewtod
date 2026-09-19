import React from "react";
import { T } from "../../theme.js";
import { Section, LinkAction, Pill } from "./shared.jsx";
import { findChapterText } from "../../lib/pdfMetadata.js";
import { ARTICLE_CONTENT_TYPES } from "../../lib/contentTypes.js";
import MediaDisplay, { extractMediaUrls } from "../MediaDisplay.jsx";

/** Résumé rendu comme un encart "abstract" (façon article académique) plutôt
 * qu'un simple bloc de texte — c'est la première chose lue sur la page. */
function Abstract({ text }) {
  if (!text) return null;
  return (
    <div className="ytd-article-abstract">
      <span className="ytd-article-abstract-label">Résumé</span>
      <p>{text}</p>
    </div>
  );
}

/** "Mots clés" inline juste sous le résumé — comme sur une vraie page d'article
 * académique (SCIRP), plutôt que des tags en bas de page. */
function Keywords({ tags }) {
  if (!tags.length) return null;
  return (
    <p className="ytd-article-keywords">
      <span className="ytd-article-keywords-label">Mots clés :</span>
      {tags.map((tag, i) => (
        <React.Fragment key={tag}>
          {i > 0 && ", "}
          <span className="ytd-article-keyword">{tag}</span>
        </React.Fragment>
      ))}
    </p>
  );
}

export default function ArticleDetail({ work }) {
  const hasReader = !!work.pdfFile;
  const images = extractMediaUrls(work.images);
  const videos = extractMediaUrls(work.embeddedVideos);
  const introduction = findChapterText(work.tableOfContents, ["introduction", "avant-propos"], "first");
  const conclusion = findChapterText(work.tableOfContents, ["conclusion", "en résumé", "pour conclure"], "last");
  const contentTypeLabel = ARTICLE_CONTENT_TYPES.find(ct => ct.value === work.contentType)?.label;
  const themes = (work.themes || "").split("\n").map(t => t.trim()).filter(Boolean);
  const tags = (work.tags || "").split("\n").map(t => t.trim()).filter(Boolean);

  return (
    <>
      {(themes.length > 0 || contentTypeLabel) && (
        <div className="ytd-article-taxonomy-row">
          {contentTypeLabel && <Pill tone={T.red}>{contentTypeLabel}</Pill>}
          {themes.map(t => <Pill key={t} tone={T.green}>{t}</Pill>)}
        </div>
      )}

      {hasReader ? (
        <>
          <Abstract text={introduction || work.summary} />
          <Keywords tags={tags} />
          <Section title="Synthèse" serif>{conclusion}</Section>
        </>
      ) : (
        <>
          {work.subtitle && !work.summary && (
            <p className="ytd-article-subtitle">{work.subtitle}</p>
          )}
          <Abstract text={work.summary} />
          <Keywords tags={tags} />
          <Section title="Contenu" serif>{work.content}</Section>
        </>
      )}

      {(images.length > 0 || videos.length > 0) && (
        <section className="ytd-work-detail-media-section">
          <h2>Médias intégrés</h2>
          <div className="ytd-work-detail-media-grid">
            {images.map(url => <figure key={url}><MediaDisplay type="image" url={url} alt={work.title} /></figure>)}
            {videos.map(url => <figure key={url}><MediaDisplay type="video" url={url} alt={work.title} /></figure>)}
          </div>
        </section>
      )}

      {hasReader && (
        <div style={{ marginTop: 30 }}>
          <LinkAction href={work.pdfFile}>Lire l'article complet (PDF)</LinkAction>
        </div>
      )}
    </>
  );
}
