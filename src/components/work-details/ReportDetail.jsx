import React from "react";
import { fmtDate } from "../../lib/contentTypes.js";
import { Section, FieldList, FactRow, TableOfContents } from "./shared.jsx";

export default function ReportDetail({ work }) {
  return (
    <>
      <Section title="Résumé">{work.executiveSummary}</Section>
      <FactRow facts={[["Version", work.version], ["Auteurs", work.authors], ["Publié le", fmtDate(work.date)]]} />
      <TableOfContents items={work.tableOfContents} />

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
