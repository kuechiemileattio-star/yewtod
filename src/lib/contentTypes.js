/* ============================================================
   Les 8 tables "Works" : table Supabase <-> segment d'URL <->
   libellé français affiché, et la liste des champs spécifiques
   à afficher sur une page de détail (au-delà des champs communs
   title / coverImage / status / publishedAt / slug).
============================================================= */

export const CONTENT_TYPES = [
  {
    table: "articles",
    routeSlug: "articles",
    label: "Articles",
    fields: [
      ["subtitle", "Sous-titre"],
      ["summary", "Résumé"],
      ["content", "Contenu"],
      ["quotes", "Citations mises en avant"],
      ["images", "Images intégrées"],
      ["embeddedVideos", "Vidéos intégrées"],
      ["references", "Références bibliographiques"],
      ["relatedContent", "Publications liées"],
    ],
  },
  {
    table: "reports",
    routeSlug: "rapports",
    label: "Rapports",
    fields: [
      ["executiveSummary", "Résumé exécutif"],
      ["problemStatement", "Problématique"],
      ["context", "Contexte"],
      ["methodology", "Méthodologie"],
      ["analyses", "Analyses"],
      ["charts", "Graphiques"],
      ["tables", "Tableaux"],
      ["results", "Résultats"],
      ["recommendations", "Recommandations"],
      ["conclusion", "Conclusion"],
      ["appendices", "Annexes"],
      ["bibliography", "Bibliographie"],
      ["pdfFile", "Fichier PDF"],
      ["version", "Version"],
      ["authors", "Auteur(s)"],
    ],
  },
  {
    table: "studies",
    routeSlug: "etudes",
    label: "Études",
    fields: [
      ["researchQuestion", "Question de recherche"],
      ["context", "Contexte"],
      ["objectives", "Objectifs"],
      ["hypotheses", "Hypothèses"],
      ["methodology", "Méthodologie"],
      ["dataUsed", "Données utilisées"],
      ["analyses", "Analyses"],
      ["results", "Résultats"],
      ["discussion", "Discussion"],
      ["limitations", "Limites"],
      ["perspectives", "Perspectives"],
      ["bibliography", "Bibliographie"],
      ["authors", "Auteur(s)"],
    ],
  },
  {
    table: "research_notes",
    routeSlug: "notes-de-recherche",
    label: "Notes de recherche",
    fields: [
      ["mainIdea", "Idée principale"],
      ["context", "Contexte"],
      ["observations", "Observations"],
      ["hypotheses", "Hypothèses"],
      ["diagrams", "Schémas"],
      ["personalNotes", "Notes personnelles"],
      ["references", "Références"],
      ["usefulLinks", "Liens utiles"],
      ["progressStatus", "Niveau d'avancement"],
    ],
  },
  {
    table: "documentary_series",
    routeSlug: "series-documentaires",
    label: "Séries documentaires",
    fields: [
      ["description", "Description"],
      ["theme", "Thématique"],
      ["trailerUrl", "Bande-annonce"],
      ["videos", "Vidéos"],
      ["transcript", "Transcription"],
      ["guests", "Invités / intervenants"],
      ["additionalResources", "Ressources complémentaires"],
      ["relatedArticles", "Articles associés"],
      ["references", "Références"],
    ],
  },
  {
    table: "documentary_episodes",
    routeSlug: "episodes-documentaires",
    label: "Épisodes documentaires",
    fields: [
      ["episodeNumber", "Numéro d'épisode"],
      ["summary", "Résumé"],
      ["videoUrl", "Vidéo"],
      ["transcript", "Transcription"],
      ["speakers", "Intervenants"],
      ["illustrations", "Illustrations"],
      ["references", "Références"],
      ["relatedDocuments", "Documents associés"],
    ],
  },
  {
    table: "experiments",
    routeSlug: "experimentations",
    label: "Expérimentations",
    fields: [
      ["objective", "Objectif"],
      ["problemStatement", "Problématique"],
      ["protocol", "Protocole expérimental"],
      ["toolsUsed", "Outils utilisés"],
      ["datasets", "Jeux de données"],
      ["sourceCodeUrl", "Code source GitHub"],
      ["screenshots", "Captures d'écran"],
      ["visualizations", "Visualisations"],
      ["results", "Résultats"],
      ["analysis", "Analyse"],
      ["limitations", "Limites"],
      ["conclusion", "Conclusion"],
      ["downloadableFiles", "Fichiers téléchargeables"],
    ],
  },
  {
    table: "data_visualizations",
    routeSlug: "visualisations-de-donnees",
    label: "Visualisations de données",
    fields: [
      ["description", "Description"],
      ["dataSource", "Source des données"],
      ["visualizationType", "Type de visualisation"],
      ["legend", "Légende"],
      ["analysis", "Analyse"],
      ["csvFile", "Téléchargement CSV"],
      ["imageFile", "Téléchargement image"],
      ["sourceCodeUrl", "Code source"],
    ],
  },
];

export const CATEGORIES = CONTENT_TYPES.map(t => t.label);

export function getTypeByTable(table) {
  return CONTENT_TYPES.find(t => t.table === table);
}

export function getTypeByLabel(label) {
  return CONTENT_TYPES.find(t => t.label === label);
}

export function getTypeByRouteSlug(routeSlug) {
  return CONTENT_TYPES.find(t => t.routeSlug === routeSlug);
}

export const BOOK_CATEGORIES = ["IA", "économie", "politique", "physique", "mathématiques", "philosophie", "entrepreneuriat", "histoire", "sciences sociales"];

export const BOOK_DIFFICULTY_LABELS = {
  accessible: "Accessible",
  intermediaire: "Intermédiaire",
  exigeant: "Exigeant",
};

export const COLLAB_TYPES = [
  "Proposition de recherche", "Proposition de livre", "Proposition d'article",
  "Invitation à intervenir", "Partenariat", "Interview",
  "Participation à une étude", "Demande générale",
];

export function fmtDate(d) {
  if (!d) return "";
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "long", year: "numeric" });
}
