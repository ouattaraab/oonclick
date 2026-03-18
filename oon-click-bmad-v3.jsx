import { useState } from "react";

const DOMAINS = [
  {
    id: "gamification",
    icon: "🎮",
    label: "Gamification & Engagement",
    agent: "UX Strategist",
    accent: "#f97316",
    features: [
      {
        name: "Système de Niveaux Abonnés",
        priority: "HIGH",
        impact: "Rétention +40%",
        description: "4 niveaux : Explorateur → Ambassadeur → Expert → Élite. Chaque niveau débloque un taux de rémunération amélioré par pub vue.",
        details: [
          "Explorateur (0–5k FCFA cumulés) : taux standard",
          "Ambassadeur (5k–50k FCFA) : +10% par pub",
          "Expert (50k–200k FCFA) : +20% par pub + priorité sur les pubs premium",
          "Élite (200k+ FCFA) : +30% + accès aux campagnes exclusives",
        ],
        stories: ["US-029 : Afficher le niveau et barre de progression dans le profil", "US-030 : Notifier l'abonné lors d'un passage de niveau"],
      },
      {
        name: "Streaks & Défis Quotidiens",
        priority: "HIGH",
        impact: "DAU +35%",
        description: "Récompenses pour les abonnés qui ouvrent l'application et consultent au moins une pub par jour consécutif.",
        details: [
          "Streak 7 jours : bonus +200 FCFA",
          "Streak 30 jours : bonus +1 000 FCFA + badge",
          "Défi hebdomadaire : 'Visionne 10 pubs cette semaine → +500 FCFA'",
          "Flamme de streak visible sur le profil (pression sociale positive)",
        ],
        stories: ["US-031 : Moteur de streak avec reset automatique à minuit", "US-032 : Écran de défi hebdomadaire configurable depuis l'admin"],
      },
      {
        name: "Badges & Collection",
        priority: "MEDIUM",
        impact: "Engagement +25%",
        description: "Système de badges collectionnables attribués automatiquement selon les actions de l'abonné.",
        details: [
          "Badges d'activité : '1ère pub vue', '100 pubs vues', 'Fidèle 1 an'",
          "Badges catégoriels : 'Fan de Tech', 'Amateur de Mode'…",
          "Badges sociaux : 'Parrain ×5', 'Top Ambassadeur du mois'",
          "Les badges rares débloquent des bonus ponctuels",
        ],
        stories: ["US-033 : Moteur de badges avec triggers configurables", "US-034 : Galerie badges sur profil public abonné"],
      },
      {
        name: "Classement & Leaderboard",
        priority: "MEDIUM",
        impact: "Compétition saine",
        description: "Tableau des meilleurs abonnés par région, par ville, par tranche d'âge. Visible publiquement (pseudo + avatar).",
        details: [
          "Top 10 abonnés de la semaine dans votre ville",
          "Récompense mensuelle pour le Top 3 national : 5 000, 3 000, 1 000 FCFA bonus",
          "Classement opt-in (respect vie privée)",
          "Widget 'Votre rang cette semaine' dans l'app",
        ],
        stories: ["US-035 : API classement avec cache Redis (refresh toutes les heures)", "US-036 : Page classement avec filtres région/ville/âge"],
      },
      {
        name: "Scratch & Win (Pub Mystère)",
        priority: "MEDIUM",
        impact: "Viralité +20%",
        description: "Certaines pubs premium sont présentées comme des 'cartes à gratter' : l'abonné gratte virtuellement pour révéler l'offre ET gagner un montant bonus surprise.",
        details: [
          "Annonceur paie un supplément pour le format Scratch (ex. 150 FCFA/abonné)",
          "L'abonné gagne entre 20 et 500 FCFA bonus selon la carte",
          "Mécanique de gamble légère, sans risque de perte pour l'abonné",
          "Très fort taux d'ouverture prévu (curiosité naturelle)",
        ],
        stories: ["US-037 : Format pub 'Scratch' avec animation canvas", "US-038 : Générateur de montants aléatoires pondérés (admin)"],
      },
      {
        name: "Mission Board (Quêtes à la Carte)",
        priority: "HIGH",
        impact: "Sessions longues +50%",
        description: "Un tableau de missions disponibles chaque semaine que l'abonné peut choisir d'accomplir pour des récompenses ciblées, comme dans un RPG.",
        details: [
          "Missions variées : 'Regarde 3 pubs de la catégorie Alimentaire', 'Complète ton profil à 100%', 'Invite 1 ami'",
          "Missions normales, difficiles et légendaires (récompenses croissantes)",
          "Limite de 5 missions actives simultanément pour ne pas surcharger",
          "Missions sponsorisées : un annonceur finance une mission centrée sur sa marque",
          "Renouvellement automatique chaque lundi à minuit",
        ],
        stories: ["US-037b : Moteur de missions configurable depuis l'admin", "US-038b : Interface Mission Board dans l'app abonné"],
      },
      {
        name: "Roue de la Fortune Hebdomadaire",
        priority: "MEDIUM",
        impact: "Ouverture app +30%",
        description: "Chaque semaine, l'abonné ayant regardé au moins X pubs gagne le droit de tourner une roue pour un bonus surprise.",
        details: [
          "Débloquée après 5 pubs vues dans la semaine",
          "Gains possibles : 100, 200, 500, 1 000 FCFA ou 'Rejoue'",
          "Probabilités configurables dans l'admin",
          "Crée un rendez-vous hebdomadaire fort dans l'app",
          "Les annonceurs peuvent sponsoriser la roue (logo affiché pendant le spin)",
        ],
        stories: ["US-038c : Moteur de roue avec probabilités pondérées", "US-038d : Animation roue + attribution bonus automatique"],
      },
    ],
  },
  {
    id: "social",
    icon: "🌐",
    label: "Social & Viralité",
    agent: "Growth Hacker",
    accent: "#10b981",
    features: [
      {
        name: "Programme de Parrainage Structuré",
        priority: "HIGH",
        impact: "Acquisition ×3",
        description: "Chaque abonné dispose d'un lien/code de parrainage unique. Double récompense : le parrain ET le filleul gagnent.",
        details: [
          "Filleul s'inscrit via lien : parrain gagne +300 FCFA quand filleul complète son profil",
          "Bonus supplémentaire quand le filleul atteint son 1er retrait",
          "Niveau Parrain : 1–5 filleuls = Bronze, 6–20 = Silver, 21+ = Gold (commissionnement croissant)",
          "Dashboard parrainage : nb filleuls, gains générés, conversions",
        ],
        stories: ["US-039 : Génération et tracking de codes de parrainage uniques", "US-040 : Dashboard parrainage abonné"],
      },
      {
        name: "Partage Social de Pub",
        priority: "MEDIUM",
        impact: "Portée organique",
        description: "L'abonné peut partager une pub sur WhatsApp, Facebook, X. S'il partage ET que son contact clique et s'inscrit → bonus.",
        details: [
          "Bouton 'Partager et gagner' sur chaque pub",
          "Lien traqué unique par abonné + pub",
          "Conversion : +150 FCFA si contact s'inscrit via ce lien partagé",
          "L'annonceur peut activer/désactiver le partage pour sa campagne",
        ],
        stories: ["US-041 : Génération de lien partageable traqué par pub/abonné", "US-042 : Attribution de bonus sur conversion depuis lien partagé"],
      },
      {
        name: "Profil Public & Social Proof",
        priority: "LOW",
        impact: "Confiance",
        description: "Chaque abonné a une mini page profil publique avec ses badges, son niveau, son streak, et son 'OON Score'.",
        details: [
          "URL unique : oon.click/@pseudo",
          "Affiche : niveau, badges, nb pubs vues, ancienneté",
          "OON Score (0–1000) basé sur engagement, fiabilité, ancienneté",
          "Utilisé par les annonceurs pour cibler les profils les plus engagés",
        ],
        stories: ["US-043 : Génération de page profil publique opt-in", "US-044 : Calcul et affichage OON Score"],
      },
      {
        name: "Challenges Communautaires",
        priority: "MEDIUM",
        impact: "Cohésion",
        description: "Défis collectifs : 'Si 10 000 abonnés regardent la pub X cette semaine, tout le monde reçoit +200 FCFA bonus.'",
        details: [
          "Lancés par l'admin ou sponsorisés par un annonceur",
          "Barre de progression collective visible dans l'app",
          "Crée un effet FOMO et d'entraînement communautaire",
          "L'annonceur peut financer le bonus collectif (coût marketing)",
        ],
        stories: ["US-045 : Module challenge communautaire avec barre de progression", "US-046 : Gestion des challenges par l'admin"],
      },
      {
        name: "Groupes & Cercles d'Abonnés",
        priority: "MEDIUM",
        impact: "Rétention communautaire",
        description: "Les abonnés peuvent rejoindre ou créer des cercles thématiques (ex. 'Tech Abidjan', 'Mamans Korhogo'). Les annonceurs peuvent cibler un cercle précis.",
        details: [
          "Cercles créés par les abonnés ou par l'admin (catégories, villes, centres d'intérêt)",
          "Fil d'actualité du cercle : pubs spéciales, challenges exclusifs, résultats",
          "Annonceur peut acheter une campagne 'Cercle exclusif' avec ciblage ultra-précis",
          "Animateur de cercle : rôle spécial avec bonus de modération mensuel",
          "Limite : 1 000 membres par cercle pour garder la qualité d'engagement",
        ],
        stories: ["US-046b : Création et gestion de cercles thématiques", "US-046c : Ciblage de campagne sur un cercle spécifique"],
      },
    ],
  },
  {
    id: "advertiser",
    icon: "📊",
    label: "Outils Annonceur Avancés",
    agent: "Product Manager",
    accent: "#6366f1",
    features: [
      {
        name: "A/B Testing de Campagnes",
        priority: "HIGH",
        impact: "ROI annonceur +30%",
        description: "Un annonceur peut créer 2 variantes d'une même publicité (A et B). oon.click distribue automatiquement 50/50 pour identifier la plus performante.",
        details: [
          "2 visuels / textes / CTA différents sur la même campagne",
          "Rapport comparatif automatique : taux d'ouverture, engagement, feedback",
          "Arrêt automatique de la variante perdante après X% de confiance statistique",
          "Disponible dès 500 abonnés ciblés minimum",
        ],
        stories: ["US-047 : Création de campagne avec 2 variantes A/B", "US-048 : Moteur de distribution 50/50 et rapport comparatif"],
      },
      {
        name: "Audiences Lookalike",
        priority: "HIGH",
        impact: "Ciblage précision ×2",
        description: "L'annonceur définit un 'abonné idéal' et le système trouve les abonnés qui lui ressemblent le plus selon leur profil.",
        details: [
          "L'annonceur charge une liste de ses meilleurs clients (email/phone)",
          "L'algorithme identifie les abonnés oon.click avec profil similaire",
          "Ciblage basé sur similarité vectorielle (âge, ville, métier, revenus)",
          "Portée estimée affichée avant l'achat de la campagne",
        ],
        stories: ["US-049 : Import de liste de clients et calcul de similarité", "US-050 : Interface de sélection d'audience lookalike"],
      },
      {
        name: "Retargeting (Reciblage)",
        priority: "HIGH",
        impact: "Conversion +50%",
        description: "Cibler à nouveau les abonnés ayant vu une campagne précédente pour renforcer le message (séquence publicitaire).",
        details: [
          "Créer une campagne 'suite de' une campagne terminée",
          "Cibler : 'tous ceux qui ont vu la campagne X' ou 'ceux qui n'ont pas vu'",
          "Séquence narrative : pub 1 (teasing) → pub 2 (révélation) → pub 3 (offre)",
          "Coût identique à une campagne normale",
        ],
        stories: ["US-051 : Segmentation audience basée sur historique de campagnes", "US-052 : Interface de création de campagne de retargeting"],
      },
      {
        name: "Boost de Campagne",
        priority: "MEDIUM",
        impact: "Flexibilité",
        description: "Après lancement, l'annonceur peut 'booster' sa campagne : augmenter le budget, élargir les critères, ou accélérer la diffusion.",
        details: [
          "Bouton 'Booster' sur une campagne active",
          "Options : +500 abonnés, +1000 abonnés, diffusion urgente (24h)",
          "Paiement additionnel immédiat via Mobile Money",
          "Utile pour les campagnes saisonnières de dernière minute",
        ],
        stories: ["US-053 : Module boost campagne avec paiement additionnel", "US-054 : Accélérateur de diffusion (mode urgent)"],
      },
      {
        name: "Bibliothèque de Templates Publicitaires",
        priority: "MEDIUM",
        impact: "Adoption PME",
        description: "Galerie de modèles prêts-à-l'emploi pour les petits annonceurs sans agence créative.",
        details: [
          "50+ templates : promotions, soldes, ouverture de boutique, événements",
          "L'annonceur personnalise : logo, couleurs, texte, photo produit",
          "Éditeur intégré simple (drag & drop)",
          "Formats exportés automatiquement pour image/vidéo/texte",
        ],
        stories: ["US-055 : Éditeur de pub simplifié avec templates", "US-056 : Galerie de templates catégorisés (alimentaire, mode, services…)"],
      },
      {
        name: "Prédiction d'Audience & Reach Estimé",
        priority: "HIGH",
        impact: "Confiance annonceur",
        description: "Avant tout paiement, l'annonceur voit en temps réel le nombre d'abonnés correspondant à ses critères, le coût total, et le délai de diffusion estimé.",
        details: [
          "Mise à jour dynamique lors de la modification des critères",
          "Indicateur : 'Votre annonce peut toucher 12 450 abonnés'",
          "Alerte si les critères sont trop restrictifs (< 100 abonnés matchés)",
          "Suggestions automatiques pour élargir l'audience si trop faible",
        ],
        stories: ["US-057 : API de comptage d'abonnés en temps réel selon critères", "US-058 : Widget de reach estimé dans le formulaire de campagne"],
      },
      {
        name: "Annonceur Vérifié (Badge de Confiance)",
        priority: "HIGH",
        impact: "Qualité & légitimité",
        description: "Les annonceurs ayant fourni des documents légaux (RCCM, NIF) reçoivent un badge 'Annonceur Vérifié' visible sur leurs pubs. Renforce la confiance des abonnés.",
        details: [
          "Processus de vérification : RCCM ou NIF + pièce d'identité dirigeant",
          "Badge affiché sur toutes les pubs de l'annonceur vérifié",
          "Accès aux formats premium (Scratch, Série, Sondage) réservés aux vérifiés",
          "Les annonceurs non vérifiés ont un plafond de campagne (ex. 50 000 FCFA max)",
          "Crée un écosystème annonceur de qualité et réduit les arnaques",
        ],
        stories: ["US-058b : Workflow de vérification annonceur avec upload documents", "US-058c : Badge vérifié affiché dynamiquement sur les pubs"],
      },
    ],
  },
  {
    id: "formats",
    icon: "✨",
    label: "Formats Publicitaires Innovants",
    agent: "Creative Director",
    accent: "#ec4899",
    features: [
      {
        name: "Pub Interactive : Sondage / Quiz",
        priority: "HIGH",
        impact: "Engagement ×4",
        description: "L'annonceur joint un sondage ou quiz à sa pub. L'abonné répond et gagne un bonus. L'annonceur reçoit des données de marché précieuses.",
        details: [
          "1 à 3 questions max après visionnage de la pub",
          "Types : QCM, notation étoiles, champ texte court",
          "Abonné : +X FCFA bonus par réponse complète",
          "Annonceur : rapport des réponses en temps réel",
          "Coût : +30 FCFA/abonné pour activer le module sondage",
        ],
        stories: ["US-059 : Builder de sondage dans le formulaire campagne", "US-060 : Affichage post-pub du sondage + crédit bonus"],
      },
      {
        name: "Pub Countdown : Offre Limitée",
        priority: "MEDIUM",
        impact: "Urgence & FOMO",
        description: "Pub avec un compte à rebours visible. L'annonceur crée une offre qui expire dans X heures/jours, visible en temps réel.",
        details: [
          "Horloge animée intégrée dans le visuel de la pub",
          "Ex : 'Promo -50% expire dans 23:47:12'",
          "Lien vers le site annonceur ou numéro WhatsApp Business",
          "Génère une urgence naturelle et augmente les conversions",
        ],
        stories: ["US-061 : Composant countdown configurable sur pub", "US-062 : CTA cliquable vers URL externe sur pub (tracking UTM)"],
      },
      {
        name: "Pub Narrative : Série en Épisodes",
        priority: "MEDIUM",
        impact: "Mémorisation marque",
        description: "L'annonceur crée une série de 3 à 5 vidéos/images courtes diffusées séquentiellement sur plusieurs jours au même abonné.",
        details: [
          "Ep.1 (Jour 1), Ep.2 (Jour 3), Ep.3 (Jour 7)…",
          "L'abonné reçoit chaque épisode dans l'ordre",
          "Rémunération à chaque épisode vu",
          "Notif : 'L'épisode 2 de [Marque X] est disponible !'",
          "Format storytelling très engageant pour les grandes marques",
        ],
        stories: ["US-063 : Campagne multi-épisodes avec planification séquentielle", "US-064 : Tracking de complétion de série par abonné"],
      },
      {
        name: "Audio Ads (Podcast-style)",
        priority: "HIGH",
        impact: "Multitâche",
        description: "Pub audio de 30s que l'abonné peut écouter en background pendant qu'il fait autre chose. Idéal en zones à faible connexion.",
        details: [
          "Lecture audio en background (même app minimisée)",
          "Notification lock-screen : 'En train d'écouter [Marque X]'",
          "Crédit déclenché après 30s d'écoute continue (timer serveur)",
          "Annonceur peut uploader jingle, voix off, ou spot radio",
        ],
        stories: ["US-065 : Lecteur audio background avec lock-screen widget", "US-066 : Validation écoute 30s via timer côté serveur"],
      },
      {
        name: "Pub Locale : Géolocalisée",
        priority: "HIGH",
        impact: "PME locales",
        description: "L'annonceur cible les abonnés dans un rayon de X km autour d'une adresse. Parfait pour les commerces locaux.",
        details: [
          "Critère de ciblage : 'Abonnés à moins de 5 km de [Adresse]'",
          "Annonceur entre l'adresse de sa boutique + rayon",
          "L'app affiche la distance entre l'abonné et le commerce",
          "CTA : 'Venez nous voir → [itinéraire Google Maps]'",
          "Ouverture d'un marché PME/artisans très large en Côte d'Ivoire",
        ],
        stories: ["US-067 : Critère de ciblage géographique (rayon km)", "US-068 : CTA directions vers l'annonceur depuis la pub"],
      },
      {
        name: "Pub Augmentée (AR Viewer)",
        priority: "LOW",
        impact: "Innovation & WOW effect",
        description: "Format expérimental permettant à l'abonné d'utiliser la caméra pour voir un produit en réalité augmentée (ex. essayer un meuble chez soi, porter des lunettes).",
        details: [
          "Technologie WebAR (fonctionne sans app dédiée via Flutter WebView)",
          "Compatible produits : ameublement, mode, cosmétiques, décoration",
          "Rémunération doublée pour les pubs AR (valeur perçue élevée)",
          "Annonceur fournit le modèle 3D ou la photo du produit",
          "Fonctionnalité premium très différenciante sur le marché africain",
        ],
        stories: ["US-068b : Intégration WebAR dans le viewer pub Flutter", "US-068c : Format campagne AR avec upload modèle 3D"],
      },
    ],
  },
  {
    id: "trust",
    icon: "🛡️",
    label: "Confiance, Qualité & Conformité",
    agent: "Security & Legal",
    accent: "#ef4444",
    features: [
      {
        name: "OON Trust Score",
        priority: "HIGH",
        impact: "Anti-fraude systémique",
        description: "Chaque abonné reçoit un score de confiance (0–100) basé sur son comportement. Les abonnés à faible score sont exclus des campagnes premium.",
        details: [
          "Facteurs positifs : ancienneté, streak, profil complet, vues légitimes",
          "Facteurs négatifs : tentatives de fraude, vues trop rapides, multi-comptes",
          "Score < 40 : accès limité (moins de pubs disponibles)",
          "Score < 20 : suspension temporaire du compte",
          "Transparent pour l'abonné : 'Votre trust score est de 87/100'",
        ],
        stories: ["US-079 : Moteur de calcul Trust Score avec facteurs pondérés", "US-080 : Interface de consultation du Trust Score par l'abonné"],
      },
      {
        name: "Modération IA des Contenus",
        priority: "HIGH",
        impact: "Sécurité plateforme",
        description: "Analyse automatique des contenus publicitaires avant publication pour détecter contenu inapproprié, trompeur ou illégal.",
        details: [
          "Scan automatique via API de modération (AWS Rekognition ou similaire)",
          "Détection : nudité, violence, arnaques, faux médicaments",
          "Pré-modération auto + validation humaine pour les cas ambigus",
          "Délai de modération affiché à l'annonceur : 'Sous 2h ouvrables'",
          "Liste noire de mots-clés et de domaines d'activité interdits",
        ],
        stories: ["US-081 : Intégration API de modération contenu", "US-082 : Workflow modération humaine pour cas ambigus"],
      },
      {
        name: "Signalement & Feedback sur les Pubs",
        priority: "HIGH",
        impact: "Qualité & légitimité",
        description: "L'abonné peut signaler une pub ou lui attribuer une note. Les pubs mal notées sont automatiquement suspendues.",
        details: [
          "Bouton 'Signaler' : arnaque, contenu choquant, information fausse",
          "Note 1–5 étoiles sur la pertinence de la pub",
          "Seuil d'alerte : 10% de signalements → suspension automatique",
          "Le taux de satisfaction moyen des pubs d'un annonceur est visible dans son profil",
        ],
        stories: ["US-083 : Système de signalement pub avec catégories", "US-084 : Suspension automatique déclenchée par taux de signalement"],
      },
      {
        name: "Centre de Préférences Publicitaires",
        priority: "HIGH",
        impact: "Satisfaction & RGPD",
        description: "L'abonné choisit les catégories de publicités qu'il souhaite ou ne souhaite PAS recevoir. Améliore la pertinence et la satisfaction.",
        details: [
          "Catégories : Alimentaire, Mode, Immobilier, Fintech, Santé, Auto, Tech…",
          "Abonné coche ses préférences et ses exclusions",
          "L'algorithme de matching prend ces préférences en compte",
          "Droit de retrait total (coupe aussi les crédits associés)",
          "Conforme aux exigences RGPD / loi ivoirienne sur les données personnelles",
        ],
        stories: ["US-085 : Interface de gestion des préférences pub", "US-086 : Intégration des préférences dans le moteur de matching"],
      },
      {
        name: "Vérification d'Identité (KYC) Progressive",
        priority: "HIGH",
        impact: "Conformité financière",
        description: "Système KYC en 3 niveaux déclenchés selon les montants en jeu, conforme aux réglementations financières ivoiriennes (BCEAO).",
        details: [
          "KYC Niveau 1 : Téléphone vérifié (OTP) → retrait jusqu'à 10 000 FCFA",
          "KYC Niveau 2 : Photo CNI/Passeport → retrait jusqu'à 100 000 FCFA",
          "KYC Niveau 3 : Selfie + document + adresse → retrait illimité",
          "Données KYC stockées chiffrées, accès admin restreint et loggé",
        ],
        stories: ["US-087 : Workflow KYC progressif avec upload document", "US-088 : Vérification automatique OCR + validation manuelle"],
      },
      {
        name: "Charte Publicitaire & Conformité Sectorielle",
        priority: "HIGH",
        impact: "Légitimité légale",
        description: "Règles claires définissant les catégories de produits autorisés, restreints ou interdits sur la plateforme, conformément à la législation ivoirienne.",
        details: [
          "Secteurs interdits : alcool fort, tabac, jeux d'argent non agréés, médicaments non homologués",
          "Secteurs restreints (vérification requise) : pharmacie, santé, crédit, immobilier",
          "Secteurs libres : alimentaire, mode, tech, services, éducation…",
          "Charte publique consultable par tout annonceur avant inscription",
          "Mise à jour trimestrielle de la charte selon l'évolution légale",
        ],
        stories: ["US-088b : Module de conformité sectorielle dans le formulaire campagne", "US-088c : Blocage automatique des secteurs interdits à la soumission"],
      },
    ],
  },
  {
    id: "intelligence",
    icon: "🧠",
    label: "Intelligence & Analytics",
    agent: "Data Scientist",
    accent: "#06b6d4",
    features: [
      {
        name: "Timing Intelligent (Smart Push)",
        priority: "HIGH",
        impact: "Taux ouverture +60%",
        description: "L'IA analyse l'historique de chaque abonné pour identifier les horaires où il est le plus susceptible d'ouvrir une pub.",
        details: [
          "Analyse : jours/heures d'ouverture des 30 dernières pubs",
          "Score 'disponibilité' par tranche horaire par abonné",
          "Ex : 'Cet abonné ouvre 80% des pubs entre 19h et 21h'",
          "Les campagnes urgentes peuvent bypasser le timing optimal",
        ],
        stories: ["US-089 : Modèle de prédiction de disponibilité abonné", "US-090 : Planificateur de notifications avec timing optimal"],
      },
      {
        name: "Dashboard Insights Annonceur (BI)",
        priority: "HIGH",
        impact: "Valeur perçue annonceur",
        description: "Tableau de bord analytique avancé post-campagne : démographie des viewers, taux de complétion, benchmark secteur.",
        details: [
          "Répartition démographique des viewers (âge, sexe, ville, métier)",
          "Courbe d'ouverture dans le temps (pic d'engagement)",
          "Taux de complétion : % ayant vu la pub jusqu'au bout",
          "Benchmark anonymisé : 'Votre taux est 15% au-dessus de la moyenne du secteur'",
          "Export rapport PDF branded oon.click",
        ],
        stories: ["US-091 : Pipeline analytique post-campagne", "US-092 : Rapport PDF automatique à la clôture de campagne"],
      },
      {
        name: "Recommandations IA pour Annonceurs",
        priority: "MEDIUM",
        impact: "Adoption & fidélisation",
        description: "Suggestions intelligentes pour améliorer les campagnes basées sur les données historiques agrégées.",
        details: [
          "'Votre visuel est trop chargé — essayez le format texte pour ce secteur'",
          "'Vos critères excluent 60% de votre cible potentielle'",
          "'Meilleur jour pour lancer : mercredi entre 18h et 20h'",
          "'Les abonnés 25–35 ans répondent mieux à ce type de pub'",
        ],
        stories: ["US-093 : Moteur de recommandations basé sur règles + ML", "US-094 : Affichage des recommandations dans le dashboard annonceur"],
      },
      {
        name: "Carte de Chaleur Géographique",
        priority: "MEDIUM",
        impact: "Insight territorial",
        description: "Visualisation cartographique de la densité des abonnés par quartier/ville pour aider l'annonceur à choisir sa géographie.",
        details: [
          "Heatmap interactive : densité abonnés par commune (Abidjan, Bouaké, Korhogo…)",
          "Filtrable par critères démographiques",
          "Données anonymisées (jamais de localisation individuelle)",
        ],
        stories: ["US-095 : Génération de heatmap à partir des données profil abonnés", "US-096 : Intégration carte interactive dans le formulaire de campagne"],
      },
      {
        name: "Score de Pertinence Pub (Ad Relevance)",
        priority: "HIGH",
        impact: "Qualité expérience abonné",
        description: "Chaque pub reçoit un score de pertinence calculé par l'IA selon l'adéquation entre le contenu, les critères de ciblage et le profil réel des viewers.",
        details: [
          "Score calculé 24h après le début de la diffusion",
          "Pubs avec score < 40/100 : notification à l'annonceur pour améliorer",
          "Pubs avec score > 80/100 : mise en avant dans les recommandations",
          "L'annonceur peut voir le score dans son dashboard",
          "Critères : taux d'ouverture, temps de visionnage, notes abonnés, signalements",
        ],
        stories: ["US-096b : Moteur de calcul du score de pertinence pub", "US-096c : Alertes annonceur sur score bas + suggestions d'amélioration"],
      },
      {
        name: "Prédiction de Churn Abonné",
        priority: "MEDIUM",
        impact: "Rétention proactive",
        description: "Algorithme détectant les abonnés sur le point d'arrêter d'utiliser l'app, déclenchant automatiquement une campagne de réactivation.",
        details: [
          "Signaux de churn : inactivité > 7 jours, baisse de fréquence, streak cassé",
          "Score de churn (0–100) calculé quotidiennement par abonné",
          "Action automatique sur score > 70 : push 'Vous avez des pubs qui vous attendent + 100 FCFA bonus'",
          "L'admin configure le message et le bonus de réactivation",
          "Rapport mensuel : abonnés réactivés vs churned",
        ],
        stories: ["US-096d : Modèle de prédiction de churn basé sur comportement", "US-096e : Moteur de réactivation automatique avec bonus configurable"],
      },
    ],
  },
  {
    id: "ecosystem",
    icon: "🚀",
    label: "Écosystème & Expansion",
    agent: "Business Strategist",
    accent: "#84cc16",
    features: [
      {
        name: "API Publique pour Annonceurs (Programmatic)",
        priority: "MEDIUM",
        impact: "Scalabilité B2B",
        description: "API REST permettant aux grandes entreprises et agences d'intégrer oon.click dans leurs outils internes.",
        details: [
          "CRUD campagnes via API authentifiée",
          "Webhooks en temps réel : vue confirmée, campagne terminée",
          "Documentation Swagger/OpenAPI publique",
          "Ouvre la porte aux agences gérant 50+ annonceurs",
        ],
        stories: ["US-097 : API publique annonceur avec auth API Key", "US-098 : Documentation interactive et sandbox API"],
      },
      {
        name: "OON for Business (Compte Multi-Annonceurs)",
        priority: "MEDIUM",
        impact: "Agences & grands comptes",
        description: "Un compte agence permettant de gérer plusieurs comptes annonceurs clients depuis une interface unifiée.",
        details: [
          "Dashboard maître avec vue consolidée de toutes les campagnes clients",
          "Facturation centralisée au nom de l'agence",
          "Gestion des droits : chef de projet, validateur, responsable facturation",
          "Commission agence configurable sur les dépenses clients",
        ],
        stories: ["US-099 : Compte Agence avec gestion multi-annonceurs", "US-100 : Tableau de bord consolidé agence"],
      },
      {
        name: "OON SDK (Réseau de Diffusion)",
        priority: "LOW",
        impact: "Réseau élargi",
        description: "SDK que d'autres applications mobiles peuvent intégrer pour diffuser des pubs oon.click à leurs utilisateurs et partager les revenus.",
        details: [
          "SDK Flutter/Android/iOS léger (< 2MB)",
          "L'app partenaire affiche des pubs oon.click dans son interface",
          "Partage de revenus 60% oon.click / 40% app partenaire",
          "Transforme oon.click en ad network africain",
        ],
        stories: ["US-101 : SDK mobile de diffusion pub pour apps partenaires", "US-102 : Dashboard partenaire avec revenus partagés"],
      },
      {
        name: "OON White-Label (SaaS Africa)",
        priority: "LOW",
        impact: "Expansion continentale",
        description: "Proposer la plateforme oon.click en marque blanche à d'autres opérateurs africains voulant lancer leur propre solution de pub rémunérée.",
        details: [
          "Sénégal, Mali, Burkina, Cameroun, Togo : marchés cibles",
          "Chaque opérateur a son domaine, sa monnaie locale, son branding",
          "Plateforme multi-tenant gérée centralement",
          "Revenus SaaS : licence mensuelle + % transactions",
        ],
        stories: ["US-105 : Architecture multi-tenant pour white-label", "US-106 : Interface de configuration par tenant (monnaie, branding, tarifs)"],
      },
      {
        name: "Programme Ambassadeurs Entreprises (B2B2C)",
        priority: "HIGH",
        impact: "Acquisition massive",
        description: "Les entreprises (employeurs, associations, universités) peuvent inviter l'ensemble de leurs membres/employés sur oon.click via un programme dédié.",
        details: [
          "L'entreprise s'enregistre comme 'Partenaire Recruteur'",
          "Génère un lien/code d'invitation groupé pour ses employés/membres",
          "Chaque inscription validée = bonus pour l'entreprise (crédit pub)",
          "Cas cibles : universités (étudiants), syndicats, coopératives, grandes entreprises",
          "Tableau de bord dédié : nb inscrits, actifs, taux de complétion de profil",
        ],
        stories: ["US-106b : Espace Partenaire Recruteur avec dashboard dédié", "US-106c : Génération de liens d'invitation en masse et tracking"],
      },
    ],
  },
  {
    id: "mobile",
    icon: "📱",
    label: "Expérience Mobile & Accessibilité",
    agent: "Mobile UX Engineer",
    accent: "#a78bfa",
    features: [
      {
        name: "Mode Hors-Ligne & File d'Attente",
        priority: "HIGH",
        impact: "Adoption zones rurales",
        description: "Les abonnés en zone à faible connectivité peuvent pré-télécharger les pubs disponibles en Wi-Fi et les visionner hors ligne. Le crédit est attribué à la reconnexion.",
        details: [
          "Téléchargement automatique des 5 prochaines pubs en Wi-Fi/3G",
          "Visionnage possible sans connexion (mode avion inclus)",
          "Crédit en attente synchronisé à la prochaine connexion",
          "Indicateur 'X pubs disponibles hors-ligne' dans l'app",
          "Critique pour le marché ivoirien (zones semi-rurales, coupures réseau)",
        ],
        stories: ["US-107 : Système de pré-téléchargement des pubs disponibles", "US-108 : File de crédits en attente synchronisée à la reconnexion"],
      },
      {
        name: "Mode Ultra-Économie de Data",
        priority: "HIGH",
        impact: "Inclusion numérique",
        description: "Mode activable réduisant drastiquement la consommation data : pubs texte et audio uniquement, images compressées, vidéos désactivées.",
        details: [
          "Activation dans les paramètres ou suggestion automatique sur connexion 2G",
          "Consommation estimée affichée : 'Ce mode utilise 10× moins de données'",
          "Les pubs vidéo ne sont pas distribuées aux abonnés en mode économie",
          "Les annonceurs voient la proportion de leurs viewers en mode économie",
          "Partenariat possible avec opérateurs pour du zero-rating",
        ],
        stories: ["US-109 : Mode économie data avec détection automatique de la qualité réseau", "US-110 : Filtrage des types de pubs selon le mode actif"],
      },
      {
        name: "Application Légère (Lite App)",
        priority: "HIGH",
        impact: "Appareils d'entrée de gamme",
        description: "Version allégée de l'app (< 10 MB) pour les smartphones Android d'entrée de gamme très répandus en Côte d'Ivoire.",
        details: [
          "Version Flutter compilée avec fonctionnalités essentielles uniquement",
          "Pas de vidéo ni AR : texte, image et audio uniquement",
          "Optimisée pour Android 6+ et 1GB RAM",
          "Disponible sur Google Play en complément de l'app principale",
          "Même compte, mêmes crédits, interface simplifiée",
        ],
        stories: ["US-111 : Build Flutter Lite avec feature flags désactivés", "US-112 : Publier l'app Lite séparément sur le Play Store"],
      },
      {
        name: "Notifications Intelligentes & DND",
        priority: "MEDIUM",
        impact: "Satisfaction & non-spam",
        description: "Gestion fine des notifications : l'abonné contrôle les heures de réception, la fréquence maximale par jour, et les types de pubs qui le notifient.",
        details: [
          "Plages horaires de réception configurables ('Ne pas déranger de 22h à 7h')",
          "Maximum X notifications/jour configurable (défaut : 5)",
          "Types : nouvelles pubs, bonus gagné, streak en danger, défis",
          "Désactivation temporaire (mode pause 24h/48h/72h)",
          "Résumé quotidien optionnel : '3 pubs disponibles, 150 FCFA à gagner aujourd'hui'",
        ],
        stories: ["US-113 : Centre de gestion des notifications par l'abonné", "US-114 : Moteur de notification respectant les préférences horaires"],
      },
      {
        name: "Accessibilité (A11Y)",
        priority: "MEDIUM",
        impact: "Inclusivité",
        description: "Adaptation de l'app pour les personnes malvoyantes ou malentendantes, conformément aux standards WCAG 2.1.",
        details: [
          "Compatibilité TalkBack (Android) et VoiceOver (iOS)",
          "Contrastes de couleurs conformes WCAG AA",
          "Sous-titres automatiques sur les pubs vidéo (STT)",
          "Description audio des pubs images (alt-text généré par IA)",
          "Taille de texte ajustable sans casser le layout",
        ],
        stories: ["US-115 : Audit et correction d'accessibilité de l'app Flutter", "US-116 : Génération automatique de sous-titres sur vidéos publicitaires"],
      },
      {
        name: "Widget & Raccourci Écran d'Accueil",
        priority: "MEDIUM",
        impact: "Rappel passif",
        description: "Widget placé sur l'écran d'accueil du smartphone affichant le solde actuel, le streak du jour, et le nombre de pubs disponibles sans ouvrir l'app.",
        details: [
          "Widget Android (homescreen) et iOS (Today View)",
          "Tailles disponibles : petit (solde + streak), moyen (+ nb pubs dispo)",
          "Actualisation toutes les 15 minutes en arrière-plan",
          "Tap sur le widget → ouverture directe sur la 1ère pub disponible",
          "Rappel visuel permanent très efficace pour la rétention quotidienne",
        ],
        stories: ["US-117 : Widget Android avec Glance API (Flutter)", "US-118 : Widget iOS avec WidgetKit integration"],
      },
    ],
  },
  {
    id: "seasonal",
    icon: "🎪",
    label: "Événementiel & Saisonnier",
    agent: "Marketing Strategist",
    accent: "#fbbf24",
    features: [
      {
        name: "Calendrier Publicitaire Africain",
        priority: "HIGH",
        impact: "Pertinence culturelle",
        description: "Intégration d'un calendrier des événements locaux et régionaux permettant aux annonceurs de créer des campagnes liées aux fêtes, saisons et moments culturels.",
        details: [
          "Événements clés intégrés : Ramadan, Tabaski, Noël, Pâques, Fête de l'Indépendance (7 août)",
          "Saisons commerciales : rentrée scolaire, saison des pluies, fêtes de fin d'année",
          "L'annonceur voit un indicateur 'Événement en approche' lors de la création de campagne",
          "Templates saisonniers adaptés à chaque fête dans la bibliothèque",
          "Taux de diffusion prioritaire pour les campagnes saisonnières actives",
        ],
        stories: ["US-119 : Module calendrier événementiel configurable en admin", "US-120 : Templates et suggestions saisonnières dans le créateur de campagne"],
      },
      {
        name: "Campagnes Flash (OON Flash)",
        priority: "HIGH",
        impact: "Urgence & réactivité",
        description: "Format de campagne express : diffusée à tous les abonnés disponibles dans les 2 heures suivant la validation. Pour les annonces urgentes et les promotions de dernière minute.",
        details: [
          "Processus simplifié : formulaire court (5 champs), paiement immédiat, diffusion express",
          "Disponible uniquement pour les annonceurs vérifiés",
          "Majoration tarifaire : +20% sur le prix standard (ex. 120 FCFA/abonné)",
          "Notification push prioritaire avec son distinct",
          "Cas d'usage : ventes flash, événements du jour, météo/actualité",
        ],
        stories: ["US-121 : Workflow campagne flash avec validation < 30 min", "US-122 : Système de notification prioritaire pour les pubs flash"],
      },
      {
        name: "OON Days (Journées Spéciales)",
        priority: "MEDIUM",
        impact: "Pics d'engagement",
        description: "Journées thématiques organisées par oon.click (ex. 'OON Day Tech', 'OON Day Mode') où les rémunérations sont doublées pour les abonnés et les pubs plus visibles.",
        details: [
          "1 OON Day par mois planifié et annoncé 1 semaine à l'avance",
          "Rémunération ×2 pour les pubs du thème du jour",
          "Les annonceurs du secteur concerné bénéficient d'une diffusion prioritaire",
          "Campagne de communication (email, push, réseaux sociaux oon.click)",
          "Crée des pics d'activité prévisibles pour la plateforme",
        ],
        stories: ["US-123 : Module OON Days avec planning mensuel en admin", "US-124 : Moteur de multiplication de rémunération selon le thème actif"],
      },
      {
        name: "Campagnes de Sensibilisation (Institutions)",
        priority: "MEDIUM",
        impact: "Positionnement citoyen",
        description: "Format spécial à tarif réduit ou gratuit pour les campagnes d'intérêt général : santé publique, éducation, sécurité routière, environnement.",
        details: [
          "Tarif préférentiel pour ONG, ministères, mairies, établissements scolaires",
          "Badge 'Message citoyen' affiché sur la pub (visibilité différenciée)",
          "Pas de rémunération abonné sur ces pubs (ou rémunération symbolique 10 FCFA)",
          "Plafond mensuel : max 5% des diffusions réservé aux campagnes citoyennes",
          "Valorisation RSE et image de marque très forte pour oon.click",
        ],
        stories: ["US-125 : Tarification institutionnelle configurable en admin", "US-126 : Badge 'Message citoyen' et workflow de validation dédié"],
      },
      {
        name: "Sponsors de Saison (Season Pass Annonceur)",
        priority: "MEDIUM",
        impact: "Revenus prévisibles",
        description: "Package annonceur 'Sponsor de Saison' : présence garantie sur 3 mois avec un nombre de diffusions contractualisé, à prix dégressif.",
        details: [
          "3 packages : Bronze (1M FCFA/trim), Silver (3M FCFA/trim), Gold (8M FCFA/trim)",
          "Garantie de diffusion mensuelle contractualisée",
          "Bonus : présence dans le newsletter mensuel oon.click aux abonnés",
          "Logo sponsor visible sur les challenges communautaires du trimestre",
          "Revenus récurrents stables pour la plateforme",
        ],
        stories: ["US-127 : Module Season Pass avec facturation trimestrielle", "US-128 : Gestion des garanties de diffusion et alertes admin"],
      },
    ],
  },
  {
    id: "partnerships",
    icon: "🤝",
    label: "Partenariats & Intégrations",
    agent: "Partnership Manager",
    accent: "#2dd4bf",
    features: [
      {
        name: "Intégration Opérateurs Télécoms",
        priority: "HIGH",
        impact: "Distribution massive",
        description: "Partenariats avec Orange CI, MTN CI, Moov Africa pour proposer oon.click à leurs abonnés et intégrer les recharges téléphoniques comme récompense.",
        details: [
          "Bundle opérateur : 'Abonnez-vous à oon.click avec votre forfait Orange'",
          "Récompense en airtime ou data au lieu de FCFA (pour les abonnés qui le préfèrent)",
          "Zero-rating : accès à oon.click sans consommer le forfait data chez les partenaires",
          "Push oon.click via USSD (*123*OON#) pour les abonnés sans smartphone",
          "Potentiel de distribution de 20M+ d'abonnés mobile en Côte d'Ivoire",
        ],
        stories: ["US-129 : API d'intégration recharge airtime/data via opérateur", "US-130 : Mode USSD basique pour téléphones non-smartphones"],
      },
      {
        name: "Intégration E-commerce Local",
        priority: "MEDIUM",
        impact: "Boucle achat complète",
        description: "Les annonceurs e-commerce (Jumia, boutiques locales) peuvent intégrer un bouton 'Acheter maintenant' directement dans leur pub oon.click.",
        details: [
          "CTA 'Acheter' redirige vers le produit sur le site/app de l'annonceur",
          "Tracking de conversion : oon.click sait si l'abonné a acheté après avoir vu la pub",
          "Rapport de ROI complet : coût/acquisition, taux de conversion, CA généré",
          "Bonus abonné supplémentaire si achat réalisé ('Bonus achat' configurable)",
          "Différencie oon.click des simples plateformes de vues",
        ],
        stories: ["US-131 : CTA e-commerce configurable avec tracking de conversion", "US-132 : Rapport ROI avec attribution des conversions à la campagne"],
      },
      {
        name: "Annuaire des Annonceurs (OON Store)",
        priority: "MEDIUM",
        impact: "Découverte & trafic",
        description: "Section dans l'app dédiée aux annonceurs vérifiés : un mini-annuaire où les abonnés peuvent explorer, suivre et contacter les marques présentes sur oon.click.",
        details: [
          "Page profil de chaque annonceur vérifié : description, secteur, contacts, historique de pubs",
          "Abonné peut 'suivre' une marque et être notifié de ses nouvelles campagnes",
          "Note globale de l'annonceur basée sur les feedbacks des pubs",
          "Recherche par catégorie, ville, nom",
          "Crée de la valeur pour l'annonceur au-delà de la simple diffusion",
        ],
        stories: ["US-133 : Page profil annonceur publique dans l'app", "US-134 : Système de suivi (follow) annonceur par les abonnés"],
      },
      {
        name: "Programme Écoles & Universités",
        priority: "HIGH",
        impact: "Acquisition jeunes",
        description: "Partenariats avec établissements scolaires et universités pour introduire oon.click aux étudiants comme source de revenu complémentaire.",
        details: [
          "Offre spéciale étudiant : bonus inscription 1 000 FCFA au lieu de 500 FCFA (sur justificatif)",
          "Campagnes spéciales 'Marques étudiantes' avec ciblage 18–25 ans",
          "Module de présentation oon.click disponible pour les campus (pitch deck + kit)",
          "Compétition inter-campus : 'Quelle université a le plus d'abonnés actifs ce mois-ci ?'",
          "Les étudiants sont un segment ultra-actif et très partageur",
        ],
        stories: ["US-135 : Workflow vérification statut étudiant et bonus majoré", "US-136 : Compétition inter-campus avec classement public"],
      },
      {
        name: "Intégration WhatsApp Business API",
        priority: "HIGH",
        impact: "Conversion directe",
        description: "Les annonceurs peuvent ajouter un CTA 'Contacter sur WhatsApp' dans leur pub. L'abonné intéressé engage directement la conversation commerciale.",
        details: [
          "Bouton WhatsApp Business sur la pub (numéro pré-rempli)",
          "Message pré-rempli : 'Bonjour, je vous contacte depuis votre pub oon.click'",
          "L'annonceur voit le nombre de clics WhatsApp dans son rapport",
          "Idéal pour les PME n'ayant pas de site web",
          "Très adapté aux habitudes commerciales ivoiriennes (contact direct)",
        ],
        stories: ["US-137 : CTA WhatsApp Business intégré dans le format pub", "US-138 : Tracking des clics WhatsApp dans le rapport de campagne"],
      },
    ],
  },
  {
    id: "learn",
    icon: "🎓",
    label: "Learn & Earn (Apprendre & Gagner)",
    agent: "Content Strategist",
    accent: "#e879f9",
    features: [
      {
        name: "Pubs Éducatives Rémunérées (OON Learn)",
        priority: "HIGH",
        impact: "Différenciation forte",
        description: "Un annonceur peut créer une pub sous forme de mini-cours (3 slides ou vidéo 60s). L'abonné est rémunéré non pas pour avoir vu, mais pour avoir compris — via un quiz de validation.",
        details: [
          "Format : 3 à 5 slides ou vidéo 60s + 2 questions de compréhension",
          "L'abonné est crédité uniquement s'il répond correctement à au moins 1 question",
          "Rémunération majorée : +50% vs pub standard (valeur de l'apprentissage)",
          "L'annonceur reçoit le taux de compréhension moyen de sa campagne",
          "Cas d'usage : banques (éducation financière), santé, assurances, new products",
          "Positionne oon.click comme 'plateforme qui élève le niveau de ses abonnés'",
        ],
        stories: ["US-139 : Format pub éducative avec quiz de validation intégré", "US-140 : Crédit conditionnel basé sur le score du quiz"],
      },
      {
        name: "Micro-Formations Sponsorisées",
        priority: "HIGH",
        impact: "Valeur utilisateur ++",
        description: "Des formations courtes (5 à 10 minutes) financées par des annonceurs, disponibles gratuitement dans l'app. L'abonné apprend et gagne un bonus à la complétion.",
        details: [
          "Durée : 5–10 min, format vidéo ou slides interactives",
          "Thèmes : entrepreneuriat, santé, finance personnelle, agriculture, numérique",
          "Entièrement financées par des annonceurs (marques, institutions, ONG)",
          "Certificat numérique téléchargeable à la fin (valeur ajoutée abonné)",
          "Badge 'Formation complétée' dans le profil abonné",
          "Partenariats avec organismes de formation ivoiriens (FDFP, universités)",
        ],
        stories: ["US-141 : Module micro-formation avec progression par étapes", "US-142 : Génération de certificat numérique à la complétion"],
      },
      {
        name: "Partenariats Médias & Presse Locale",
        priority: "MEDIUM",
        impact: "Contenu de qualité",
        description: "Partenariats avec des médias ivoiriens (Fraternité Matin, AIP, médias en ligne) pour diffuser des articles et reportages via oon.click. L'abonné est rémunéré pour avoir lu l'article.",
        details: [
          "Articles de 3–5 min de lecture depuis les médias partenaires",
          "L'abonné gagne un micro-crédit pour chaque article lu (temps de lecture mesuré)",
          "Le média partenaire partage une commission avec oon.click",
          "Les annonceurs peuvent sponsoriser des rubriques entières (ex. 'Sport sponsorisé par MTN')",
          "Renforce l'image de oon.click comme app utile au quotidien, pas juste pub",
        ],
        stories: ["US-143 : API d'import de contenu médias partenaires", "US-144 : Mesure du temps de lecture réel et attribution de crédit"],
      },
      {
        name: "OON Quiz Show (Jeu en Direct)",
        priority: "MEDIUM",
        impact: "Événement & viralité",
        description: "Chaque semaine, un quiz en direct dans l'app : tous les abonnés connectés jouent en même temps. Les gagnants se partagent une cagnotte sponsorisée par un annonceur.",
        details: [
          "10 questions en 10 minutes, format buzzer (premier arrivé)",
          "Cagnotte hebdomadaire de 10 000 à 50 000 FCFA financée par l'annonceur sponsor",
          "Annonceur : logo partout pendant le quiz, question sponsorisée dédiée à sa marque",
          "Notification 24h avant + 1h avant pour maximiser la participation",
          "Replay disponible pour ceux qui ont raté (sans récompense, juste le fun)",
        ],
        stories: ["US-145 : Moteur de quiz temps réel avec WebSocket", "US-146 : Gestion des cagnottes partagées entre gagnants"],
      },
      {
        name: "Bibliothèque de Ressources Abonnés",
        priority: "LOW",
        impact: "Rétention long terme",
        description: "Section permanente dans l'app rassemblant toutes les ressources accumulées : formations complétées, certificats, articles lus, quiz passés.",
        details: [
          "Portfolio d'apprentissage personnel consultable à tout moment",
          "Partage de certificats sur LinkedIn ou WhatsApp",
          "Statistiques personnelles : 'Vous avez appris X heures sur oon.click'",
          "Les employeurs peuvent vérifier les certifications via un lien public",
          "Transforme le profil oon.click en mini-CV de compétences informelles",
        ],
        stories: ["US-147 : Bibliothèque personnelle de ressources et certifications", "US-148 : Lien de vérification de certification partageable"],
      },
    ],
  },
  {
    id: "selfserve",
    icon: "🛠️",
    label: "Micro-Annonceurs & Self-Serve",
    agent: "SMB Product Manager",
    accent: "#fb923c",
    features: [
      {
        name: "OON Express (Campagne en 3 Minutes)",
        priority: "HIGH",
        impact: "Acquisition PME ×5",
        description: "Interface ultra-simplifiée permettant à un artisan, commerçant ou particulier de lancer sa première campagne en moins de 3 minutes, sans aucune connaissance marketing.",
        details: [
          "3 étapes seulement : 1) Quel est votre produit/service? 2) Quel est votre budget? 3) Payer",
          "oon.click configure automatiquement les critères de ciblage (IA)",
          "Pas de formulaire complexe, pas de choix technique",
          "Budget minimum : 5 000 FCFA (50 abonnés)",
          "Résultat affiché : 'Votre pub sera vue par ~50 personnes dans votre ville'",
          "Idéal pour : coiffeuses, tailleurs, restaurants de quartier, prestataires de services",
        ],
        stories: ["US-149 : Wizard campagne ultra-simplifié (3 étapes)", "US-150 : Configuration automatique des critères par IA selon la description du produit"],
      },
      {
        name: "Compte Prépayé Annonceur (OON Credits)",
        priority: "HIGH",
        impact: "Fidélisation & flexibilité",
        description: "Les annonceurs rechargent un crédit publicitaire et lancent des campagnes à la demande sans refaire une transaction à chaque fois.",
        details: [
          "Recharge en FCFA via Mobile Money : 10k, 25k, 50k, 100k FCFA",
          "Bonus sur les gros rechargements : +5% à 50k FCFA, +10% à 100k FCFA",
          "Campagnes débitées automatiquement du solde disponible",
          "Alerte par SMS/email quand le solde descend sous un seuil configurable",
          "Validité : 12 mois (pas de perte si inactif temporairement)",
        ],
        stories: ["US-151 : Module de compte prépayé annonceur avec recharge", "US-152 : Alertes de solde bas et recharge automatique optionnelle"],
      },
      {
        name: "Duplication & Optimisation de Campagne",
        priority: "MEDIUM",
        impact: "Productivité annonceur",
        description: "Fonctionnalité permettant à un annonceur de dupliquer une ancienne campagne performante, modifier un seul élément, et la relancer en un clic.",
        details: [
          "Bouton 'Dupliquer cette campagne' sur toute campagne terminée",
          "Tous les paramètres pré-remplis : médias, critères, budget",
          "L'annonceur modifie uniquement ce qu'il souhaite changer",
          "'Campagnes similaires' suggérées basées sur les meilleures performances passées",
          "Réduit drastiquement le temps de création pour les annonceurs récurrents",
        ],
        stories: ["US-153 : Duplication de campagne avec pré-remplissage complet", "US-154 : Suggestions de campagnes basées sur l'historique de performance"],
      },
      {
        name: "Abonnement Annonceur Mensuel (OON Pro)",
        priority: "HIGH",
        impact: "Revenus récurrents",
        description: "Formule d'abonnement mensuel pour les annonceurs réguliers : budget inclus, diffusion garantie, et fonctionnalités avancées débloquées.",
        details: [
          "Starter (15 000 FCFA/mois) : 150 abonnés/mois + dashboard basique",
          "Business (50 000 FCFA/mois) : 600 abonnés/mois + A/B test + rapport PDF",
          "Premium (150 000 FCFA/mois) : 2 000 abonnés/mois + lookalike + account manager",
          "Paiement récurrent automatique via Mobile Money",
          "Résiliation à tout moment sans pénalité",
          "Revenu mensuel prévisible et stable pour oon.click",
        ],
        stories: ["US-155 : Module abonnement annonceur avec 3 paliers", "US-156 : Gestion des paiements récurrents et résiliation"],
      },
      {
        name: "Marketplace de Services Créatifs",
        priority: "MEDIUM",
        impact: "Écosystème créatif local",
        description: "Galerie de prestataires créatifs ivoiriens (graphistes, vidéastes, rédacteurs) proposant leurs services aux annonceurs qui n'ont pas de contenu publicitaire prêt.",
        details: [
          "Annonceur commande : 'Je veux une image pub pour ma boutique → 5 000 FCFA'",
          "Créatif livré en 24-48h directement dans l'espace de création de campagne",
          "Paiement via la plateforme (sécurité acheteur + vendeur)",
          "Notation des prestataires par les annonceurs",
          "Génère un écosystème créatif local autour de oon.click",
        ],
        stories: ["US-157 : Marketplace prestataires créatifs avec brief et livraison", "US-158 : Système de paiement et notation des prestataires"],
      },
    ],
  },
  {
    id: "support",
    icon: "💬",
    label: "Support & Expérience Utilisateur",
    agent: "Customer Success Lead",
    accent: "#38bdf8",
    features: [
      {
        name: "Onboarding Interactif Guidé",
        priority: "HIGH",
        impact: "Activation +60%",
        description: "Parcours d'onboarding gamifié qui guide le nouvel utilisateur étape par étape à travers les fonctionnalités clés, avec des mini-récompenses à chaque étape.",
        details: [
          "Abonné : 7 étapes (inscription → profil → 1ère pub → invite un ami → etc.)",
          "Annonceur : 5 étapes (compte → vérification → 1ère campagne → rapport → boost)",
          "Chaque étape complétée = badge + FCFA bonus",
          "Barre de progression 'Complétez votre démarrage' visible jusqu'à 100%",
          "Skip possible à tout moment (mais perte des bonus d'étape)",
        ],
        stories: ["US-159 : Moteur d'onboarding à étapes avec récompenses", "US-160 : Dashboard de progression d'onboarding par utilisateur"],
      },
      {
        name: "Assistant IA In-App (OON Bot)",
        priority: "HIGH",
        impact: "Support -70% coût",
        description: "Chatbot intelligent intégré dans l'app capable de répondre aux questions fréquentes, guider la création de campagne, et escalader vers un humain si besoin.",
        details: [
          "Réponses instantanées 24h/24 aux questions : paiements, retraits, campagnes, profil",
          "Capable de lancer des actions directement : 'Montre-moi mon solde', 'Comment relancer ma campagne'",
          "Escalade vers un agent humain si le bot ne sait pas répondre (ticket créé automatiquement)",
          "Personnalisé : connaît le profil et l'historique de l'utilisateur",
          "Disponible en français, et compréhension du nouchi (argot ivoirien) pour les abonnés",
        ],
        stories: ["US-161 : Chatbot IA with FAQ et actions contextuelles", "US-162 : Escalade vers ticket humain avec historique de conversation"],
      },
      {
        name: "Centre d'Aide & Tutoriels Vidéo",
        priority: "HIGH",
        impact: "Autonomie utilisateur",
        description: "Base de connaissances complète in-app avec tutoriels vidéo courts, FAQ illustrées, et guides pas-à-pas pour toutes les fonctionnalités.",
        details: [
          "Vidéos tutorielles de 1–3 min pour chaque fonctionnalité majeure",
          "FAQ illustrées : 'Pourquoi mon retrait est en attente ?', 'Comment cibler ma campagne ?'",
          "Recherche intelligente dans la base de connaissances",
          "Accessible sans connexion (téléchargement auto des tutoriels en Wi-Fi)",
          "Mis à jour à chaque release de nouvelle fonctionnalité",
        ],
        stories: ["US-163 : Base de connaissances in-app avec recherche", "US-164 : Module tutoriels vidéo téléchargeables hors-ligne"],
      },
      {
        name: "Système de Tickets & Suivi de Litiges",
        priority: "HIGH",
        impact: "Confiance & rétention",
        description: "Système de gestion des réclamations permettant à un abonné ou annonceur de signaler un problème, suivre son traitement, et être notifié de la résolution.",
        details: [
          "Catégories : paiement non reçu, campagne non diffusée, compte bloqué, bug technique",
          "Numéro de ticket unique + statut en temps réel (Ouvert, En cours, Résolu)",
          "Délai de réponse garanti affiché : 'Sous 24h ouvrables'",
          "Historique complet des échanges sur le ticket",
          "Satisfaction note à la fermeture du ticket (feedback continu sur le support)",
        ],
        stories: ["US-165 : Système de tickets avec catégories et statuts", "US-166 : Notification temps réel sur mise à jour de ticket"],
      },
      {
        name: "NPS & Feedback Continu",
        priority: "MEDIUM",
        impact: "Amélioration produit",
        description: "Collecte systématique et discrète des retours utilisateurs pour piloter l'amélioration continue du produit.",
        details: [
          "NPS abonné : affiché après le 10ème retrait ('Recommanderiez-vous oon.click ?')",
          "NPS annonceur : affiché 3 jours après la fin d'une première campagne",
          "Micro-feedbacks contextuels : 'Cette pub était pertinente pour vous ?' (pouce haut/bas)",
          "Boîte à idées : 'Suggérez une fonctionnalité' visible dans les paramètres",
          "Tableau de bord admin : évolution du NPS mensuel + verbatims récents",
        ],
        stories: ["US-167 : Module NPS avec déclenchement contextuel", "US-168 : Dashboard admin NPS et feedback produit"],
      },
      {
        name: "Statut Système & Communication Incidents",
        priority: "MEDIUM",
        impact: "Transparence & confiance",
        description: "Page de statut publique (status.oon.click) et notifications in-app lors de pannes ou maintenances pour éviter les tickets inutiles et rassurer les utilisateurs.",
        details: [
          "Page status.oon.click avec uptime en temps réel de chaque service",
          "Notification in-app automatique en cas de panne : 'Les paiements sont temporairement indisponibles'",
          "Historique des incidents des 90 derniers jours",
          "Estimation de retour à la normale affichée",
          "Réduction drastique des tickets de support lors des incidents",
        ],
        stories: ["US-169 : Page de statut publique avec indicateurs par service", "US-170 : Système de broadcast d'incidents vers les utilisateurs actifs"],
      },
    ],
  },
  {
    id: "diaspora",
    icon: "🌍",
    label: "Diaspora & Expansion Internationale",
    agent: "International Growth Manager",
    accent: "#4ade80",
    features: [
      {
        name: "Mode Diaspora (Abonnés Ivoiriens à l'Étranger)",
        priority: "HIGH",
        impact: "Marché 3M+ personnes",
        description: "Permettre aux Ivoiriens vivant en France, aux USA, au Canada, etc. de s'inscrire et gagner des FCFA qu'ils peuvent envoyer à des proches en Côte d'Ivoire.",
        details: [
          "Inscription disponible depuis n'importe quel pays",
          "Pubs ciblées en FCFA mais visibles depuis l'étranger",
          "Retrait : l'abonné diaspora choisit le bénéficiaire en CI (numéro Mobile Money)",
          "Cas d'usage : 'Je regarde des pubs le soir depuis Paris et ma mère reçoit l'argent à Abidjan'",
          "Critère de ciblage 'Diaspora' disponible pour les annonceurs (ex. banques proposant des transferts)",
          "Marché de 3M+ ivoiriens à l'étranger totalement inexploité",
        ],
        stories: ["US-171 : Profil diaspora avec configuration du bénéficiaire CI", "US-172 : Retrait vers Mobile Money d'un tiers en Côte d'Ivoire"],
      },
      {
        name: "Ciblage Culturel & Ethnographique",
        priority: "MEDIUM",
        impact: "Pertinence culturelle",
        description: "Enrichissement des critères de ciblage avec des données culturelles : langue parlée, région d'origine, pratique religieuse — pour des campagnes ultra-contextualisées.",
        details: [
          "Champs optionnels dans le profil : langue(s) parlée(s), région d'origine, religion (optionnel)",
          "Ex : annonceur peut cibler 'Dioulas du Nord' ou 'Baoulés de la Vallée du Bandama'",
          "Ciblage religieux : campagnes spéciales Ramadan pour profils musulmans",
          "Toutes les données sont opt-in et explicitement consenties",
          "Conformité totale avec les règles anti-discrimination (charte publicitaire stricte sur ce point)",
        ],
        stories: ["US-173 : Champs culturels optionnels dans le formulaire profil", "US-174 : Critères culturels disponibles dans le ciblage campagne (avec restrictions légales)"],
      },
      {
        name: "Version USSD (Sans Smartphone)",
        priority: "HIGH",
        impact: "Inclusion numérique totale",
        description: "Accès à oon.click via USSD pour les abonnés n'ayant pas de smartphone. Ils reçoivent des pubs par SMS audio ou texte et répondent via leur clavier.",
        details: [
          "Composer *123*OON# → menu interactif USSD",
          "Pub texte : message publicitaire de 160 caractères envoyé par SMS",
          "Réponse : l'abonné répond 1 pour confirmer la lecture → crédit automatique",
          "Solde consultable via USSD : *123*OON*2#",
          "Demande de retrait via USSD (Mobile Money uniquement)",
          "Cible les zones rurales et les populations sans smartphone (40%+ en CI)",
        ],
        stories: ["US-175 : Interface USSD pour les fonctions essentielles abonné", "US-176 : Format pub SMS 160 caractères avec tracking de lecture"],
      },
      {
        name: "Multi-Devise & Expansion UEMOA",
        priority: "MEDIUM",
        impact: "Marché 130M+ hab.",
        description: "Architecture permettant l'expansion vers les 7 autres pays de l'UEMOA (FCFA commun) sans changer de devise : Sénégal, Mali, Burkina Faso, Niger, Togo, Bénin, Guinée-Bissau.",
        details: [
          "Le FCFA est commun aux 8 pays de l'UEMOA — 0 friction de change",
          "Annonceurs ivoiriens peuvent cibler des abonnés sénégalais ou maliens",
          "Localisation par pays : langue, réglementation, opérateurs de paiement",
          "Déploiement progressif : CI → Sénégal → Burkina en priorité",
          "Potentiel : 130M+ habitants, même monnaie, même espace économique",
        ],
        stories: ["US-177 : Architecture multi-pays avec configuration par pays", "US-178 : Ciblage transnational UEMOA dans le créateur de campagne"],
      },
      {
        name: "Annonceurs Internationaux (Marques Globales)",
        priority: "MEDIUM",
        impact: "Revenus premium",
        description: "Interface en anglais + support international pour permettre aux grandes marques mondiales (Samsung, Nestlé, Unilever) de cibler spécifiquement le marché ivoirien via oon.click.",
        details: [
          "Interface annonceur disponible en anglais et français",
          "Paiement en USD/EUR converti automatiquement en FCFA",
          "Account manager dédié pour les campagnes > 1M FCFA",
          "Media kit oon.click en anglais pour les agences internationales",
          "Rapports en anglais et français",
          "Porte d'entrée pour les grandes marques souhaitant un ROI mesurable en Afrique de l'Ouest",
        ],
        stories: ["US-179 : Interface annonceur bilingue FR/EN", "US-180 : Gestion des paiements en devises étrangères avec conversion FCFA"],
      },
    ],
  },
  {
    id: "creators",
    icon: "⭐",
    label: "Programme Créateurs & Influenceurs",
    agent: "Creator Economy Lead",
    accent: "#f472b6",
    features: [
      {
        name: "Programme Créateurs Officiels (OON Creator)",
        priority: "HIGH",
        impact: "Acquisition virale",
        description: "Les influenceurs ivoiriens (Instagram, TikTok, YouTube) peuvent devenir des Créateurs Officiels oon.click et gagner de l'argent en intégrant oon.click dans leur contenu.",
        details: [
          "Éligibilité : 1 000+ followers sur au moins une plateforme sociale",
          "Le créateur partage son code de parrainage personnalisé dans ses vidéos/posts",
          "Rémunération : 500 FCFA par filleul actif (ayant vu ≥ 3 pubs)",
          "Badge 'Créateur Officiel OON' dans leur bio oon.click",
          "Dashboard créateur : impressions, filleuls actifs, gains mensuels",
          "Top 10 créateurs du mois mis en avant dans l'app (exposition gratuite pour eux)",
        ],
        stories: ["US-181 : Espace Créateur avec dashboard de performance", "US-182 : Système de suivi de filleuls actifs et rémunération créateur"],
      },
      {
        name: "Pub Native Créateur (Contenu Sponsorisé)",
        priority: "HIGH",
        impact: "Authenticité & engagement",
        description: "Les annonceurs peuvent sponsoriser directement un créateur oon.click pour qu'il crée une pub authentique de leur produit, diffusée ensuite à ses followers.",
        details: [
          "L'annonceur parcourt le catalogue des créateurs (niche, audience, tarif)",
          "Il envoie une demande de sponsoring avec brief et budget",
          "Le créateur produit son contenu (video, image, texte) directement dans l'app",
          "Contenu validé par l'annonceur puis diffusé comme campagne standard",
          "Taux d'engagement supérieur grâce à l'authenticité créateur",
          "Tarification créateur : fixée par lui-même dans son profil",
        ],
        stories: ["US-183 : Marketplace de sponsoring créateur avec brief et validation", "US-184 : Diffusion du contenu créateur comme campagne standard"],
      },
      {
        name: "OON Live (Pub en Streaming Live)",
        priority: "MEDIUM",
        impact: "Formats nouvelle génération",
        description: "Les créateurs peuvent faire des live streams dans l'app. Les annonceurs sponsorisent le live et leurs pubs apparaissent pendant la diffusion.",
        details: [
          "Streaming live intégré dans l'app (via WebRTC ou SDK tiers)",
          "L'annonceur achète des 'slots pub' de 15s pendant le live (bannière ou overlay)",
          "Les spectateurs du live gagnent de petits crédits pour regarder (micro-rémunération live)",
          "Le créateur reçoit une commission sur les pubs diffusées pendant son live",
          "Cas d'usage : live shopping, événements, Q&A sponsorisés",
        ],
        stories: ["US-185 : Module live streaming avec intégration pub overlay", "US-186 : Gestion des slots pub live et rémunération créateur"],
      },
      {
        name: "Réseau de Microinfluenceurs Locaux",
        priority: "HIGH",
        impact: "Capillarité terrain",
        description: "Programme structuré pour les microinfluenceurs de quartier (500–10 000 followers) qui connaissent leurs communautés locales et font la promotion de oon.click.",
        details: [
          "Kit de démarrage numérique : flyers digitaux, stories templates, scripts de présentation",
          "Formation en ligne de 30 min sur comment présenter oon.click",
          "Commission récurrente sur l'activité mensuelle de leurs filleuls actifs",
          "Classement mensuel des meilleurs microinfluenceurs par ville",
          "Couvre des quartiers et communes que la pub digitale classique n'atteint pas",
          "Stratégie de bouche-à-oreille hyper-locale, très efficace en Afrique de l'Ouest",
        ],
        stories: ["US-187 : Programme microinfluenceur avec kit digital et formation", "US-188 : Commission récurrente sur activité mensuelle des filleuls"],
      },
      {
        name: "OON Studio (Outil de Création In-App)",
        priority: "MEDIUM",
        impact: "Autonomie créateurs",
        description: "Éditeur créatif intégré permettant aux créateurs et annonceurs de produire des contenus publicitaires de qualité professionnelle sans outil externe.",
        details: [
          "Éditeur d'image avec filtres, textes, stickers, logos",
          "Montage vidéo simple : couper, assembler des clips, ajouter musique libre de droits",
          "Bibliothèque de musiques libre de droits africaines (afrobeat, coupé-décalé…)",
          "Templates de formats pré-dimensionnés pour chaque type de pub oon.click",
          "Export direct vers la création de campagne sans quitter l'app",
        ],
        stories: ["US-189 : Éditeur d'image in-app avec bibliothèque d'assets", "US-190 : Monteur vidéo simplifié avec bibliothèque musicale libre de droits"],
      },
    ],
  },
];

const PRIORITY_COLORS = { HIGH: "#10b981", MEDIUM: "#f59e0b", LOW: "#6366f1" };

function FeatureCard({ feature, accent }) {
  const [open, setOpen] = useState(false);
  return (
    <div
      onClick={() => setOpen(!open)}
      style={{
        background: open ? "#0c1220" : "#070c18",
        border: `1px solid ${open ? accent + "55" : "#1a2234"}`,
        borderRadius: "10px",
        padding: "16px 18px",
        marginBottom: "10px",
        cursor: "pointer",
        transition: "all 0.2s",
        boxShadow: open ? `0 0 0 1px ${accent}20` : "none",
      }}
    >
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", gap: "12px" }}>
        <div style={{ flex: 1 }}>
          <div style={{ display: "flex", alignItems: "center", gap: "8px", marginBottom: "7px", flexWrap: "wrap" }}>
            <span style={{
              background: PRIORITY_COLORS[feature.priority] + "20",
              color: PRIORITY_COLORS[feature.priority],
              border: `1px solid ${PRIORITY_COLORS[feature.priority]}40`,
              borderRadius: "5px",
              padding: "2px 8px",
              fontSize: "10px",
              fontWeight: "700",
              letterSpacing: "0.08em",
              textTransform: "uppercase",
              fontFamily: "monospace",
            }}>{feature.priority}</span>
            <span style={{
              background: accent + "15",
              color: accent,
              border: `1px solid ${accent}30`,
              borderRadius: "5px",
              padding: "2px 8px",
              fontSize: "10px",
              fontWeight: "600",
              fontFamily: "monospace",
            }}>📈 {feature.impact}</span>
          </div>
          <div style={{ fontSize: "14px", fontWeight: "700", color: "#f1f5f9", marginBottom: "5px", letterSpacing: "-0.01em" }}>
            {feature.name}
          </div>
          <div style={{ fontSize: "12px", color: "#64748b", lineHeight: "1.55" }}>
            {feature.description}
          </div>
        </div>
        <div style={{
          color: open ? accent : "#334155",
          fontSize: "14px",
          flexShrink: 0,
          marginTop: "2px",
          transition: "color 0.2s",
        }}>
          {open ? "▲" : "▼"}
        </div>
      </div>

      {open && (
        <div style={{ marginTop: "16px", borderTop: `1px solid ${accent}20`, paddingTop: "16px" }}>
          <div style={{ marginBottom: "14px" }}>
            <div style={{ fontSize: "10px", color: "#475569", textTransform: "uppercase", letterSpacing: "0.1em", marginBottom: "8px", fontFamily: "monospace" }}>
              ↳ DÉTAILS FONCTIONNELS
            </div>
            {feature.details.map((d, i) => (
              <div key={i} style={{ display: "flex", gap: "8px", marginBottom: "5px", alignItems: "flex-start" }}>
                <span style={{ color: accent, fontSize: "11px", marginTop: "2px", flexShrink: 0 }}>▸</span>
                <span style={{ fontSize: "12px", color: "#94a3b8", lineHeight: "1.55" }}>{d}</span>
              </div>
            ))}
          </div>
          <div>
            <div style={{ fontSize: "10px", color: "#475569", textTransform: "uppercase", letterSpacing: "0.1em", marginBottom: "8px", fontFamily: "monospace" }}>
              ↳ USER STORIES ASSOCIÉES
            </div>
            {feature.stories.map((s, i) => (
              <div key={i} style={{
                background: "#050a14",
                border: `1px solid ${accent}20`,
                borderLeft: `3px solid ${accent}`,
                borderRadius: "0 6px 6px 0",
                padding: "7px 12px",
                marginBottom: "5px",
                fontSize: "11px",
                color: "#7dd3fc",
                fontFamily: "monospace",
              }}>{s}</div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

export default function BMADDeepDiveV4() {
  const [activeDomain, setActiveDomain] = useState("gamification");
  const domain = DOMAINS.find(d => d.id === activeDomain);

  const totalFeatures = DOMAINS.reduce((acc, d) => acc + d.features.length, 0);
  const highCount = DOMAINS.reduce((acc, d) => acc + d.features.filter(f => f.priority === "HIGH").length, 0);
  const totalStories = DOMAINS.reduce((acc, d) => acc + d.features.reduce((a, f) => a + f.stories.length, 0), 0);

  return (
    <div style={{
      minHeight: "100vh",
      background: "#030812",
      fontFamily: "'IBM Plex Mono', 'Courier New', monospace",
      color: "#e2e8f0",
    }}>
      {/* Header */}
      <div style={{
        background: "linear-gradient(160deg, #050f20 0%, #080f08 60%, #050f20 100%)",
        borderBottom: "1px solid #0f2040",
        padding: "22px 24px 0",
      }}>
        <div style={{ display: "flex", alignItems: "flex-start", justifyContent: "space-between", marginBottom: "18px", flexWrap: "wrap", gap: "14px" }}>
          <div>
            <div style={{ fontSize: "10px", color: "#334155", letterSpacing: "0.15em", textTransform: "uppercase", marginBottom: "5px" }}>
              BMAD Deep Dive v4 · Analyse Finale · 14 Domaines
            </div>
            <h1 style={{
              margin: 0,
              fontSize: "20px",
              fontWeight: "800",
              background: "linear-gradient(135deg, #34d399, #10b981, #a3e635)",
              WebkitBackgroundClip: "text",
              WebkitTextFillColor: "transparent",
              letterSpacing: "-0.02em",
            }}>oon.click · Innovation Layer</h1>
            <div style={{ fontSize: "11px", color: "#334155", marginTop: "3px" }}>
              Wallet supprimé · 14 domaines · {totalFeatures} fonctionnalités · {totalStories} user stories
            </div>
          </div>
          <div style={{ display: "flex", gap: "18px", flexWrap: "wrap" }}>
            {[
              { label: "Domaines", val: DOMAINS.length, color: "#34d399" },
              { label: "Features", val: totalFeatures, color: "#60a5fa" },
              { label: "HIGH", val: highCount, color: "#f97316" },
              { label: "Stories", val: totalStories, color: "#a78bfa" },
            ].map((s, i) => (
              <div key={i} style={{ textAlign: "center" }}>
                <div style={{ fontSize: "20px", fontWeight: "800", color: s.color }}>{s.val}</div>
                <div style={{ fontSize: "9px", color: "#334155", textTransform: "uppercase", letterSpacing: "0.1em" }}>{s.label}</div>
              </div>
            ))}
          </div>
        </div>

        {/* Domain Tabs — scrollable */}
        <div style={{ display: "flex", gap: "2px", overflowX: "auto", paddingBottom: "0", scrollbarWidth: "none" }}>
          {DOMAINS.map(d => (
            <button
              key={d.id}
              onClick={() => setActiveDomain(d.id)}
              style={{
                background: activeDomain === d.id ? d.accent + "18" : "transparent",
                border: "none",
                borderBottom: activeDomain === d.id ? `2px solid ${d.accent}` : "2px solid transparent",
                color: activeDomain === d.id ? d.accent : "#374151",
                padding: "9px 13px",
                fontSize: "11px",
                fontWeight: "700",
                cursor: "pointer",
                transition: "all 0.15s",
                fontFamily: "inherit",
                whiteSpace: "nowrap",
                letterSpacing: "0.01em",
                borderRadius: "4px 4px 0 0",
              }}
            >
              {d.icon} {d.label}
            </button>
          ))}
        </div>
      </div>

      {/* Content */}
      <div style={{ padding: "22px 24px", maxWidth: "980px" }}>
        {/* Domain header */}
        <div style={{
          background: `linear-gradient(135deg, ${domain.accent}08, transparent)`,
          border: `1px solid ${domain.accent}20`,
          borderRadius: "10px",
          padding: "14px 18px",
          marginBottom: "18px",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          flexWrap: "wrap",
          gap: "10px",
        }}>
          <div>
            <div style={{ fontSize: "10px", color: "#334155", textTransform: "uppercase", letterSpacing: "0.12em", marginBottom: "3px" }}>
              Agent responsable : {domain.agent}
            </div>
            <div style={{ fontSize: "16px", fontWeight: "800", color: domain.accent }}>
              {domain.icon} {domain.label}
            </div>
          </div>
          <div style={{ display: "flex", gap: "7px", flexWrap: "wrap" }}>
            {["HIGH", "MEDIUM", "LOW"].map(p => {
              const count = domain.features.filter(f => f.priority === p).length;
              return count > 0 ? (
                <div key={p} style={{
                  background: PRIORITY_COLORS[p] + "12",
                  border: `1px solid ${PRIORITY_COLORS[p]}28`,
                  color: PRIORITY_COLORS[p],
                  borderRadius: "6px",
                  padding: "3px 10px",
                  fontSize: "10px",
                  fontWeight: "700",
                  fontFamily: "monospace",
                }}>{count}× {p}</div>
              ) : null;
            })}
            <div style={{
              background: "#1e293b",
              color: "#64748b",
              borderRadius: "6px",
              padding: "3px 10px",
              fontSize: "10px",
              fontFamily: "monospace",
            }}>{domain.features.reduce((a, f) => a + f.stories.length, 0)} stories</div>
          </div>
        </div>

        {domain.features.map((feature, i) => (
          <FeatureCard key={i} feature={feature} accent={domain.accent} />
        ))}
      </div>

      <div style={{
        borderTop: "1px solid #070f1a",
        padding: "12px 24px",
        display: "flex",
        justifyContent: "space-between",
        fontSize: "10px",
        color: "#0f1e30",
        flexWrap: "wrap",
        gap: "6px",
      }}>
        <span>BMAD Deep Dive v4 · oon.click Innovation Layer</span>
        <span>{totalFeatures} fonctionnalités · {totalStories} user stories · {DOMAINS.length} domaines analysés</span>
      </div>
    </div>
  );
}
