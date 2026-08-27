import { T } from "./theme.js";

/* ============================================================
   DONNÉES DE DÉMONSTRATION
============================================================= */

export const CATEGORIES = [
  "Articles", "Rapports", "Études", "Notes de recherche",
  "Séries documentaires", "Épisodes documentaires", "Expérimentations", "Visualisations de données",
];

export const DEFAULT_MEDIA = {
  Articles: { image: "https://images.unsplash.com/photo-1495020689067-958852a7765e?auto=format&fit=crop&w=1200&q=85" },
  Rapports: { image: "https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=85" },
  Études: { image: "https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1200&q=85" },
  "Notes de recherche": { image: "https://images.unsplash.com/photo-1456324504439-367cee3b3c32?auto=format&fit=crop&w=1200&q=85" },
  "Séries documentaires": { image: "https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&w=1200&q=85", video: "https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4" },
  "Épisodes documentaires": { image: "https://images.unsplash.com/photo-1492724441997-5dc865305da7?auto=format&fit=crop&w=1200&q=85", video: "https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4" },
  Expérimentations: { image: "https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=1200&q=85" },
  "Visualisations de données": { image: "https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=85" },
};

const ARTICLE_FIELDS = [
  ["subtitle", "Sous-titre", "text"], ["coverImage", "Image de couverture", "url"], ["summary", "Résumé", "textarea"],
  ["content", "Contenu", "textarea"], ["quotes", "Citations mises en avant", "textarea"], ["images", "Images intégrées", "textarea"],
  ["embeddedVideos", "Vidéos intégrées", "textarea"], ["references", "Références bibliographiques", "textarea"],
  ["tags", "Mots-clés", "text"], ["relatedContent", "Publications liées", "text"],
];

const COMMON_FIELDS = ARTICLE_FIELDS;

export const CONTENT_FIELDS = {
  Articles: COMMON_FIELDS,
  Rapports: [
    ["executiveSummary", "Résumé exécutif", "textarea"], ["coverImage", "Image de couverture", "url"], ["problemStatement", "Problématique", "textarea"], ["context", "Contexte", "textarea"],
    ["methodology", "Méthodologie", "textarea"], ["analyses", "Analyses", "textarea"], ["charts", "Graphiques", "textarea"],
    ["tables", "Tableaux", "textarea"], ["results", "Résultats", "textarea"], ["recommendations", "Recommandations", "textarea"],
    ["conclusion", "Conclusion", "textarea"], ["appendices", "Annexes", "textarea"], ["bibliography", "Bibliographie", "textarea"],
    ["pdfFile", "Fichier PDF", "url"], ["version", "Version", "text"], ["authors", "Auteur(s)", "text"],
    ["publishedAt", "Date de publication", "date"],
  ],
  Études: [
    ["researchQuestion", "Question de recherche", "textarea"], ["context", "Contexte", "textarea"], ["objectives", "Objectifs", "textarea"],
    ["hypotheses", "Hypothèses", "textarea"], ["methodology", "Méthodologie", "textarea"], ["dataUsed", "Données utilisées", "textarea"],
    ["analyses", "Analyses", "textarea"], ["results", "Résultats", "textarea"], ["discussion", "Discussion", "textarea"],
    ["limitations", "Limites", "textarea"], ["perspectives", "Perspectives", "textarea"], ["bibliography", "Bibliographie", "textarea"],
    ["authors", "Auteur(s)", "text"], ["publishedAt", "Date de publication", "date"],
  ],
  "Notes de recherche": [
    ["mainIdea", "Idée principale", "textarea"], ["context", "Contexte", "textarea"], ["observations", "Observations", "textarea"],
    ["hypotheses", "Hypothèses", "textarea"], ["diagrams", "Schémas", "textarea"], ["personalNotes", "Notes personnelles", "textarea"],
    ["references", "Références", "textarea"], ["usefulLinks", "Liens utiles", "textarea"], ["progressStatus", "Niveau d'avancement", "select"],
    ["updatedAt", "Date de mise à jour", "date"],
  ],
  "Séries documentaires": [
    ["description", "Description", "textarea"], ["coverImage", "Image de couverture", "url"], ["theme", "Thématique", "text"], ["episodes", "Épisodes", "textarea"],
    ["trailer", "Bande-annonce", "url"], ["videos", "Vidéos", "textarea"], ["transcript", "Transcription", "textarea"],
    ["guests", "Invités / intervenants", "textarea"], ["additionalResources", "Ressources complémentaires", "textarea"],
    ["relatedArticles", "Articles associés", "text"], ["references", "Références", "textarea"],
  ],
  "Épisodes documentaires": [
    ["episodeNumber", "Numéro d'épisode", "number"], ["summary", "Résumé", "textarea"], ["video", "Vidéo", "url"],
    ["transcript", "Transcription", "textarea"], ["speakers", "Intervenants", "textarea"], ["chapters", "Chapitres", "textarea"],
    ["illustrations", "Illustrations", "textarea"], ["references", "Références", "textarea"], ["relatedDocuments", "Documents associés", "textarea"],
    ["publishedAt", "Date de publication", "date"],
  ],
  Expérimentations: [
    ["objective", "Objectif", "textarea"], ["problemStatement", "Problématique", "textarea"], ["protocol", "Protocole expérimental", "textarea"],
    ["toolsUsed", "Outils utilisés", "textarea"], ["datasets", "Jeux de données", "textarea"], ["sourceCode", "Code source GitHub", "url"],
    ["screenshots", "Captures d'écran", "textarea"], ["visualizations", "Visualisations", "textarea"], ["results", "Résultats", "textarea"],
    ["analysis", "Analyse", "textarea"], ["limitations", "Limites", "textarea"], ["conclusion", "Conclusion", "textarea"],
    ["downloadableFiles", "Fichiers téléchargeables", "textarea"],
  ],
  "Visualisations de données": [
    ["description", "Description", "textarea"], ["dataSource", "Source des données", "text"], ["visualizationType", "Type de visualisation", "select"],
    ["interactiveChart", "Graphique interactif", "textarea"], ["filters", "Filtres", "textarea"], ["legend", "Légende", "textarea"],
    ["analysis", "Analyse", "textarea"], ["csvDownload", "Téléchargement CSV", "url"], ["imageDownload", "Téléchargement image", "url"],
    ["sourceCode", "Code source", "url"], ["updatedAt", "Date de mise à jour", "date"],
  ],
};

export function emptyContentFields(category) {
  return Object.fromEntries((CONTENT_FIELDS[category] || COMMON_FIELDS).map(([key]) => [key, key === "progressStatus" ? "idée" : ""]));
}

export const WORKS = [
  {
    id: "w1", category: "Articles", title: "Pourquoi les systèmes complexes résistent à nos réformes",
    excerpt: "Une lecture des politiques publiques à travers la théorie des systèmes : pourquoi les leviers évidents produisent souvent l'effet inverse.",
    date: "2026-08-02", author: "Yewtod", readTime: "9 min", tags: ["systèmes complexes", "politique publique"],
    tone: T.green,
  },
  {
    id: "w2", category: "Rapports", title: "Cartographie des inégalités éducatives en Afrique de l'Ouest",
    excerpt: "Un rapport de synthèse sur les trajectoires scolaires, les données disponibles et les angles morts statistiques de la région.",
    date: "2026-07-21", author: "Yewtod", readTime: "22 min", tags: ["éducation", "données"],
    tone: T.red,
  },
  {
    id: "w3", category: "Notes de recherche", title: "Ce que l'IA générative change vraiment au travail intellectuel",
    excerpt: "Notes de terrain sur l'adoption des IA génératives dans des équipes de recherche, entre gain de productivité et dilution du jugement.",
    date: "2026-07-08", author: "Yewtod", readTime: "6 min", tags: ["intelligence artificielle", "travail"],
    tone: T.inkSoft,
  },
  {
    id: "w4", category: "Études", title: "Croissance économique et fragilité institutionnelle : le cas du Bénin",
    excerpt: "Une étude longue sur les liens entre trajectoire de croissance et solidité des institutions publiques depuis deux décennies.",
    date: "2026-06-29", author: "Yewtod", readTime: "27 min", tags: ["économie", "institutions"],
    tone: T.green,
  },
  {
    id: "w5", category: "Visualisations de données", title: "Vingt ans de flux migratoires ouest-africains, visualisés",
    excerpt: "Une série de visualisations interactives pour comprendre les grandes recompositions migratoires régionales.",
    date: "2026-06-14", author: "Yewtod", readTime: "5 min", tags: ["migrations", "dataviz"],
    tone: T.red,
  },
  {
    id: "w6", category: "Expérimentations", title: "Simuler une politique de transfert monétaire avant de la lancer",
    excerpt: "Un prototype de simulation multi-agents pour tester, sur données synthétiques, l'effet d'un programme de transferts monétaires.",
    date: "2026-05-30", author: "Yewtod", readTime: "14 min", tags: ["modélisation", "protection sociale"],
    tone: T.inkSoft,
  },
  {
    id: "w7", category: "Séries documentaires", title: "Les artisans de l'innovation frugale — épisode 1",
    excerpt: "Premier épisode d'une série consacrée aux innovateurs qui bricolent des solutions à bas coût pour des problèmes complexes.",
    date: "2026-05-11", author: "Yewtod", readTime: "18 min (vidéo)", tags: ["innovation", "terrain"],
    tone: T.green,
  },
  {
    id: "w8", category: "Articles", title: "La gouvernance des données publiques n'est pas un sujet technique",
    excerpt: "Pourquoi les débats sur la donnée publique sont d'abord des débats de pouvoir, et rarement des débats d'infrastructure.",
    date: "2026-04-27", author: "Yewtod", readTime: "8 min", tags: ["données publiques", "gouvernance"],
    tone: T.red,
  },
  {
    id: "w9", category: "Rapports", title: "Rapport annuel Yewtod SS 2025 : ce que nous avons appris",
    excerpt: "Bilan de l'année : les travaux publiés, les hypothèses infirmées, et ce que nous voulons explorer en 2026.",
    date: "2026-01-15", author: "Yewtod", readTime: "12 min", tags: ["bilan"],
    tone: T.inkSoft,
  },
  {
    id: "w10", category: "Épisodes documentaires", title: "Réparer plutôt que remplacer",
    excerpt: "Premier épisode : des artisans montrent comment prolonger la vie des objets avec des outils simples.",
    date: "2026-05-18", author: "Yewtod SS", readTime: "18 min (vidéo)", tags: ["innovation", "terrain"],
    tone: T.green,
  },
];

const WORK_DETAILS = {
  w1: {
    subtitle: "Pourquoi les solutions linéaires échouent face aux problèmes qui s'adaptent.",
    summary: "Une analyse des boucles de rétroaction qui rendent les réformes publiques imprévisibles.",
    content: "Une réforme ne rencontre jamais un terrain immobile. Les acteurs réagissent, les institutions compensent et les effets secondaires deviennent parfois plus puissants que l'intention initiale.\n\nLire une politique comme un système permet de repérer les délais, les rétroactions et les points de levier avant d'agir.",
    quotes: "« Le comportement d'un système ne se comprend pas en isolant ses éléments. »",
    references: "Donella Meadows, Thinking in Systems\nPeter Senge, The Fifth Discipline",
    images: "Schéma des boucles de rétroaction · figure 1",
    videos: "Entretien avec une chercheuse en politiques publiques · 04:32 · https://www.youtube.com/watch?v=aqz-KE-bpKQ",
    embeddedVideos: "https://www.youtube.com/watch?v=aqz-KE-bpKQ",
    related: "w4, w6",
  },
  w2: {
    executiveSummary: "Ce rapport rassemble les données disponibles sur les trajectoires scolaires en Afrique de l'Ouest et identifie les comparaisons réellement fiables.",
    problem: "Les indicateurs nationaux masquent des écarts importants entre territoires, niveaux de revenus et parcours des élèves.",
    context: "L'étude couvre le Bénin, le Sénégal, la Côte d'Ivoire et le Ghana sur la période 2005-2024.",
    methodology: "Revue de 42 jeux de données publics, harmonisation des définitions et entretiens avec 18 acteurs éducatifs.",
    analyses: "Les écarts d'accès diminuent plus vite que les écarts d'apprentissage. Les données rurales restent les moins comparables.",
    results: "La progression moyenne de la scolarisation ne suffit pas à expliquer la persistance des inégalités de compétences.",
    recommendations: "Financer la mesure des acquis, publier les données à un niveau territorial fin et évaluer les transitions entre cycles.",
    conclusion: "Une politique éducative efficace doit suivre les parcours, et non uniquement les inscriptions.",
    charts: "Graphique 1 · évolution des inscriptions et des acquis\nGraphique 2 · écarts urbains et ruraux",
    tables: "Tableau 1 · sources comparées par pays\nTableau 2 · indicateurs retenus",
    appendices: "Annexe A · définitions harmonisées\nAnnexe B · guide d'entretien",
    pdf: "rapport-inegalites-educatives-2026.pdf",
    bibliography: "UNESCO Institute for Statistics, données éducatives 2024\nBanque mondiale, World Development Report",
    version: "Version 1.0 · août 2026",
    authors: "Yewtod SS · Équipe de recherche",
  },
  w3: {
    mainIdea: "L'IA générative accélère la production de textes, mais déplace la valeur vers la formulation des questions et la vérification du raisonnement.",
    context: "Notes issues d'observations menées auprès de trois équipes de recherche entre janvier et juin 2026.",
    observations: "Les premiers gains concernent la synthèse et la reformulation. Les erreurs les plus coûteuses apparaissent dans les étapes de cadrage.",
    hypotheses: "L'outil augmente la vitesse de travail lorsque les critères de qualité sont explicites, mais peut réduire l'effort de jugement lorsqu'ils ne le sont pas.",
    personalNotes: "À tester : comparer des équipes avec et sans journal de vérification des sources.",
    references: "Entretiens anonymisés · Carnets de terrain Yewtod SS · juin 2026",
    progress: "En cours · phase d'observation",
  },
  w4: {
    researchQuestion: "Dans quelles conditions la croissance économique renforce-t-elle réellement les institutions publiques ?",
    context: "Étude comparative du Bénin entre 2004 et 2025, replacée dans les trajectoires régionales.",
    objectives: "Relier les indicateurs de croissance aux capacités administratives, à la confiance publique et à la qualité de la dépense.",
    hypotheses: "La croissance améliore les institutions lorsqu'elle s'accompagne d'une capacité de coordination et d'une reddition de comptes crédible.",
    methodology: "Analyse documentaire, séries macroéconomiques et entretiens semi-directifs avec des responsables publics et des chercheurs.",
    dataUsed: "World Bank DataBank · IMF Data · Rapports budgétaires nationaux · 24 entretiens.",
    results: "Les gains de croissance sont durables lorsque les administrations peuvent apprendre et corriger leurs dispositifs.",
    discussion: "La robustesse institutionnelle apparaît davantage comme une capacité d'adaptation que comme un stock de règles.",
    limitations: "Les comparaisons historiques restent limitées par les changements de définition des indicateurs.",
    perspectives: "Étendre l'étude aux politiques de santé et d'éducation au niveau communal.",
    bibliography: "Acemoglu & Robinson, Why Nations Fail\nBanque mondiale, rapports pays Bénin",
    authors: "Yewtod SS · Yewtod",
  },
  w5: {
    description: "Une visualisation exploratoire des flux migratoires régionaux et de leurs changements sur vingt ans.",
    dataSource: "UN DESA International Migrant Stock · bases statistiques nationales harmonisées.",
    visualizationType: "Carte de flux et séries temporelles comparables.",
    interactiveChart: "Version interactive en préparation : sélection d'un pays d'origine, de destination et d'une année.",
    filters: "Pays d'origine · pays de destination · période · groupe d'âge.",
    legend: "L'épaisseur des flux indique le volume estimé ; les pointillés signalent une donnée interpolée.",
    analysis: "Les flux régionaux restent majoritaires, tandis que les destinations extra-régionales progressent de façon inégale.",
    sourceCode: "https://github.com/yewtod-ss/migrations-dataviz",
    updatedAt: "2026-08-14",
  },
  w6: {
    objective: "Tester les effets indirects d'un programme de transferts monétaires avant son déploiement.",
    problem: "Une aide peut modifier les prix locaux, les comportements d'offre et les relations entre ménages bénéficiaires et non-bénéficiaires.",
    protocol: "Simulation multi-agents sur 10 000 ménages synthétiques, avec trois scénarios de ciblage et deux niveaux de financement.",
    tools: "Python · Mesa · Jupyter · pandas",
    datasets: "Données synthétiques inspirées des enquêtes harmonisées LSMS.",
    github: "https://github.com/yewtod-ss/cash-transfer-simulation",
    results: "Le ciblage améliore le revenu médian, mais les effets sur les prix dépendent fortement de la capacité des marchés locaux.",
    analysis: "Le modèle sert surtout à révéler les hypothèses sensibles avant de collecter des données de terrain.",
    limitations: "Les comportements simulés ne remplacent pas une évaluation expérimentale.",
    conclusion: "Une simulation utile ne prédit pas l'avenir : elle montre quelles hypothèses méritent d'être testées.",
    downloads: "Prototype · documentation · données synthétiques",
  },
  w7: {
    description: "Une série documentaire consacrée aux personnes qui construisent des solutions simples pour des problèmes difficiles.",
    theme: "Innovation frugale, autonomie locale et apprentissage par le terrain.",
    episodes: "Épisode 1 · Réparer plutôt que remplacer\nÉpisode 2 · Produire avec moins\nÉpisode 3 · Organiser l'entraide",
    trailer: "https://www.youtube.com/watch?v=aqz-KE-bpKQ",
    videos: "Épisode 1 · 18 minutes · https://www.youtube.com/watch?v=aqz-KE-bpKQ",
    guests: "Artisans, ingénieurs et entrepreneurs sociaux d'Afrique de l'Ouest.",
    resources: "Carnet de terrain et bibliographie de la série.",
    related: "w6, w8",
    references: "Entretiens filmés par Yewtod SS · 2026",
  },
  w8: {
    subtitle: "Les infrastructures ne sont jamais neutres : elles distribuent des droits, des coûts et du pouvoir.",
    summary: "Pourquoi gouverner la donnée publique exige une discussion politique avant une discussion technique.",
    content: "Une donnée n'est pas seulement un fichier. C'est une décision sur ce qui mérite d'être mesuré, classé et rendu visible. La gouvernance commence donc avant le choix de l'outil.",
    quotes: "« Ce qui n'est pas mesuré ne disparaît pas : cela devient plus difficile à contester. »",
    references: "Virginia Eubanks, Automating Inequality\nRapport sur la gouvernance ouverte des données, 2025",
    related: "w2, w5",
  },
  w9: {
    executiveSummary: "Bilan des travaux publiés et des hypothèses révisées par Yewtod SS en 2025.",
    context: "Rapport annuel de la plateforme, couvrant les publications, les collaborations et les enseignements méthodologiques.",
    analyses: "Les travaux les plus utiles sont ceux qui relient une question précise à une décision concrète.",
    results: "9 publications, 5 collaborations reçues et une bibliothèque de 9 références éditoriales.",
    recommendations: "Documenter davantage les méthodes, ouvrir les données réutilisables et inviter de nouveaux contributeurs.",
    conclusion: "La prochaine année sera consacrée à l'expérimentation et à la transmission des méthodes.",
    bibliography: "Archives éditoriales Yewtod SS · 2025",
    version: "Version 1.0",
    authors: "Yewtod SS",
  },
  w10: {
    episodeNumber: "Épisode 1",
    summary: "À Cotonou, un atelier transforme des objets usés en outils du quotidien et transmet ses savoir-faire à de jeunes apprentis.",
    video: "https://www.youtube.com/watch?v=aqz-KE-bpKQ",
    transcription: "Transcription intégrale disponible avec la vidéo.",
    guests: "Afi Lawson · réparatrice et formatrice",
    chapters: "00:00 · Le problème de l'obsolescence\n05:20 · Le geste de réparation\n12:45 · Transmettre un métier",
    illustrations: "Photographies de l'atelier et schéma du processus de réparation.",
    references: "Entretien de terrain Yewtod SS · Cotonou · avril 2026",
    documents: "Carnet de terrain · fiche pratique de réparation",
  },
};

WORKS.forEach(work => Object.assign(work, WORK_DETAILS[work.id] || {}));
WORKS.forEach(work => Object.assign(work, {
  isSeed: true,
  coverImage: work.coverImage || work.cover || "",
  publishedAt: work.publishedAt || work.date,
  readingTime: work.readingTime || Number.parseInt(work.readTime, 10) || 0,
  problemStatement: work.problemStatement || work.problem || "",
  embeddedVideos: work.embeddedVideos || work.videos || "",
  transcript: work.transcript || work.transcription || "",
  speakers: work.speakers || work.guests || "",
  additionalResources: work.additionalResources || work.resources || "",
  relatedArticles: work.relatedArticles || work.related || "",
  toolsUsed: work.toolsUsed || work.tools || "",
  sourceCode: work.sourceCode || work.github || "",
  downloadableFiles: work.downloadableFiles || work.downloads || "",
  progressStatus: work.progressStatus || work.progress || "idée",
  updatedAt: work.updatedAt || work.date,
}));

export const BOOK_CATEGORIES = ["IA", "économie", "politique", "physique", "mathématiques", "philosophie", "entrepreneuriat", "histoire", "sciences sociales"];

export const BOOKS = [
  { id: "b1", title: "Thinking in Systems", author: "Donella Meadows", category: "sciences sociales", difficulty: "Accessible", note: "Le livre que je recommande toujours en premier pour raisonner en boucles plutôt qu'en lignes droites.", description: "Une introduction lumineuse aux boucles de rétroaction, aux délais et aux points de levier qui structurent les systèmes.", reviews: [{ id: "r1", author: "Yewtod", text: "Un excellent point de départ pour apprendre à regarder les relations plutôt que les objets.", date: "2026-08-10" }], tone: T.green },
  { id: "b2", title: "The Origins of Political Order", author: "Francis Fukuyama", category: "politique", difficulty: "Exigeant", note: "Une histoire comparée de la formation des États, indispensable pour penser les institutions africaines.", tone: T.red },
  { id: "b3", title: "Poor Economics", author: "Banerjee & Duflo", category: "économie", difficulty: "Accessible", note: "L'économie du développement telle qu'elle devrait s'écrire : au ras du terrain.", tone: T.inkSoft },
  { id: "b4", title: "Superintelligence", author: "Nick Bostrom", category: "IA", difficulty: "Exigeant", note: "Daté sur certains points, mais toujours la meilleure charpente pour penser les risques long terme de l'IA.", tone: T.green },
  { id: "b5", title: "The Structure of Scientific Revolutions", author: "Thomas Kuhn", category: "philosophie", difficulty: "Intermédiaire", note: "À relire à chaque fois qu'on croit qu'un paradigme scientifique est définitif.", tone: T.red },
  { id: "b6", title: "Seven Brief Lessons on Physics", author: "Carlo Rovelli", category: "physique", difficulty: "Accessible", note: "Un condensé élégant pour garder un pied dans les sciences dures.", tone: T.inkSoft },
  { id: "b7", title: "How to Lie with Statistics", author: "Darrell Huff", category: "mathématiques", difficulty: "Accessible", note: "Un petit livre qui immunise durablement contre les graphiques trompeurs.", tone: T.green },
  { id: "b8", title: "The Lean Startup", author: "Eric Ries", category: "entrepreneuriat", difficulty: "Accessible", note: "Utile bien au-delà des startups : une méthode pour tester des hypothèses vite et pas cher.", tone: T.red },
  { id: "b9", title: "Sapiens", author: "Yuval Noah Harari", category: "histoire", difficulty: "Accessible", note: "Discutable sur le plan académique, mais redoutablement efficace pour prendre du recul.", tone: T.inkSoft },
];

export const BOOK_FIELDS = [
  ["coverImage", "Couverture", "url"], ["publisher", "Maison d'édition", "text"], ["publicationYear", "Année de publication", "number"],
  ["summary", "Résumé", "textarea"], ["personalReview", "Avis personnel", "textarea"], ["favoriteQuotes", "Citations favorites", "textarea"],
  ["purchaseOrReadLink", "Lien d'achat ou de consultation", "url"], ["similarBooks", "Ouvrages similaires", "text"],
];

const BOOK_DETAILS = {
  b1: { publisher: "Chelsea Green Publishing", year: "2008", favoriteQuotes: "« You can't understand a system by changing one part in isolation. »", similar: "The Fifth Discipline\nThe Systems Bible" },
  b2: { publisher: "Farrar, Straus and Giroux", year: "2011", description: "Une histoire comparée de la formation des États, des institutions et de l'État de droit.", favoriteQuotes: "« Political order is not the natural state of human affairs. »", similar: "Why Nations Fail" },
  b3: { publisher: "PublicAffairs", year: "2011", description: "Une approche empirique de l'économie du développement, centrée sur les décisions réelles des ménages.", favoriteQuotes: "« Good economics is about asking better questions. »", similar: "Development as Freedom" },
  b4: { publisher: "Oxford University Press", year: "2014", description: "Une réflexion sur les trajectoires possibles d'une intelligence artificielle dépassant les capacités humaines.", favoriteQuotes: "« The first superintelligence to be created may be the last invention that humanity needs to make. »", similar: "Human Compatible" },
  b5: { publisher: "University of Chicago Press", year: "1962", description: "Un classique de l'histoire des sciences sur les paradigmes et les révolutions scientifiques.", favoriteQuotes: "« Normal science does not aim at novelties of fact or theory. »", similar: "The Logic of Scientific Discovery" },
  b6: { publisher: "Riverhead Books", year: "2015", description: "Sept leçons courtes pour traverser les grandes idées de la physique moderne.", favoriteQuotes: "« We are made mostly of the same stuff as the stars. »", similar: "A Brief History of Time" },
  b7: { publisher: "W. W. Norton", year: "1954", description: "Un guide accessible pour reconnaître les usages trompeurs des statistiques.", favoriteQuotes: "« The secret language of statistics, so appealing in a fact-minded culture, is employed to sensationalize, inflate, confuse, and oversimplify. »", similar: "The Data Detective" },
  b8: { publisher: "Crown Business", year: "2011", description: "Une méthode pour tester rapidement des hypothèses dans un environnement incertain.", favoriteQuotes: "« The only way to win is to learn faster than anyone else. »", similar: "The Mom Test" },
  b9: { publisher: "Harper", year: "2015", description: "Une vaste synthèse narrative de l'histoire de l'humanité et de ses systèmes de coopération.", favoriteQuotes: "« The real question is not what do we want to become, but what do we want to want? »", similar: "Guns, Germs, and Steel" },
};

BOOKS.forEach(book => Object.assign(book, BOOK_DETAILS[book.id] || {}));
BOOKS.forEach(book => Object.assign(book, {
  isSeed: true,
  coverImage: book.coverImage || "",
  publicationYear: book.publicationYear || book.year || "",
  summary: book.summary || book.description || "",
  personalReview: book.personalReview || book.note || "",
  purchaseOrReadLink: book.purchaseOrReadLink || book.link || "",
  similarBooks: book.similarBooks || book.similar || "",
  difficultyLevel: book.difficultyLevel || book.difficulty,
}));

export const BOOK_COVERS = {
  "Thinking in Systems": "https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=500&q=85",
  "The Origins of Political Order": "https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=500&q=85",
  "Poor Economics": "https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=500&q=85",
  Superintelligence: "https://images.unsplash.com/photo-1535378917042-10a22c95931a?auto=format&fit=crop&w=500&q=85",
  "The Structure of Scientific Revolutions": "https://images.unsplash.com/photo-1532012197267-da84d127e765?auto=format&fit=crop&w=500&q=85",
  "Seven Brief Lessons on Physics": "https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=500&q=85",
  "How to Lie with Statistics": "https://images.unsplash.com/photo-1509228468518-180dd4864904?auto=format&fit=crop&w=500&q=85",
  "The Lean Startup": "https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=500&q=85",
  Sapiens: "https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=500&q=85",
};

BOOKS.forEach(book => { book.coverImage = book.coverImage || BOOK_COVERS[book.title] || ""; });

export const COLLAB_TYPES = [
  "Proposition de recherche", "Proposition de livre", "Proposition d'article",
  "Invitation à intervenir", "Partenariat", "Interview",
  "Participation à une étude", "Demande générale",
];

export const ADMIN_COLLABS = [
  { id: "c1", nom: "Awa Traoré", org: "Université de Dakar", type: "Proposition de recherche", statut: "Nouveau", date: "2026-08-12" },
  { id: "c2", nom: "Julien Marchand", org: "France Culture", type: "Interview", statut: "En cours", date: "2026-08-09" },
  { id: "c3", nom: "Fatou Ndiaye", org: "ONG Terra Nova", type: "Partenariat", statut: "En cours", date: "2026-08-05" },
  { id: "c4", nom: "Marc Dubois", org: "Éditions Kalao", type: "Proposition de livre", statut: "Archivé", date: "2026-07-22" },
  { id: "c5", nom: "Ingrid Sowah", org: "Indépendante", type: "Demande générale", statut: "Nouveau", date: "2026-07-19" },
];

export function fmtDate(d) {
  return new Date(d).toLocaleDateString("fr-FR", { day: "numeric", month: "long", year: "numeric" });
}
