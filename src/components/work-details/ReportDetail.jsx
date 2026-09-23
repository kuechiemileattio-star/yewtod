import React from "react";
import { fmtDate } from "../../lib/contentTypes.js";
import { Section, FactRow, LinkAction } from "./shared.jsx";

// Le Sommaire (quand il y a du contenu par section) s'affiche désormais
// dans la barre latérale, sous l'image de couverture — voir WorkDetail.jsx.
// Une "Conclusion"/"Synthèse" n'est plus un champ séparé : c'est juste un
// sous-titre comme un autre, ajouté dans le constructeur de sections.
export default function ReportDetail({ work }) {
  const hasReader = !!work.pdfFile;
  const hasSommaireContent = work.tableOfContents?.some(item => item.content?.trim());

  return (
    <>
      {!hasSommaireContent && <Section title="Description" serif>{work.executiveSummary}</Section>}
      <FactRow facts={[["Version", work.version], ["Auteurs", work.authors], ["Publié le", fmtDate(work.date)]]} />

      {hasReader && (
        <div style={{ marginTop: 30 }}>
          <LinkAction href={work.pdfFile}>Lire le rapport complet (PDF)</LinkAction>
        </div>
      )}
    </>
  );
}
