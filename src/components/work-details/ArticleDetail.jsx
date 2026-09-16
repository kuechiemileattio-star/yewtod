import React from "react";
import { Section, LinkAction } from "./shared.jsx";
import { findChapterText } from "../../lib/pdfMetadata.js";
import MediaDisplay, { extractMediaUrls } from "../MediaDisplay.jsx";

export default function ArticleDetail({ work }) {
  const hasReader = !!work.pdfFile;
  const images = extractMediaUrls(work.images);
  const videos = extractMediaUrls(work.embeddedVideos);
  const introduction = findChapterText(work.tableOfContents, ["introduction", "avant-propos"], "first");
  const conclusion = findChapterText(work.tableOfContents, ["conclusion", "en résumé", "pour conclure"], "last");

  return (
    <>
      <Section title="Description" serif>{introduction || work.summary}</Section>
      <Section title="Synthèse" serif>{conclusion}</Section>
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
