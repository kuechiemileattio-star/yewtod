import React from "react";
import { fmtDate } from "../../lib/contentTypes.js";
import { findChapterText } from "../../lib/pdfMetadata.js";
import { Section, FactRow, LinkAction, InteractiveSommaire } from "./shared.jsx";

export default function ReportDetail({ work }) {
  const hasReader = !!work.pdfFile;
  const introduction = findChapterText(work.tableOfContents, ["introduction", "avant-propos", "résumé exécutif"], "first");
  const conclusion = findChapterText(work.tableOfContents, ["conclusion", "en résumé", "pour conclure"], "last");
  const hasSommaireContent = work.tableOfContents?.some(item => item.content?.trim());

  return (
    <>
      {hasSommaireContent
        ? <InteractiveSommaire items={work.tableOfContents} />
        : <Section title="Description" serif>{introduction || work.executiveSummary}</Section>}
      <FactRow facts={[["Version", work.version], ["Auteurs", work.authors], ["Publié le", fmtDate(work.date)]]} />
      <Section title="Synthèse" serif>{conclusion || work.conclusion}</Section>

      {hasReader && (
        <div style={{ marginTop: 30 }}>
          <LinkAction href={work.pdfFile}>Lire le rapport complet (PDF)</LinkAction>
        </div>
      )}
    </>
  );
}
