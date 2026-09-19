-- ============================================================================
-- seed_demo_articles.sql
-- Articles ORIGINAUX (pas copiés d'un autre site), un par thème de la
-- taxonomie éditoriale (voir ARTICLE_THEMES dans src/lib/contentTypes.js),
-- pour prouver que le formulaire dashboard couvre bien toutes les catégories
-- avec la même logique (thème/sous-thème/type de contenu/tags/mise en avant).
-- À exécuter APRÈS 011_articles_editorial_taxonomy.sql et
-- 012_scheduled_publish_visibility.sql. Optionnel, à lancer une seule fois.
-- ============================================================================

-- cover_image utilise picsum.photos (service de photos libres, générées à
-- partir d'une seed déterministe) — uniquement pour que les cartes aient une
-- image de démonstration cohérente ; à remplacer par de vraies images une
-- fois les articles édités dans le dashboard.
insert into public.articles
  (title, subtitle, cover_image, summary, content, tags, theme, subtheme, content_type, status, published_at, featured)
values
  (
    'Ce que l''IA générative change vraiment au travail de rédaction',
    'Entre gain de temps réel et nouvelles dépendances, un état des lieux sans emballement.',
    'https://picsum.photos/seed/yewtod-ia-travail/1200/700',
    'Un tour d''horizon de ce que les outils de génération de texte modifient concrètement dans les métiers d''écriture — au-delà du débat pour ou contre.',
    'Depuis deux ans, les rédactions, cabinets d''études et équipes marketing ont massivement adopté les outils d''IA générative pour accélérer certaines tâches : premiers jets, reformulation, synthèse de sources longues.' || chr(10) || chr(10) ||
    'Introduction' || chr(10) ||
    'La question n''est plus de savoir si ces outils seront utilisés, mais comment ils redistribuent le temps de travail entre recherche, écriture et vérification.' || chr(10) || chr(10) ||
    'Ce que l''automatisation déplace réellement' || chr(10) ||
    'Le gain de temps se concentre sur les tâches mécaniques — mise en forme, résumé, reformulation — pendant que la vérification des faits et le jugement éditorial restent, eux, irréductiblement humains et prennent proportionnellement plus de place.' || chr(10) || chr(10) ||
    'Conclusion' || chr(10) ||
    'Le travail rédactionnel ne disparaît pas, il se redéplace vers ce que ces outils ne savent pas faire : vérifier, choisir un angle, assumer une responsabilité éditoriale.',
    array['intelligence artificielle', 'travail', 'rédaction', 'automatisation'],
    'Intelligence artificielle', 'Automatisation du travail', 'dossier', 'published', now() - interval '3 days', true
  ),
  (
    'Le chômage structurel, une définition qui sert souvent à mal débattre',
    null,
    'https://picsum.photos/seed/yewtod-chomage/1200/700',
    'Une fiche de référence pour clarifier un terme économique fréquemment confondu avec le chômage conjoncturel.',
    'Le chômage structurel désigne la part du chômage qui persiste indépendamment du cycle économique : inadéquation entre les qualifications disponibles et celles recherchées, rigidités du marché du travail, ou transformations sectorielles durables (automatisation, déclin d''une industrie).' || chr(10) || chr(10) ||
    'Il se distingue du chômage conjoncturel, qui varie avec les phases de croissance ou de récession et se résorbe généralement avec la reprise économique — sans réforme structurelle nécessaire.' || chr(10) || chr(10) ||
    'Cette distinction est régulièrement instrumentalisée dans le débat public : attribuer un chômage élevé au seul cycle économique, ou au contraire à des causes structurelles, oriente des politiques publiques très différentes (relance budgétaire vs réformes du marché du travail).',
    array['économie', 'chômage', 'définition'],
    'Économie', 'Macroéconomie', 'definition', 'published', now() - interval '10 days', false
  ),
  (
    'Pourquoi certains quartiers cumulent les mêmes difficultés depuis des décennies',
    'Un phénomène que les statistiques agrégées nationales ont tendance à effacer.',
    'https://picsum.photos/seed/yewtod-quartiers/1200/700',
    'Les inégalités territoriales se reproduisent souvent à l''identique sur plusieurs générations, dans les mêmes zones — un constat qui interroge l''efficacité des politiques de la ville.',
    'Un même quartier peut rester en tête des indicateurs de pauvreté, de chômage ou de décrochage scolaire pendant trente ans, malgré plusieurs vagues de rénovation urbaine ou de dispositifs sociaux ciblés.' || chr(10) || chr(10) ||
    'Introduction' || chr(10) ||
    'Ce phénomène de reproduction territoriale des inégalités est documenté depuis longtemps en sociologie urbaine, sans qu''un consensus existe sur les leviers réellement efficaces pour l''enrayer.' || chr(10) || chr(10) ||
    'Effet de quartier ou effet de composition ?' || chr(10) ||
    'Le débat porte sur la part de responsabilité du lieu lui-même (isolement, offre de services, image) par rapport à la simple concentration de ménages déjà vulnérables — une distinction qui change radicalement les politiques à privilégier.' || chr(10) || chr(10) ||
    'Conclusion' || chr(10) ||
    'Sans mixité sociale réelle dans le parc de logements, les dispositifs ciblés sur un quartier isolé ont montré des résultats durablement limités.',
    array['inégalités', 'urbanisme', 'politique de la ville'],
    'Sociologie', 'Inégalités', 'dossier', 'published', now() - interval '6 days', false
  ),
  (
    'La gouvernance multi-niveaux, une définition pour s''y retrouver',
    null,
    'https://picsum.photos/seed/yewtod-gouvernance/1200/700',
    'Un terme de science politique de plus en plus utilisé pour décrire des décisions publiques éclatées entre plusieurs échelons.',
    'La gouvernance multi-niveaux désigne un mode de décision publique où l''autorité et les responsabilités sont partagées entre plusieurs échelons de gouvernement — local, régional, national, supranational — sans qu''un seul niveau détienne le dernier mot sur toutes les questions.' || chr(10) || chr(10) ||
    'Le concept s''est surtout développé pour décrire le fonctionnement de l''Union européenne, où une même politique publique (agricole, environnementale) implique simultanément des institutions communautaires, des États membres et des collectivités locales.' || chr(10) || chr(10) ||
    'Il est aujourd''hui mobilisé plus largement pour analyser toute situation où la coordination entre échelons, plutôt que la hiérarchie stricte, devient la norme de fonctionnement.',
    array['science politique', 'gouvernance', 'définition'],
    'Science politique', 'Gouvernance', 'definition', 'published', now() - interval '8 days', false
  ),
  (
    'Un accord commercial régional entre en vigueur ce mois-ci',
    null,
    'https://picsum.photos/seed/yewtod-commerce/1200/700',
    'Un point rapide sur une actualité de politique commerciale, sans analyse approfondie.',
    'Un nouvel accord de libre-échange régional entre en application ce mois-ci, après plusieurs années de négociations, réduisant les droits de douane sur un large éventail de produits entre les pays signataires.' || chr(10) || chr(10) ||
    'Les analystes restent partagés sur son impact réel à court terme, certains secteurs bénéficiant d''un accès facilité aux marchés voisins pendant que d''autres redoutent une concurrence accrue sur leur marché intérieur.',
    array['commerce international', 'actualité', 'politiques publiques'],
    'Science politique', 'Relations internationales', 'actualite', 'published', now() - interval '2 days', false
  ),
  (
    'Ce que les enquêtes disent vraiment des migrations ouest-africaines',
    'Loin des idées reçues sur des flux à sens unique vers l''Europe.',
    'https://picsum.photos/seed/yewtod-migrations/1200/700',
    'La majorité des migrations dans la région restent intra-régionales — un fait statistique largement sous-représenté dans le débat public sur les migrations africaines.',
    'Introduction' || chr(10) ||
    'Les données de plusieurs enquêtes démographiques convergent : la grande majorité des mouvements migratoires originaires d''Afrique de l''Ouest se font entre pays de la région, et non vers l''Europe.' || chr(10) || chr(10) ||
    'Un décalage entre perception et données' || chr(10) ||
    'Ce décalage s''explique en partie par la visibilité médiatique très asymétrique des traversées vers l''Europe, comparée à celle des migrations de travail saisonnières ou des déplacements liés aux études, beaucoup plus fréquents mais moins spectaculaires.' || chr(10) || chr(10) ||
    'Conclusion' || chr(10) ||
    'Comprendre l''ampleur réelle de ces mobilités régionales est une condition préalable à toute politique migratoire qui prétend s''appuyer sur les faits plutôt que sur la perception.',
    array['migrations', 'démographie', 'Afrique de l''Ouest'],
    'Anthropologie', 'Migrations', 'dossier', 'published', now() - interval '12 days', false
  ),
  (
    'Qu''est-ce qu''un service écosystémique ?',
    null,
    'https://picsum.photos/seed/yewtod-ecosysteme/1200/700',
    'Une fiche de référence sur un concept central en économie de l''environnement.',
    'Un service écosystémique désigne un bénéfice que les sociétés humaines tirent, directement ou indirectement, du fonctionnement des écosystèmes naturels — pollinisation des cultures, régulation du climat local, épuration de l''eau, ou simplement valeur esthétique et récréative d''un paysage.' || chr(10) || chr(10) ||
    'Le concept a pris une importance croissante dans les politiques publiques environnementales, car il permet de donner une traduction économique — et donc négociable dans les arbitrages budgétaires — à des fonctions naturelles qui, autrement, n''apparaissent dans aucun compte.' || chr(10) || chr(10) ||
    'Il reste toutefois débattu : certains y voient un outil pragmatique de protection de la nature, d''autres critiquent le risque de réduire le vivant à sa seule valeur monétaire.',
    array['environnement', 'économie de l''environnement', 'définition'],
    'Environnement & société', 'Ressources', 'definition', 'published', now() - interval '9 days', false
  ),
  (
    'Le redoublement recule, mais les débats sur son efficacité restent vifs',
    'Un sujet où les résultats de la recherche et les pratiques du terrain divergent depuis longtemps.',
    'https://picsum.photos/seed/yewtod-redoublement/1200/700',
    'Malgré des recherches largement défavorables au redoublement, la pratique reste ancrée dans plusieurs systèmes éducatifs — un cas d''école du décalage entre preuves et politiques publiques.',
    'Introduction' || chr(10) ||
    'La plupart des études comparatives internationales concluent que le redoublement a un effet limité, voire négatif, sur la réussite scolaire ultérieure des élèves concernés, tout en ayant un coût budgétaire élevé pour les systèmes éducatifs.' || chr(10) || chr(10) ||
    'Pourquoi la pratique persiste malgré tout' || chr(10) ||
    'Les enseignants et les familles y voient souvent un signal nécessaire de rigueur et une réponse de bon sens à un retard constaté, ce qui rend la pratique difficile à faire disparaître par la seule preuve statistique.' || chr(10) || chr(10) ||
    'Conclusion' || chr(10) ||
    'Le vrai enjeu n''est peut-être pas redoubler ou non, mais ce qui est mis en place en amont pour éviter que le retard ne s''accumule.',
    array['éducation', 'redoublement', 'politiques publiques'],
    'Éducation', 'Systèmes éducatifs', 'dossier', 'published', now() - interval '15 days', false
  ),
  (
    'Trois pays ont relevé leur salaire minimum ce trimestre',
    null,
    'https://picsum.photos/seed/yewtod-salaire/1200/700',
    'Un tour rapide de l''actualité sociale récente, sans analyse approfondie.',
    'Ce trimestre, trois pays ont annoncé une revalorisation de leur salaire minimum, dans des contextes très différents : ajustement à l''inflation dans un cas, réforme structurelle dans les deux autres.' || chr(10) || chr(10) ||
    'Ces décisions interviennent alors que le débat sur l''indexation automatique des salaires reste vif dans plusieurs économies développées, entre partisans d''une protection du pouvoir d''achat et inquiétudes sur l''effet emploi.',
    array['économie', 'salaire minimum', 'actualité'],
    'Économie', 'Développement', 'actualite', 'published', now() - interval '1 day', false
  ),
  (
    'Ce qu''une brève histoire des idées peut apprendre sur nos débats actuels',
    null,
    'https://picsum.photos/seed/yewtod-idees/1200/700',
    'Une courte note sur la manière dont d''anciens débats philosophiques ressurgissent sous une forme nouvelle.',
    'Beaucoup de controverses présentées comme inédites — sur la nature humaine, le rôle de la technique, ou les limites de la raison — reformulent en réalité des débats déjà anciens, parfois vieux de plusieurs siècles, sous un habillage contemporain.',
    array['histoire des idées', 'philosophie', 'brève'],
    'Idées & débats', 'Histoire des idées', 'breve', 'published', now() - interval '4 days', false
  );
