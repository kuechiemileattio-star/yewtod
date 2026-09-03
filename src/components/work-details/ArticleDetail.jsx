import React from "react";
import { T } from "../../theme.js";
import { Section, FieldList, QuoteStack } from "./shared.jsx";
import MediaDisplay, { extractMediaUrls } from "../MediaDisplay.jsx";

export default function ArticleDetail({ work }) {
  const images = extractMediaUrls(work.images);
  const videos = extractMediaUrls(work.embeddedVideos);
  return (
    <>
      <p style={{ fontFamily: "'Fraunces', serif", fontWeight: 500, fontSize: 21, lineHeight: 1.5, color: T.ink, margin: "0 0 34px", paddingBottom: 26, borderBottom: `1px solid ${T.line}` }}>
        {work.subtitle || work.excerpt}
      </p>
      <Section title="Contenu">{work.content}</Section>
      <QuoteStack value={work.quotes} />
      {(images.length > 0 || videos.length > 0) && (
        <section className="ytd-work-detail-media-section">
          <h2>Médias intégrés</h2>
          <div className="ytd-work-detail-media-grid">
            {images.map(url => <figure key={url}><MediaDisplay type="image" url={url} alt={work.title} /></figure>)}
            {videos.map(url => <figure key={url}><MediaDisplay type="video" url={url} alt={work.title} /></figure>)}
          </div>
        </section>
      )}
      <FieldList title="Références bibliographiques" value={work.references} ordered />
    </>
  );
}
