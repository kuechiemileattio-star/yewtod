/* ============================================================
   Config for every "Works" content type except books: all 8 share
   the same simplified creation page (Titre, Catégorie, Date,
   Résumé, fichier facultatif, image). Livres keeps its own richer
   editor (see BooksPanel.jsx) since it isn't a "Works" table.
   SIMPLE_WORK_ORDER only lists the 3 types with their own sidebar
   section — that's what the in-page type-switch tabs offer; the
   other 5 stay reachable from "Autres publications".
============================================================= */

export const SIMPLE_WORK_TYPES = {
  reports: {
    table: "reports",
    adminPath: "rapports",
    singular: "Rapport",
    plural: "Rapports",
    summaryField: "executiveSummary",
    summaryLabel: "Résumé",
    fileField: "pdfFile",
    fileLabel: "Document (PDF)",
    fileAccept: ".pdf",
    fileBucket: "documents",
    migrationFile: "007_reports_pdf_metadata.sql",
  },
  articles: {
    table: "articles",
    adminPath: "articles",
    singular: "Article",
    plural: "Articles",
    summaryField: "summary",
    summaryLabel: "Résumé",
    fileField: "pdfFile",
    fileLabel: "Document (PDF)",
    fileAccept: ".pdf",
    fileBucket: "documents",
    migrationFile: "008_articles_pdf.sql",
  },
  data_visualizations: {
    table: "data_visualizations",
    adminPath: "visualisations",
    singular: "Visualisation",
    plural: "Visualisations",
    summaryField: "description",
    summaryLabel: "Description",
    fileField: "csvFile",
    fileLabel: "Fichier de données (CSV)",
    fileAccept: ".csv",
    fileBucket: "documents",
  },
  studies: {
    table: "studies",
    adminPath: "etudes",
    singular: "Étude",
    plural: "Études",
    summaryField: "context",
    summaryLabel: "Résumé",
  },
  research_notes: {
    table: "research_notes",
    adminPath: "notes-de-recherche",
    singular: "Note de recherche",
    plural: "Notes de recherche",
    summaryField: "mainIdea",
    summaryLabel: "Résumé",
  },
  documentary_series: {
    table: "documentary_series",
    adminPath: "series-documentaires",
    singular: "Série documentaire",
    plural: "Séries documentaires",
    summaryField: "description",
    summaryLabel: "Résumé",
    urlField: "trailerUrl",
    urlLabel: "Lien de la bande-annonce (YouTube)",
  },
  documentary_episodes: {
    table: "documentary_episodes",
    adminPath: "episodes-documentaires",
    singular: "Épisode documentaire",
    plural: "Épisodes documentaires",
    summaryField: "summary",
    summaryLabel: "Résumé",
    urlField: "videoUrl",
    urlLabel: "Lien de la vidéo (YouTube)",
  },
  experiments: {
    table: "experiments",
    adminPath: "experimentations",
    singular: "Expérimentation",
    plural: "Expérimentations",
    summaryField: "objective",
    summaryLabel: "Résumé",
  },
};

// Only the 3 types with their own dedicated sidebar section get the
// in-page type-switch tabs — the other 5 are reached via "Autres publications".
export const SIMPLE_WORK_ORDER = ["reports", "articles", "data_visualizations"];

export const OTHER_SIMPLE_WORK_KEYS = ["studies", "research_notes", "documentary_series", "documentary_episodes", "experiments"];
