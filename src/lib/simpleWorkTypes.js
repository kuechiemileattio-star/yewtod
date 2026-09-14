/* ============================================================
   Config for the 3 content types that get their own dashboard
   section (Rapports, Articles, Visualisations) with a simplified
   creation page: Titre, Catégorie, Date, Résumé, fichier, image.
   Livres has its own richer editor (see BooksPanel.jsx) and the
   5 remaining content types stay under "Autres publications"
   (see PublicationsPanel.jsx).
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
};

export const SIMPLE_WORK_ORDER = ["reports", "articles", "data_visualizations"];
