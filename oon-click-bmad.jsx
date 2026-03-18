import { useState } from "react";

const agents = [
  { id: "analyst", label: "🔍 Analyst", title: "Brief Produit & Analyse des Besoins" },
  { id: "pm", label: "📋 Product Manager", title: "PRD — Product Requirements Document" },
  { id: "architect", label: "🏗️ Architect", title: "Architecture Technique" },
  { id: "dev", label: "⚙️ Developer", title: "Backlog Épics & User Stories" },
  { id: "scrum", label: "🚀 Scrum Master", title: "Roadmap & Plan de Livraison" },
];

const content = {
  analyst: {
    sections: [
      {
        title: "Vision Produit",
        icon: "🎯",
        color: "#f59e0b",
        items: [
          { label: "Nom", value: "oon.click" },
          { label: "Tagline", value: "Regarde une pub. Gagne de l'argent." },
          { label: "Catégorie", value: "AdTech / FinTech / Micro-earning" },
          { label: "Marché cible", value: "Côte d'Ivoire & Afrique francophone subsaharienne" },
          { label: "Supports", value: "Application mobile (iOS + Android) + Web App" },
          { label: "Devise", value: "FCFA (XOF)" },
        ],
      },
      {
        title: "Problèmes résolus",
        icon: "💡",
        color: "#3b82f6",
        items: [
          { label: "Pour l'annonceur", value: "Difficulté à cibler précisément des audiences locales qualifiées sur mobile en Afrique de l'Ouest" },
          { label: "Pour l'abonné", value: "Absence de rémunération pour le temps et l'attention accordés aux publicités numériques" },
          { label: "Pour le marché", value: "Faible taux d'engagement publicitaire dû au manque d'incitation financière" },
        ],
      },
      {
        title: "Acteurs & Parties prenantes",
        icon: "👥",
        color: "#8b5cf6",
        items: [
          { label: "Abonné (Viewer)", value: "Particulier inscrit gratuitement, profil renseigné, consomme les publicités et est rémunéré" },
          { label: "Annonceur (Advertiser)", value: "Entreprise ou individu qui crée et finance des campagnes publicitaires ciblées" },
          { label: "Administrateur", value: "Équipe oon.click gérant la plateforme, les tarifs, les paiements et les modèles de profil" },
          { label: "Agrégateur de paiement", value: "Prestataire tiers (ex. CinetPay, FedaPay) gérant Mobile Money, VISA, PayPal" },
        ],
      },
      {
        title: "Modèle économique",
        icon: "💰",
        color: "#10b981",
        items: [
          { label: "Revenus", value: "Commission sur chaque campagne annonceur (ex. marge entre prix facturé et rémunération abonné)" },
          { label: "Prix annonceur", value: "100 FCFA / abonné ciblé (configurable en admin)" },
          { label: "Bonus inscription abonné", value: "500 FCFA à la complétion du profil" },
          { label: "Seuil de retrait abonné", value: "5 000 FCFA minimum" },
          { label: "Rémunération par vue", value: "À définir par admin (fraction du 100 FCFA encaissé)" },
          { label: "Canaux de paiement", value: "Mobile Money (Orange, MTN, Moov), VISA, PayPal" },
        ],
      },
      {
        title: "Contraintes & Risques identifiés",
        icon: "⚠️",
        color: "#ef4444",
        items: [
          { label: "Risque fraude", value: "Faux clics / vues automatisées — nécessite anti-fraud (détection bot, vérification humaine)" },
          { label: "Risque financier", value: "Solvabilité : les paiements annonceurs doivent précéder les crédits abonnés (escrow)" },
          { label: "Réglementation", value: "Conformité RGPD / loi ivoirienne sur les données personnelles (ARTCI)" },
          { label: "Scalabilité", value: "Campagnes pouvant cibler des dizaines de milliers d'abonnés simultanément" },
          { label: "Contenu", value: "Modération des annonces (images, vidéos, audios) avant publication" },
        ],
      },
    ],
  },
  pm: {
    sections: [
      {
        title: "Épics Fonctionnels",
        icon: "📌",
        color: "#6366f1",
        items: [
          { label: "EP-01", value: "Authentification & Gestion des comptes (inscription, connexion, profil)" },
          { label: "EP-02", value: "Onboarding & Profil Abonné (formulaire dynamique, bonus 500 FCFA)" },
          { label: "EP-03", value: "Création & Gestion de Campagnes Annonceur" },
          { label: "EP-04", value: "Ciblage & Matching Abonnés ↔ Campagnes" },
          { label: "EP-05", value: "Diffusion & Consommation des Publicités (viewer)" },
          { label: "EP-06", value: "Gestion des Portefeuilles & Transactions (abonné + annonceur)" },
          { label: "EP-07", value: "Paiements In-App (Mobile Money, VISA, PayPal)" },
          { label: "EP-08", value: "Rapports & Analytics (annonceur + admin)" },
          { label: "EP-09", value: "Administration Plateforme (admin panel)" },
          { label: "EP-10", value: "Notifications & Communication (push, email, SMS)" },
        ],
      },
      {
        title: "Exigences Fonctionnelles Clés",
        icon: "✅",
        color: "#10b981",
        items: [
          { label: "RF-01", value: "Formulaire de profil abonné 100% configurable depuis l'admin (champs dynamiques)" },
          { label: "RF-02", value: "Types de médias supportés : Image (JPG/PNG), Vidéo MP4 (≤30s), Audio MP3 (≤30s), Texte riche" },
          { label: "RF-03", value: "Ciblage multi-critères : âge, sexe, lieu, formation, métier, fonction, tranche de salaire, groupe sanguin" },
          { label: "RF-04", value: "Le crédit abonné est déclenché UNIQUEMENT après ouverture réelle de la pub (tracking engagement)" },
          { label: "RF-05", value: "Escrow : le budget campagne est bloqué dès la validation de paiement annonceur" },
          { label: "RF-06", value: "Rapport de campagne automatique envoyé à l'annonceur à la clôture" },
          { label: "RF-07", value: "Retrait abonné conditionné à un solde ≥ 5 000 FCFA via Mobile Money ou autre" },
          { label: "RF-08", value: "Interface admin pour configurer : tarif/abonné, bonus inscription, seuil retrait, champs profil" },
          { label: "RF-09", value: "Modération des contenus publicitaires avant mise en ligne" },
          { label: "RF-10", value: "Anti-fraude : détection de vues répétées anormales, limitation fréquence par abonné/campagne" },
        ],
      },
      {
        title: "Exigences Non-Fonctionnelles",
        icon: "⚙️",
        color: "#f59e0b",
        items: [
          { label: "Performance", value: "Chargement des médias < 3s sur 3G (optimisation CDN + compression)" },
          { label: "Disponibilité", value: "SLA 99.5% — architecture haute disponibilité" },
          { label: "Sécurité", value: "Chiffrement des données personnelles, conformité ARTCI, JWT + refresh tokens" },
          { label: "Scalabilité", value: "Architecture micro-services ou modulaire permettant 100k+ abonnés actifs" },
          { label: "Accessibilité", value: "Support Android 8+ / iOS 14+ / Navigateurs modernes" },
          { label: "Internationalisation", value: "Français (principal), extensible à l'anglais" },
        ],
      },
      {
        title: "Critères d'Acceptation Globaux",
        icon: "🎯",
        color: "#ec4899",
        items: [
          { label: "CA-01", value: "Un annonceur peut créer, configurer, payer et lancer une campagne en moins de 10 minutes" },
          { label: "CA-02", value: "Un abonné reçoit son crédit dans les 60 secondes après ouverture d'une pub" },
          { label: "CA-03", value: "Le rapport de campagne contient : nb vues, taux d'ouverture, coût total, profil des viewers" },
          { label: "CA-04", value: "Les retraits Mobile Money sont traités en moins de 24h ouvrables" },
          { label: "CA-05", value: "L'admin peut modifier le tarif/abonné sans redéploiement applicatif" },
        ],
      },
    ],
  },
  architect: {
    sections: [
      {
        title: "Stack Technique Recommandée",
        icon: "🔧",
        color: "#3b82f6",
        items: [
          { label: "Mobile", value: "Flutter (iOS + Android — codebase unique, performant en Afrique basse connectivité)" },
          { label: "Web Frontend", value: "React + TypeScript + TailwindCSS (Next.js pour SEO annonceurs)" },
          { label: "Backend API", value: "Node.js + Express ou NestJS (REST + WebSocket pour notifications temps réel)" },
          { label: "Base de données", value: "PostgreSQL (données transactionnelles) + Redis (sessions, cache, anti-fraude)" },
          { label: "Stockage médias", value: "AWS S3 ou Cloudflare R2 + CDN (CloudFront / Cloudflare) pour vidéos/images/audios" },
          { label: "Paiements", value: "CinetPay ou FedaPay (agrégateur FCFA : Orange Money, MTN, Moov, VISA) + PayPal SDK" },
          { label: "Notifications", value: "Firebase Cloud Messaging (push mobile) + SendGrid (email) + Twilio (SMS)" },
          { label: "Auth", value: "JWT + Refresh Token + OAuth2 (connexion Google/Facebook optionnelle)" },
          { label: "Infrastructure", value: "Docker + Railway ou Render (démarrage) → AWS / GCP (scale)" },
          { label: "CI/CD", value: "GitHub Actions → déploiement automatique" },
        ],
      },
      {
        title: "Architecture des Modules",
        icon: "🏛️",
        color: "#8b5cf6",
        items: [
          { label: "auth-service", value: "Inscription, connexion, gestion tokens, rôles (ABONNÉ, ANNONCEUR, ADMIN)" },
          { label: "profile-service", value: "Formulaire dynamique abonné, gestion champs configurables, bonus inscription" },
          { label: "campaign-service", value: "CRUD campagne, upload médias, définition critères ciblage, workflow modération" },
          { label: "targeting-engine", value: "Algorithme de matching abonnés ↔ critères campagne (SQL query builder dynamique)" },
          { label: "delivery-service", value: "File de distribution des pubs, tracking ouverture, anti-fraude, crédit abonné" },
          { label: "wallet-service", value: "Portefeuille abonné (crédit/débit), portefeuille annonceur (escrow), historique transactions" },
          { label: "payment-service", value: "Intégration agrégateur, initiation paiement, webhook confirmation, déclenchement escrow" },
          { label: "reporting-service", value: "Génération rapports campagne, exports PDF/CSV, dashboards analytics" },
          { label: "admin-service", value: "Configuration plateforme, modération contenu, gestion utilisateurs, paramétrage tarifs" },
          { label: "notification-service", value: "Orchestration push/email/SMS selon événements métier" },
        ],
      },
      {
        title: "Schéma Base de Données (entités clés)",
        icon: "🗄️",
        color: "#10b981",
        items: [
          { label: "users", value: "id, email, phone, role, status, created_at" },
          { label: "subscriber_profiles", value: "user_id, age, sexe, ville, formation, metier, fonction, salaire, groupe_sanguin, [champs_dynamiques JSONB], profile_completed_at" },
          { label: "wallets", value: "id, user_id, balance, pending_balance, currency, updated_at" },
          { label: "campaigns", value: "id, advertiser_id, title, media_type, media_url, budget_total, cost_per_view, target_count, status, starts_at, ends_at" },
          { label: "campaign_criteria", value: "campaign_id, criteria_key, criteria_value (table de critères de ciblage)" },
          { label: "campaign_targets", value: "campaign_id, subscriber_id, status (PENDING/VIEWED/SKIPPED), viewed_at" },
          { label: "transactions", value: "id, wallet_id, type (CREDIT/DEBIT/ESCROW/WITHDRAWAL), amount, ref_id, status, created_at" },
          { label: "withdrawals", value: "id, wallet_id, amount, method, account_ref, status, processed_at" },
          { label: "platform_config", value: "config_key, config_value (tarif_par_abonne, bonus_inscription, seuil_retrait, ...)" },
        ],
      },
      {
        title: "Flux Critiques",
        icon: "🔄",
        color: "#f59e0b",
        items: [
          { label: "Flux Campagne", value: "Annonceur remplit formulaire → Calcul coût (nb_cibles × 100 FCFA) → Paiement → Escrow → Modération → Matching → Distribution → Tracking → Rapport" },
          { label: "Flux Vue Pub", value: "Abonné reçoit notif → Ouvre pub → Timer engagement (vidéo 30s / audio 30s / image 5s) → Confirmation → Crédit wallet → Anti-fraude check" },
          { label: "Flux Retrait", value: "Abonné demande retrait (≥5000 FCFA) → Vérification solde → Initiation paiement agrégateur → Webhook confirmation → Débit wallet → Notification" },
          { label: "Flux Bonus Profil", value: "Abonné complète tous les champs requis → Validation → Crédit 500 FCFA → Notification → Une seule fois par compte" },
        ],
      },
      {
        title: "Sécurité & Anti-Fraude",
        icon: "🛡️",
        color: "#ef4444",
        items: [
          { label: "Rate limiting", value: "Max N vues/jour par abonné par campagne (configurable admin)" },
          { label: "Device fingerprinting", value: "Détection multi-comptes sur même appareil" },
          { label: "Engagement validation", value: "Tracking temps réel : vidéo/audio doit être vue/écoutée entièrement pour créditer" },
          { label: "IP monitoring", value: "Alertes sur volumes anormaux depuis une même IP" },
          { label: "KYC léger", value: "Vérification numéro de téléphone (OTP) obligatoire pour retrait" },
        ],
      },
    ],
  },
  dev: {
    sections: [
      {
        title: "Sprint 1 — Fondations (Semaines 1-3)",
        icon: "🏁",
        color: "#3b82f6",
        items: [
          { label: "US-001", value: "[Auth] En tant qu'utilisateur, je peux m'inscrire avec email/téléphone et créer mon compte" },
          { label: "US-002", value: "[Auth] En tant qu'utilisateur, je peux me connecter et recevoir un JWT sécurisé" },
          { label: "US-003", value: "[Auth] En tant qu'admin, je peux gérer les rôles (abonné / annonceur / admin)" },
          { label: "US-004", value: "[Profil] En tant qu'abonné, je remplis mon profil via un formulaire dynamique" },
          { label: "US-005", value: "[Profil] En tant qu'abonné, je reçois 500 FCFA dès que mon profil est complété" },
          { label: "US-006", value: "[Admin] En tant qu'admin, je configure les champs du formulaire profil (ajouter/supprimer/rendre obligatoire)" },
          { label: "US-007", value: "[Wallet] En tant qu'abonné, je consulte mon solde et l'historique de mes transactions" },
        ],
      },
      {
        title: "Sprint 2 — Campagnes Annonceur (Semaines 4-6)",
        icon: "📢",
        color: "#8b5cf6",
        items: [
          { label: "US-008", value: "[Campagne] En tant qu'annonceur, je crée une campagne en choisissant type de média et critères de ciblage" },
          { label: "US-009", value: "[Campagne] En tant qu'annonceur, je vois le coût estimé de ma campagne en temps réel (nb cibles × tarif)" },
          { label: "US-010", value: "[Campagne] En tant qu'annonceur, je paye ma campagne via Mobile Money, VISA ou PayPal" },
          { label: "US-011", value: "[Campagne] En tant qu'annonceur, je peux uploader image, vidéo (≤30s), audio (≤30s) ou texte" },
          { label: "US-012", value: "[Admin] En tant qu'admin, je modère et approuve/rejette une campagne avant sa diffusion" },
          { label: "US-013", value: "[Campagne] En tant qu'annonceur, je visualise le statut de ma campagne (en attente / active / terminée)" },
        ],
      },
      {
        title: "Sprint 3 — Diffusion & Rémunération (Semaines 7-9)",
        icon: "📺",
        color: "#10b981",
        items: [
          { label: "US-014", value: "[Viewer] En tant qu'abonné, je reçois une notification push quand une pub me correspond" },
          { label: "US-015", value: "[Viewer] En tant qu'abonné, j'ouvre la pub depuis l'app et je la visionne (image/vidéo/audio/texte)" },
          { label: "US-016", value: "[Viewer] En tant qu'abonné, mon compte est crédité automatiquement après consommation complète de la pub" },
          { label: "US-017", value: "[Anti-fraude] Le système détecte et bloque les tentatives de vues répétées anormales" },
          { label: "US-018", value: "[Targeting] Le moteur de matching sélectionne les abonnés correspondant aux critères de la campagne" },
        ],
      },
      {
        title: "Sprint 4 — Paiements & Retraits (Semaines 10-11)",
        icon: "💳",
        color: "#f59e0b",
        items: [
          { label: "US-019", value: "[Retrait] En tant qu'abonné, je peux demander un retrait si mon solde ≥ 5 000 FCFA" },
          { label: "US-020", value: "[Retrait] En tant qu'abonné, je choisis mon mode de retrait (Mobile Money, VISA)" },
          { label: "US-021", value: "[Payment] Le système reçoit les webhooks de confirmation de paiement de l'agrégateur" },
          { label: "US-022", value: "[Escrow] Le budget campagne est bloqué à réception du paiement annonceur et libéré progressivement" },
          { label: "US-023", value: "[Admin] En tant qu'admin, je visualise et traite les demandes de retrait en attente" },
        ],
      },
      {
        title: "Sprint 5 — Rapports & Admin (Semaines 12-13)",
        icon: "📊",
        color: "#ec4899",
        items: [
          { label: "US-024", value: "[Rapport] En tant qu'annonceur, je reçois un rapport détaillé à la fin de ma campagne (vues, taux, profils, coût)" },
          { label: "US-025", value: "[Dashboard] En tant qu'annonceur, j'accède à un dashboard temps réel de ma campagne active" },
          { label: "US-026", value: "[Admin] En tant qu'admin, je configure le tarif/abonné, bonus inscription, seuil de retrait" },
          { label: "US-027", value: "[Admin] En tant qu'admin, j'accède aux KPIs globaux : nb campagnes, CA, abonnés actifs, taux de fraude" },
          { label: "US-028", value: "[Notif] En tant qu'utilisateur, je reçois des notifications email/SMS aux étapes clés" },
        ],
      },
    ],
  },
  scrum: {
    sections: [
      {
        title: "Phases de Livraison",
        icon: "🗺️",
        color: "#3b82f6",
        items: [
          { label: "Phase 0 — Setup (S1)", value: "Repo GitHub, architecture Docker, CI/CD, environnements dev/staging/prod, design system" },
          { label: "Phase 1 — MVP Core (S2-S9)", value: "Auth + Profil + Campagne + Diffusion + Rémunération — Déploiement beta fermée" },
          { label: "Phase 2 — Paiements (S10-S11)", value: "Intégration agrégateur complète, retraits Mobile Money, escrow — Beta ouverte" },
          { label: "Phase 3 — Analytics & Admin (S12-S13)", value: "Rapports, dashboards, admin configurateur — Release candidate" },
          { label: "Phase 4 — Launch (S14)", value: "Tests de charge, audit sécurité, soumission App Store / Play Store, lancement public" },
        ],
      },
      {
        title: "Priorités Techniques (MoSCoW)",
        icon: "🎯",
        color: "#10b981",
        items: [
          { label: "MUST HAVE", value: "Auth · Profil dynamique · Bonus inscription · Campagne (4 types médias) · Matching · Tracking vue · Crédit abonné · Paiement annonceur · Retrait Mobile Money" },
          { label: "SHOULD HAVE", value: "Anti-fraude avancé · Dashboard annonceur temps réel · Rapport PDF automatique · Notifications push" },
          { label: "COULD HAVE", value: "Connexion OAuth Google/Facebook · Retrait VISA/PayPal · KYC documentaire · Programme de parrainage" },
          { label: "WON'T HAVE (V1)", value: "Marketplace de templates publicitaires · API publique · Programme d'affiliation tiers · Diffusion programmatique" },
        ],
      },
      {
        title: "Estimations & Équipe Recommandée",
        icon: "👨‍💻",
        color: "#8b5cf6",
        items: [
          { label: "Durée MVP", value: "~14 semaines (3,5 mois) avec une équipe de 4-5 développeurs" },
          { label: "Profils nécessaires", value: "1 Lead Dev Backend (Node.js) · 1 Dev Flutter Mobile · 1 Dev React Web · 1 Dev Full Stack (admin + intégrations) · 1 DevOps/Cloud" },
          { label: "Outils de suivi", value: "GitHub Projects ou Jira · Figma (UI/UX) · Postman (API) · Sentry (monitoring erreurs)" },
          { label: "Budget infra estimé", value: "~150-300 €/mois (démarrage) → ~800-1500 €/mois (10k+ abonnés actifs)" },
        ],
      },
      {
        title: "Risques & Mitigations",
        icon: "🚨",
        color: "#ef4444",
        items: [
          { label: "Risque #1 : Fraude massive", value: "→ Mitigation : Implémenter l'anti-fraude dès le Sprint 3, pas en V2" },
          { label: "Risque #2 : Intégration paiement complexe", value: "→ Mitigation : Prototyer CinetPay en Sprint 1 (sandbox), pas attendre Sprint 4" },
          { label: "Risque #3 : Lenteur sur faible réseau", value: "→ Mitigation : Mode offline partiel Flutter + lazy loading médias + CDN Cloudflare" },
          { label: "Risque #4 : Conformité ARTCI/données perso", value: "→ Mitigation : Audit juridique dès Phase 0, consentement explicite RGPD dans onboarding" },
          { label: "Risque #5 : Trésorerie négative", value: "→ Mitigation : Paiement annonceur systématiquement AVANT diffusion (escrow strict)" },
        ],
      },
      {
        title: "Prochaines Actions Immédiates",
        icon: "⚡",
        color: "#f59e0b",
        items: [
          { label: "Action 1", value: "Valider le modèle de rémunération exact (combien sur les 100 FCFA revient à l'abonné vs marge plateforme)" },
          { label: "Action 2", value: "Choisir et tester l'agrégateur de paiement (CinetPay ou FedaPay) — ouvrir un compte sandbox" },
          { label: "Action 3", value: "Créer les maquettes Figma (flows : inscription abonné, création campagne, vue publicité)" },
          { label: "Action 4", value: "Définir la grille de ciblage complète (quels critères, quelles valeurs possibles par critère)" },
          { label: "Action 5", value: "Réserver le domaine oon.click et configurer les environnements de développement" },
        ],
      },
    ],
  },
};

function Badge({ text, color }) {
  return (
    <span style={{
      background: color + "20",
      color: color,
      border: `1px solid ${color}40`,
      borderRadius: "6px",
      padding: "2px 10px",
      fontSize: "11px",
      fontWeight: "700",
      letterSpacing: "0.05em",
      textTransform: "uppercase",
      whiteSpace: "nowrap",
    }}>
      {text}
    </span>
  );
}

function Section({ section }) {
  return (
    <div style={{
      background: "#0f172a",
      border: `1px solid ${section.color}30`,
      borderRadius: "12px",
      marginBottom: "20px",
      overflow: "hidden",
    }}>
      <div style={{
        background: `linear-gradient(135deg, ${section.color}15, transparent)`,
        borderBottom: `1px solid ${section.color}25`,
        padding: "14px 20px",
        display: "flex",
        alignItems: "center",
        gap: "10px",
      }}>
        <span style={{ fontSize: "20px" }}>{section.icon}</span>
        <h3 style={{
          margin: 0,
          fontSize: "14px",
          fontWeight: "700",
          color: section.color,
          letterSpacing: "0.04em",
          textTransform: "uppercase",
        }}>{section.title}</h3>
      </div>
      <div style={{ padding: "4px 0" }}>
        {section.items.map((item, i) => (
          <div key={i} style={{
            display: "grid",
            gridTemplateColumns: "180px 1fr",
            gap: "12px",
            padding: "10px 20px",
            borderBottom: i < section.items.length - 1 ? "1px solid #1e293b" : "none",
            alignItems: "start",
          }}>
            <div>
              <Badge text={item.label} color={section.color} />
            </div>
            <div style={{
              fontSize: "13px",
              color: "#cbd5e1",
              lineHeight: "1.6",
            }}>{item.value}</div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default function BMADReport() {
  const [active, setActive] = useState("analyst");

  return (
    <div style={{
      minHeight: "100vh",
      background: "#020817",
      fontFamily: "'IBM Plex Mono', 'Fira Code', monospace",
      color: "#e2e8f0",
    }}>
      {/* Header */}
      <div style={{
        background: "linear-gradient(135deg, #0a0f1e 0%, #0d1b3e 50%, #0a0f1e 100%)",
        borderBottom: "1px solid #1e3a6e",
        padding: "28px 32px 20px",
      }}>
        <div style={{ display: "flex", alignItems: "center", gap: "16px", marginBottom: "6px" }}>
          <div style={{
            background: "linear-gradient(135deg, #3b82f6, #8b5cf6)",
            borderRadius: "10px",
            width: "44px",
            height: "44px",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            fontSize: "22px",
            flexShrink: 0,
          }}>👁</div>
          <div>
            <div style={{ fontSize: "11px", color: "#64748b", letterSpacing: "0.12em", textTransform: "uppercase", marginBottom: "2px" }}>
              BMAD Analysis Report · v1.0
            </div>
            <h1 style={{
              margin: 0,
              fontSize: "26px",
              fontWeight: "800",
              background: "linear-gradient(135deg, #60a5fa, #a78bfa, #34d399)",
              WebkitBackgroundClip: "text",
              WebkitTextFillColor: "transparent",
              letterSpacing: "-0.02em",
            }}>oon.click</h1>
          </div>
          <div style={{ marginLeft: "auto", textAlign: "right" }}>
            <div style={{ fontSize: "11px", color: "#475569" }}>Plateforme de publicité rémunérée</div>
            <div style={{ fontSize: "11px", color: "#475569" }}>Web + Mobile · Marché FCFA</div>
          </div>
        </div>

        {/* Agent Tabs */}
        <div style={{
          display: "flex",
          gap: "6px",
          marginTop: "20px",
          flexWrap: "wrap",
        }}>
          {agents.map(agent => (
            <button
              key={agent.id}
              onClick={() => setActive(agent.id)}
              style={{
                background: active === agent.id
                  ? "linear-gradient(135deg, #1e40af, #4c1d95)"
                  : "#0f172a",
                border: active === agent.id ? "1px solid #3b82f6" : "1px solid #1e293b",
                color: active === agent.id ? "#e2e8f0" : "#64748b",
                borderRadius: "8px",
                padding: "8px 16px",
                fontSize: "12px",
                fontWeight: "600",
                cursor: "pointer",
                transition: "all 0.15s",
                fontFamily: "inherit",
                letterSpacing: "0.02em",
              }}
            >
              {agent.label}
            </button>
          ))}
        </div>
      </div>

      {/* Content */}
      <div style={{ padding: "28px 32px", maxWidth: "1000px" }}>
        {/* Agent title */}
        <div style={{ marginBottom: "24px" }}>
          <div style={{ fontSize: "11px", color: "#475569", textTransform: "uppercase", letterSpacing: "0.1em", marginBottom: "4px" }}>
            Agent : {agents.find(a => a.id === active)?.label}
          </div>
          <h2 style={{
            margin: 0,
            fontSize: "20px",
            fontWeight: "700",
            color: "#f1f5f9",
          }}>
            {agents.find(a => a.id === active)?.title}
          </h2>
        </div>

        {/* Sections */}
        {content[active]?.sections.map((section, i) => (
          <Section key={i} section={section} />
        ))}
      </div>

      {/* Footer */}
      <div style={{
        borderTop: "1px solid #0f172a",
        padding: "16px 32px",
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        fontSize: "11px",
        color: "#334155",
      }}>
        <span>BMAD · Breakthrough Method of Agile AI-Driven Development</span>
        <span>oon.click · GS2E Analysis © 2026</span>
      </div>
    </div>
  );
}
