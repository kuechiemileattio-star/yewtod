import React from "react";
import { T } from "../../theme.js";
import { fmtDate } from "../../lib/contentTypes.js";
import { Section, FieldList, FactRow, LinkAction } from "./shared.jsx";

export default function ReportDetail({ work }) {
  return (
    <>
      <p style={{ fontFamily: "'Fraunces', serif", fontWeight: 500, fontSize: 21, lineHeight: 1.5, color: T.ink, margin: "0 0 6px", paddingBottom: 26, borderBottom: `1px solid ${T.line}` }}>
        {work.executiveSummary}
      </p>
      <FactRow facts={[["Version", work.version], ["Auteurs", work.authors], ["Publié le", fmtDate(work.date)]]} />
      {work.pdfFile && <div style={{ marginBottom: 30 }}><LinkAction href={work.pdfFile} download>Télécharger le rapport (PDF)</LinkAction></div>}

      <Section title="Problématique">{work.problemStatement}</Section>
      <Section title="Contexte">{work.context}</Section>
      <Section title="Méthodologie">{work.methodology}</Section>
      <Section title="Analyses">{work.analyses}</Section>
      <FieldList title="Graphiques" value={work.charts} />
      <FieldList title="Tableaux" value={work.tables} />
      <Section title="Résultats">{work.results}</Section>
      <Section title="Recommandations">{work.recommendations}</Section>
      <Section title="Conclusion">{work.conclusion}</Section>
      <FieldList title="Annexes" value={work.appendices} />
      <FieldList title="Bibliographie" value={work.bibliography} ordered />
    </>
  );
}
