import { T } from "./theme.js";

/* ============================================================
   DONNÉES DE DÉMONSTRATION
============================================================= */

export const CATEGORIES = [
  "Articles", "Rapports", "Études", "Notes de recherche",
  "Séries documentaires", "Expérimentations", "Visualisations de données",
];

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
];

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
