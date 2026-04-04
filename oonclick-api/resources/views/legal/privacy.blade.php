<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Politique de Confidentialité — oon.click</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --sky:    #2AABF0;
      --sky2:   #1A95D8;
      --sky3:   #0E7AB8;
      --navy:   #1B2A6E;
      --navy2:  #162058;
      --border: #C8E4F6;
      --muted:  #5A7098;
      --bg:     #F0F8FF;
      --pale:   #EBF7FE;
    }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--navy);
      line-height: 1.7;
    }

    /* ── Header ────────────────────────────────────────────────────────────── */
    .site-header {
      background: linear-gradient(135deg, var(--navy2) 0%, var(--sky2) 100%);
      padding: 28px 48px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
    }
    .header-brand {
      font-size: 26px;
      font-weight: 900;
      text-decoration: none;
      line-height: 1;
    }
    .header-brand .oon   { color: var(--sky); }
    .header-brand .click { color: rgba(255,255,255,0.9); }
    .header-meta {
      font-size: 13px;
      font-weight: 700;
      color: rgba(255,255,255,0.7);
      text-align: right;
    }
    .header-meta strong { color: white; display: block; font-size: 15px; }

    /* ── Layout ────────────────────────────────────────────────────────────── */
    .layout {
      display: flex;
      max-width: 1100px;
      margin: 0 auto;
      padding: 40px 24px;
      gap: 40px;
      align-items: flex-start;
    }

    /* ── Table of Contents ─────────────────────────────────────────────────── */
    .toc {
      width: 250px;
      flex-shrink: 0;
      background: white;
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 24px 20px;
      position: sticky;
      top: 24px;
      box-shadow: 0 4px 20px rgba(27,42,110,0.06);
    }
    .toc-title {
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--muted);
      margin-bottom: 14px;
    }
    .toc-list { list-style: none; }
    .toc-list li { margin-bottom: 6px; }
    .toc-list a {
      font-size: 12.5px;
      font-weight: 600;
      color: var(--muted);
      text-decoration: none;
      display: block;
      padding: 4px 8px;
      border-radius: 8px;
      transition: background 0.15s, color 0.15s;
      line-height: 1.4;
    }
    .toc-list a:hover {
      background: var(--pale);
      color: var(--sky2);
    }
    .toc-back {
      display: block;
      margin-top: 16px;
      font-size: 12px;
      font-weight: 700;
      color: var(--sky2);
      text-decoration: none;
      text-align: center;
      padding: 8px;
      border-radius: 8px;
      border: 1px solid var(--border);
      transition: background 0.15s;
    }
    .toc-back:hover { background: var(--pale); }

    /* ── Content ───────────────────────────────────────────────────────────── */
    .content { flex: 1; min-width: 0; }

    .doc-header {
      background: white;
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 32px 36px;
      margin-bottom: 28px;
      box-shadow: 0 4px 20px rgba(27,42,110,0.06);
    }
    .doc-header h1 {
      font-size: 28px;
      font-weight: 900;
      margin-bottom: 8px;
    }
    .doc-header .dpo-contact {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--pale);
      border: 1px solid var(--border);
      padding: 8px 16px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 700;
      color: var(--sky2);
      margin-top: 12px;
      text-decoration: none;
    }
    .doc-header .doc-meta {
      font-size: 13px;
      color: var(--muted);
      font-weight: 600;
      margin-bottom: 4px;
    }
    .doc-header .doc-meta span {
      display: inline-block;
      background: var(--pale);
      border: 1px solid var(--border);
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      color: var(--sky2);
      margin-right: 8px;
    }

    /* Preamble */
    .preamble {
      background: var(--pale);
      border-left: 4px solid var(--sky);
      border-radius: 0 12px 12px 0;
      padding: 20px 24px;
      margin-bottom: 28px;
      font-size: 14px;
      font-weight: 600;
      color: var(--navy);
      line-height: 1.7;
    }
    .preamble ul { margin-top: 10px; padding-left: 20px; }
    .preamble ul li { margin-bottom: 4px; font-size: 13px; }

    /* Article sections */
    .article {
      background: white;
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 28px 32px;
      margin-bottom: 20px;
      box-shadow: 0 2px 10px rgba(27,42,110,0.04);
    }
    .article h2 {
      font-size: 18px;
      font-weight: 900;
      color: var(--navy);
      margin-bottom: 16px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--pale);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .article h2 .art-num {
      background: linear-gradient(135deg, var(--sky), var(--sky3));
      color: white;
      font-size: 11px;
      font-weight: 800;
      padding: 3px 10px;
      border-radius: 20px;
      flex-shrink: 0;
    }
    .article h3 {
      font-size: 15px;
      font-weight: 800;
      color: var(--navy);
      margin-top: 18px;
      margin-bottom: 10px;
    }
    .article p {
      font-size: 14px;
      font-weight: 600;
      color: #3B4F70;
      margin-bottom: 12px;
      line-height: 1.75;
    }
    .article ul, .article ol {
      padding-left: 22px;
      margin-bottom: 12px;
    }
    .article ul li, .article ol li {
      font-size: 14px;
      font-weight: 600;
      color: #3B4F70;
      margin-bottom: 6px;
      line-height: 1.6;
    }

    /* Data tables */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 13px;
    }
    .data-table thead th {
      background: var(--pale);
      padding: 10px 14px;
      text-align: left;
      font-size: 11px;
      font-weight: 800;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      border-bottom: 2px solid var(--border);
    }
    .data-table tbody td {
      padding: 10px 14px;
      border-bottom: 1px solid var(--border);
      font-weight: 600;
      color: #3B4F70;
      vertical-align: top;
      line-height: 1.5;
    }
    .data-table tbody td:first-child { font-weight: 800; color: var(--navy); }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table tbody tr:hover td { background: #FAFCFF; }

    /* Badges */
    .badge-mandatory {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 800;
      background: #FEE2E2;
      color: #991B1B;
    }
    .badge-optional {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 800;
      background: #D1FAE5;
      color: #065F46;
    }

    /* Rights table */
    .rights-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 13px;
    }
    .rights-table thead th {
      background: var(--pale);
      padding: 10px 14px;
      text-align: left;
      font-size: 11px;
      font-weight: 800;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      border-bottom: 2px solid var(--border);
    }
    .rights-table tbody td {
      padding: 10px 14px;
      border-bottom: 1px solid var(--border);
      font-weight: 600;
      color: #3B4F70;
      vertical-align: top;
      line-height: 1.5;
    }
    .rights-table tbody td:first-child { font-weight: 800; color: var(--sky2); }
    .rights-table tbody tr:last-child td { border-bottom: none; }

    /* Info / warn boxes */
    .info-box {
      background: var(--pale);
      border: 1px solid var(--border);
      border-left: 4px solid var(--sky);
      border-radius: 0 10px 10px 0;
      padding: 14px 18px;
      margin: 14px 0;
      font-size: 13.5px;
      font-weight: 600;
      color: var(--navy);
      line-height: 1.6;
    }
    .warn-box {
      background: #FFF9ED;
      border: 1px solid #FDE68A;
      border-left: 4px solid #F59E0B;
      border-radius: 0 10px 10px 0;
      padding: 14px 18px;
      margin: 14px 0;
      font-size: 13.5px;
      font-weight: 700;
      color: #78350F;
    }
    .success-box {
      background: #F0FDF4;
      border: 1px solid #BBF7D0;
      border-left: 4px solid #22C55E;
      border-radius: 0 10px 10px 0;
      padding: 14px 18px;
      margin: 14px 0;
      font-size: 13.5px;
      font-weight: 600;
      color: #14532D;
    }

    /* Footer */
    .legal-footer {
      max-width: 1100px;
      margin: 40px auto 0;
      padding: 24px;
      text-align: center;
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      border-top: 1px solid var(--border);
    }
    .legal-footer a { color: var(--sky2); text-decoration: none; }

    @media (max-width: 800px) {
      .layout { flex-direction: column; padding: 20px 16px; }
      .toc { width: 100%; position: static; }
      .site-header { padding: 20px 24px; }
      .article { padding: 20px 18px; }
      .doc-header { padding: 24px 20px; }
      .data-table, .rights-table { font-size: 12px; }
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
  <a class="header-brand" href="{{ route('home') }}">
    <span class="oon">oon</span><span class="click">.click</span>
  </a>
  <div class="header-meta">
    <strong>Politique de Confidentialité</strong>
    Version 1.0 &mdash; 3 avril 2026
  </div>
</header>

<div class="layout">

  <!-- TABLE OF CONTENTS -->
  <nav class="toc" aria-label="Table des matières">
    <div class="toc-title">Table des matières</div>
    <ul class="toc-list">
      <li><a href="#preambule">Préambule</a></li>
      <li><a href="#art1">Art. 1 &mdash; Responsable du traitement</a></li>
      <li><a href="#art2">Art. 2 &mdash; Données collectées</a></li>
      <li><a href="#art3">Art. 3 &mdash; Finalités du traitement</a></li>
      <li><a href="#art4">Art. 4 &mdash; Partage des données</a></li>
      <li><a href="#art5">Art. 5 &mdash; Consentement et monétisation</a></li>
      <li><a href="#art6">Art. 6 &mdash; Sécurité des données</a></li>
      <li><a href="#art7">Art. 7 &mdash; Droits des Utilisateurs</a></li>
      <li><a href="#art8">Art. 8 &mdash; Cookies et technologies</a></li>
      <li><a href="#art9">Art. 9 &mdash; Conservation des données</a></li>
      <li><a href="#art10">Art. 10 &mdash; Protection des mineurs</a></li>
      <li><a href="#art11">Art. 11 &mdash; Modification de la Politique</a></li>
      <li><a href="#art12">Art. 12 &mdash; Déclaration ARTCI</a></li>
      <li><a href="#art13">Art. 13 &mdash; Contact</a></li>
    </ul>
    <a class="toc-back" href="{{ route('home') }}">&larr; Retour à l'accueil</a>
  </nav>

  <!-- DOCUMENT CONTENT -->
  <main class="content">

    <div class="doc-header">
      <h1>Politique de Confidentialité</h1>
      <div class="doc-meta">
        <span>Version 1.0</span>
        <span>3 avril 2026</span>
        République de Côte d'Ivoire
      </div>
      <a href="mailto:dpo@oon.click" class="dpo-contact">
        Délégué à la Protection des Données (DPO) : dpo@oon.click
      </a>
    </div>

    <!-- PREAMBULE -->
    <div class="preamble" id="preambule">
      <strong>Préambule</strong>
      <p style="margin-top:10px;">La société OON CLICK, éditrice de la plateforme oon.click, s'engage à protéger la vie privée et les données à caractère personnel de ses Utilisateurs conformément à la législation ivoirienne en vigueur.</p>
      <p>La présente Politique est établie conformément aux dispositions de :</p>
      <ul>
        <li>Loi n&deg;2013-450 du 19 juin 2013 relative à la protection des données à caractère personnel ;</li>
        <li>Loi n&deg;2013-451 du 19 juin 2013 relative à la lutte contre la cybercriminalité (modifiée par la Loi n&deg;2023-593) ;</li>
        <li>Loi n&deg;2013-546 du 30 juillet 2013 relative aux transactions électroniques ;</li>
        <li>Loi n&deg;2016-412 du 15 juin 2016 relative à la consommation ;</li>
        <li>Loi n&deg;2020-522 du 16 juin 2020 portant régime juridique de la communication publicitaire ;</li>
        <li>Loi n&deg;2024-352 du 6 juin 2024 relative aux communications électroniques ;</li>
        <li>Le Règlement Général sur la Protection des Données (RGPD) de l'Union Européenne, appliqué à titre de référence de bonnes pratiques internationales.</li>
      </ul>
      <p style="margin-top:10px;">L'ARTCI (Autorité de Régulation des Télécommunications/TIC de Côte d'Ivoire) est l'autorité compétente en matière de protection des données à caractère personnel.</p>
    </div>

    <!-- ARTICLE 1 -->
    <section class="article" id="art1">
      <h2><span class="art-num">Article 1</span> Responsable du traitement</h2>
      <p>Le responsable du traitement des données à caractère personnel est :</p>
      <div class="info-box">
        <strong>OON CLICK</strong><br>
        Forme juridique : Société de droit ivoirien<br>
        Siège social : Abidjan, Côte d'Ivoire<br>
        E-mail DPO : <a href="mailto:dpo@oon.click" style="color:var(--sky2);">dpo@oon.click</a><br>
        Site web : <a href="https://oon.click" style="color:var(--sky2);">https://oon.click</a>
      </div>
      <p>L'Éditeur a désigné un Délégué à la Protection des Données (DPO) joignable à l'adresse <a href="mailto:dpo@oon.click" style="color:var(--sky2);font-weight:700;">dpo@oon.click</a>, conformément à la Loi n&deg;2013-450.</p>
    </section>

    <!-- ARTICLE 2 -->
    <section class="article" id="art2">
      <h2><span class="art-num">Article 2</span> Données collectées</h2>

      <h3>2.1 Données fournies directement par l'Utilisateur</h3>
      <table class="data-table">
        <thead>
          <tr>
            <th>Catégorie</th>
            <th>Données concernées</th>
            <th>Caractère</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Identité</td>
            <td>Nom, prénom, date de naissance, genre</td>
            <td><span class="badge-mandatory">Obligatoire</span></td>
          </tr>
          <tr>
            <td>Contact</td>
            <td>Numéro de téléphone, adresse e-mail</td>
            <td><span class="badge-mandatory">Obligatoire</span></td>
          </tr>
          <tr>
            <td>Localisation</td>
            <td>Ville, commune, quartier, pays</td>
            <td><span class="badge-mandatory">Obligatoire</span></td>
          </tr>
          <tr>
            <td>Socio-professionnel</td>
            <td>Éducation, profession, fonction, tranche salariale</td>
            <td><span class="badge-mandatory">Obligatoire</span></td>
          </tr>
          <tr>
            <td>Santé</td>
            <td>Groupe sanguin</td>
            <td><span class="badge-optional">Facultatif (avec consentement spécifique)</span></td>
          </tr>
          <tr>
            <td>Préférences</td>
            <td>Centres d'intérêt (15 catégories)</td>
            <td><span class="badge-mandatory">Obligatoire</span></td>
          </tr>
          <tr>
            <td>Entreprise (Annonceur)</td>
            <td>Raison sociale, secteur, RCCM, NIF, site web, adresse, taille, budget mensuel</td>
            <td><span class="badge-mandatory">Obligatoire pour Annonceurs</span></td>
          </tr>
          <tr>
            <td>Vérification KYC</td>
            <td>Pièce d'identité (CNI, passeport), selfie, justificatif de domicile, registre de commerce</td>
            <td><span class="badge-mandatory">Obligatoire selon le niveau KYC</span></td>
          </tr>
          <tr>
            <td>Parrainage</td>
            <td>Code de parrainage, référent</td>
            <td><span class="badge-optional">Facultatif</span></td>
          </tr>
        </tbody>
      </table>

      <h3>2.2 Données collectées automatiquement</h3>
      <table class="data-table">
        <thead>
          <tr>
            <th>Catégorie</th>
            <th>Données concernées</th>
            <th>Finalité</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Appareil</td>
            <td>Empreinte numérique (device fingerprint), modèle, système d'exploitation, version de l'application</td>
            <td>Sécurité et anti-fraude</td>
          </tr>
          <tr>
            <td>Connexion</td>
            <td>Adresse IP, user agent, date et heure de connexion</td>
            <td>Sécurité et audit</td>
          </tr>
          <tr>
            <td>Comportement</td>
            <td>Historique de visionnage, durée de visionnage, taux de complétion, interactions</td>
            <td>Ciblage et amélioration du service</td>
          </tr>
          <tr>
            <td>Engagement</td>
            <td>Niveaux, badges, streaks, scores de confiance (OON Trust Score)</td>
            <td>Gamification et lutte anti-fraude</td>
          </tr>
          <tr>
            <td>Financier</td>
            <td>Solde du portefeuille, historique des transactions, demandes de retrait</td>
            <td>Gestion financière</td>
          </tr>
          <tr>
            <td>Géolocalisation</td>
            <td>Coordonnées GPS (si autorisé)</td>
            <td>Ciblage géographique (avec consentement)</td>
          </tr>
          <tr>
            <td>Token FCM</td>
            <td>Token Firebase Cloud Messaging, type d'appareil</td>
            <td>Notifications push</td>
          </tr>
        </tbody>
      </table>

      <h3>2.3 Données sensibles</h3>
      <p>Au sens de la Loi n&deg;2013-450, certaines données collectées sont qualifiées de « sensibles », notamment le groupe sanguin et les données de santé. La collecte de ces données est facultative et ne peut être effectuée qu'avec le consentement explicite et spécifique de l'Utilisateur, recueilli par un mécanisme distinct (case à cocher séparée, non pré-cochée).</p>
      <p>L'Utilisateur peut retirer son consentement à tout moment pour ces données sensibles sans affecter son accès aux fonctionnalités principales de la Plateforme.</p>
    </section>

    <!-- ARTICLE 3 -->
    <section class="article" id="art3">
      <h2><span class="art-num">Article 3</span> Finalités du traitement</h2>
      <p>Les données à caractère personnel sont traitées pour les finalités suivantes :</p>
      <table class="data-table">
        <thead>
          <tr>
            <th>Finalité</th>
            <th>Base légale</th>
            <th>Durée de conservation</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Création et gestion des comptes Utilisateurs</td>
            <td>Exécution du contrat (CGU)</td>
            <td>Durée de la relation contractuelle + 5 ans</td>
          </tr>
          <tr>
            <td>Fourniture du service de publicité rémunérée</td>
            <td>Exécution du contrat</td>
            <td>Durée de la relation contractuelle</td>
          </tr>
          <tr>
            <td>Ciblage publicitaire (démographique, géographique, par centres d'intérêt)</td>
            <td>Consentement explicite de l'Utilisateur</td>
            <td>Durée du consentement</td>
          </tr>
          <tr>
            <td>Mise à disposition de données agrégées aux Annonceurs</td>
            <td>Consentement explicite (monétisation)</td>
            <td>Durée du consentement</td>
          </tr>
          <tr>
            <td>Vérification d'identité (KYC)</td>
            <td>Obligation légale (réglementation BCEAO)</td>
            <td>5 ans après la clôture du compte</td>
          </tr>
          <tr>
            <td>Lutte contre la fraude et scoring de confiance</td>
            <td>Intérêt légitime de l'Éditeur</td>
            <td>3 ans après le dernier événement</td>
          </tr>
          <tr>
            <td>Gestion des paiements et retraits</td>
            <td>Obligation légale et exécution du contrat</td>
            <td>10 ans (obligations comptables)</td>
          </tr>
          <tr>
            <td>Amélioration du service et analyses statistiques</td>
            <td>Intérêt légitime (données anonymisées)</td>
            <td>Indéfini (après anonymisation)</td>
          </tr>
          <tr>
            <td>Envoi de notifications (push, e-mail, SMS)</td>
            <td>Consentement</td>
            <td>Durée du consentement</td>
          </tr>
          <tr>
            <td>Modération des contenus publicitaires</td>
            <td>Obligation légale (Loi n&deg;2020-522)</td>
            <td>1 an après fin de la campagne</td>
          </tr>
          <tr>
            <td>Audit et traçabilité (journal d'audit immuable)</td>
            <td>Intérêt légitime et obligation légale</td>
            <td>5 ans minimum</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- ARTICLE 4 -->
    <section class="article" id="art4">
      <h2><span class="art-num">Article 4</span> Partage et destinataires des données</h2>

      <h3>4.1 Données partagées avec les Annonceurs</h3>
      <p>Les Annonceurs accèdent aux données suivantes dans le cadre de leurs campagnes publicitaires :</p>
      <ul>
        <li>Données agrégées et anonymisées : statistiques démographiques, statistiques d'engagement, données de performance de campagne ;</li>
        <li>Estimation de portée (reach) : calculée en temps réel sur la base des critères de ciblage, sans identification individuelle ;</li>
        <li>Rapports de campagne : incluant des ventilations démographiques et géographiques agrégées.</li>
      </ul>
      <div class="warn-box">EN AUCUN CAS les données nominatives, les coordonnées personnelles (téléphone, e-mail) ou les pièces d'identité des Abonnés ne sont transmises directement aux Annonceurs, sauf consentement explicite et spécifique de l'Abonné via le mécanisme de « Consentement de Monétisation Avancée ».</div>

      <h3>4.2 Sous-traitants et prestataires techniques</h3>
      <table class="data-table">
        <thead>
          <tr>
            <th>Sous-traitant</th>
            <th>Service fourni</th>
            <th>Données concernées</th>
            <th>Localisation</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Paystack</td>
            <td>Paiements (Mobile Money, VISA)</td>
            <td>Données financières</td>
            <td>Nigeria/International</td>
          </tr>
          <tr>
            <td>Firebase (Google)</td>
            <td>Authentification, notifications push</td>
            <td>Téléphone, tokens FCM</td>
            <td>International</td>
          </tr>
          <tr>
            <td>AWS (Amazon)</td>
            <td>Stockage média (S3), modération IA (Rekognition)</td>
            <td>Médias, contenus publicitaires</td>
            <td>International</td>
          </tr>
          <tr>
            <td>Cloudflare</td>
            <td>Sécurité (WAF, DDoS), stockage (R2)</td>
            <td>Données de connexion, fichiers KYC</td>
            <td>International</td>
          </tr>
          <tr>
            <td>SendGrid</td>
            <td>E-mails transactionnels</td>
            <td>Adresse e-mail</td>
            <td>International</td>
          </tr>
          <tr>
            <td>Twilio</td>
            <td>SMS (OTP, notifications)</td>
            <td>Numéro de téléphone</td>
            <td>International</td>
          </tr>
          <tr>
            <td>Sentry</td>
            <td>Suivi d'erreurs</td>
            <td>Données techniques anonymisées</td>
            <td>International</td>
          </tr>
          <tr>
            <td>Google Cloud Platform</td>
            <td>Hébergement infrastructure</td>
            <td>Toutes données</td>
            <td>International</td>
          </tr>
        </tbody>
      </table>
      <p>Conformément à la Loi n&deg;2013-450, le transfert de données vers des pays tiers est encadré par des clauses contractuelles types et des garanties de sécurité adéquates. L'Utilisateur est informé et consent expressément à ces transferts lors de l'acceptation des présentes.</p>

      <h3>4.3 Autorités compétentes</h3>
      <p>L'Éditeur peut être amené à communiquer des données aux autorités judiciaires, administratives ou réglementaires compétentes (ARTCI, ACP, autorités judiciaires) sur réquisition légale ou dans le cadre de la lutte contre la fraude et la cybercriminalité.</p>
    </section>

    <!-- ARTICLE 5 -->
    <section class="article" id="art5">
      <h2><span class="art-num">Article 5</span> Consentement et monétisation des données</h2>

      <h3>5.1 Principe de la rémunération liée aux données</h3>
      <div class="info-box">
        La Plateforme repose sur un modèle économique dans lequel les Abonnés mettent à disposition certaines de leurs données de profil pour permettre un ciblage publicitaire pertinent, en contrepartie d'une rémunération en FCFA pour chaque contenu publicitaire consommé. Ce modèle est conforme à la Loi n&deg;2013-450 dès lors que le consentement est libre, spécifique, éclairé et univoque.
      </div>

      <h3>5.2 Niveaux de consentement</h3>
      <table class="data-table">
        <thead>
          <tr>
            <th>Type de consentement</th>
            <th>Données concernées</th>
            <th>Moment de la collecte</th>
            <th>Révocable</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Consentement de base (CGU)</td>
            <td>Données de profil obligatoires, utilisation du service</td>
            <td>Inscription</td>
            <td>Oui (entraîne la clôture du compte)</td>
          </tr>
          <tr>
            <td>Consentement au ciblage publicitaire</td>
            <td>Données démographiques, localisation, intérêts pour ciblage</td>
            <td>Inscription (case séparée)</td>
            <td>Oui (réduit la pertinence des pubs)</td>
          </tr>
          <tr>
            <td>Consentement à la géolocalisation</td>
            <td>Coordonnées GPS en temps réel</td>
            <td>Première utilisation de la fonction</td>
            <td>Oui</td>
          </tr>
          <tr>
            <td>Consentement aux données sensibles</td>
            <td>Groupe sanguin, données de santé</td>
            <td>Profil (case spécifique)</td>
            <td>Oui</td>
          </tr>
          <tr>
            <td>Consentement aux notifications</td>
            <td>Token FCM, préférences de communication</td>
            <td>Après inscription</td>
            <td>Oui</td>
          </tr>
          <tr>
            <td>Consentement de monétisation avancée</td>
            <td>Données nominatives partagées individuellement avec Annonceurs</td>
            <td>Sur demande, avant partage</td>
            <td>Oui</td>
          </tr>
          <tr>
            <td>Consentement au transfert international</td>
            <td>Toutes données traitées par des sous-traitants internationaux</td>
            <td>Inscription</td>
            <td>Oui (entraîne la clôture)</td>
          </tr>
        </tbody>
      </table>

      <h3>5.3 Valeur probante du consentement</h3>
      <p>Conformément à la Loi n&deg;2013-546, chaque acte de consentement est :</p>
      <ul>
        <li>Horodaté avec précision (date, heure, minute, seconde) ;</li>
        <li>Associé à l'identifiant unique de l'Utilisateur ;</li>
        <li>Enregistré avec l'adresse IP et l'identifiant de l'appareil ;</li>
        <li>Archivé dans le journal d'audit immuable de la Plateforme ;</li>
        <li>Lié à la version exacte du texte juridique accepté.</li>
      </ul>
      <div class="success-box">Ces éléments constituent une preuve électronique recevable devant les juridictions ivoiriennes. L'Éditeur conserve l'intégralité de ces preuves pendant une durée minimale de dix (10) ans.</div>
    </section>

    <!-- ARTICLE 6 -->
    <section class="article" id="art6">
      <h2><span class="art-num">Article 6</span> Sécurité des données</h2>
      <p>L'Éditeur met en oeuvre les mesures techniques et organisationnelles suivantes :</p>
      <ul>
        <li><strong>Chiffrement en transit :</strong> TLS 1.3 obligatoire sur toutes les communications, HSTS preload, épinglage de certificat (certificate pinning) sur l'application mobile ;</li>
        <li><strong>Chiffrement au repos :</strong> AES-256 pour les documents KYC et les données sensibles ;</li>
        <li><strong>Authentification :</strong> JWT RS256 avec durée limitée (15 minutes), tokens de rafraîchissement rotatifs (7 jours), liste noire en cache Redis ;</li>
        <li><strong>Contrôle d'accès :</strong> RBAC (Role-Based Access Control) avec vérification du propriétaire sur chaque ressource ;</li>
        <li><strong>Infrastructure :</strong> VPC privé, pare-feu applicatif Cloudflare (WAF), protection anti-DDoS, limitation de débit (rate limiting) ;</li>
        <li><strong>Stockage sécurisé :</strong> Google Secret Manager pour les secrets d'application, Cloudflare R2 pour les fichiers KYC avec accès restreint ;</li>
        <li><strong>Journalisation :</strong> Logs d'audit immuables enregistrant toute action administrative ;</li>
        <li><strong>Sauvegarde :</strong> Sauvegardes chiffrées quotidiennes avec rétention de 30 jours et plan de reprise d'activité (PRA).</li>
      </ul>
      <div class="info-box">En cas de violation de données personnelles, l'Éditeur s'engage à notifier l'ARTCI dans les 72 heures suivant la découverte de l'incident, et à informer les Utilisateurs concernés dans les meilleurs délais, conformément aux dispositions de la Loi n&deg;2013-450.</div>
    </section>

    <!-- ARTICLE 7 -->
    <section class="article" id="art7">
      <h2><span class="art-num">Article 7</span> Droits des Utilisateurs</h2>
      <p>Conformément à la Loi n&deg;2013-450, l'Utilisateur dispose des droits suivants :</p>
      <table class="rights-table">
        <thead>
          <tr>
            <th>Droit</th>
            <th>Description</th>
            <th>Modalité d'exercice</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Droit d'accès</td>
            <td>Obtenir la confirmation du traitement de ses données et en obtenir copie</td>
            <td>Demande à dpo@oon.click ou via l'application</td>
          </tr>
          <tr>
            <td>Droit de rectification</td>
            <td>Faire corriger des données inexactes ou incomplètes</td>
            <td>Modification directe dans le profil ou demande au DPO</td>
          </tr>
          <tr>
            <td>Droit de suppression</td>
            <td>Demander l'effacement de ses données personnelles</td>
            <td>Demande à dpo@oon.click (sous réserve des obligations légales)</td>
          </tr>
          <tr>
            <td>Droit d'opposition</td>
            <td>S'opposer au traitement de ses données pour des motifs légitimes</td>
            <td>Demande à dpo@oon.click</td>
          </tr>
          <tr>
            <td>Droit à la limitation</td>
            <td>Demander la limitation du traitement dans certains cas</td>
            <td>Demande à dpo@oon.click</td>
          </tr>
          <tr>
            <td>Droit à la portabilité</td>
            <td>Recevoir ses données dans un format structuré et lisible par machine</td>
            <td>Demande à dpo@oon.click (format JSON)</td>
          </tr>
          <tr>
            <td>Droit de retrait du consentement</td>
            <td>Retirer son consentement à tout moment sans effet rétroactif</td>
            <td>Centre de préférences dans l'application ou demande au DPO</td>
          </tr>
          <tr>
            <td>Droit de réclamation</td>
            <td>Saisir l'ARTCI en cas de violation</td>
            <td>Saisine de l'ARTCI (<a href="https://www.artci.ci" target="_blank" style="color:var(--sky2);">www.artci.ci</a>)</td>
          </tr>
        </tbody>
      </table>
      <p>L'Éditeur s'engage à répondre à toute demande relative à l'exercice de ces droits dans un délai maximum de trente (30) jours à compter de la réception de la demande.</p>

      @auth
      <div class="info-box" style="margin-top:16px;">
        Gérez vos consentements directement depuis votre compte :
        <a href="{{ route('legal.consents') }}" style="color:var(--sky2);font-weight:800;display:inline-block;margin-top:6px;">Gérer mes consentements &rarr;</a>
      </div>
      @endauth
    </section>

    <!-- ARTICLE 8 -->
    <section class="article" id="art8">
      <h2><span class="art-num">Article 8</span> Cookies et technologies similaires</h2>
      <p>L'application mobile oon.click utilise des technologies de stockage local (cache local, Hive, SecureStorage) pour :</p>
      <ul>
        <li>Maintenir la session de l'Utilisateur connecté ;</li>
        <li>Stocker les préférences de l'Utilisateur (mode hors ligne, préférences de notification) ;</li>
        <li>Permettre le fonctionnement du mode hors ligne (cache de contenus publicitaires) ;</li>
        <li>Améliorer la performance et réduire la consommation de données mobiles.</li>
      </ul>
      <p>Le site web associé peut utiliser des cookies. L'Utilisateur sera invité à donner son consentement via un bandeau de cookies lors de sa première visite.</p>
    </section>

    <!-- ARTICLE 9 -->
    <section class="article" id="art9">
      <h2><span class="art-num">Article 9</span> Conservation des données</h2>
      <p>Les durées de conservation des données sont déterminées en fonction de la finalité du traitement et des obligations légales applicables (cf. Article 3). À l'expiration de ces délais, les données sont :</p>
      <ul>
        <li>Supprimées de manière irréversible ; ou</li>
        <li>Anonymisées de manière irréversible pour être utilisées à des fins statistiques uniquement.</li>
      </ul>
      <p>Les documents KYC sont conservés pendant cinq (5) ans après la clôture du compte, conformément aux obligations réglementaires de la BCEAO en matière de lutte contre le blanchiment de capitaux.</p>
      <p>Les journaux d'audit sont conservés pendant cinq (5) ans minimum et sont immuables (aucune modification ni suppression possible).</p>
    </section>

    <!-- ARTICLE 10 -->
    <section class="article" id="art10">
      <h2><span class="art-num">Article 10</span> Protection des mineurs</h2>
      <p>La Plateforme est réservée aux personnes âgées d'au moins dix-huit (18) ans. L'Éditeur ne collecte pas sciemment de données relatives à des mineurs. Si l'Éditeur découvre qu'un mineur a créé un compte, celui-ci sera immédiatement suspendu et les données associées seront supprimées.</p>
      <p>Tout représentant légal souhaitant signaler la présence d'un mineur sur la Plateforme peut contacter le DPO à l'adresse <a href="mailto:dpo@oon.click" style="color:var(--sky2);font-weight:700;">dpo@oon.click</a>.</p>
    </section>

    <!-- ARTICLE 11 -->
    <section class="article" id="art11">
      <h2><span class="art-num">Article 11</span> Modification de la Politique de Confidentialité</h2>
      <p>L'Éditeur se réserve le droit de modifier la présente Politique à tout moment. Toute modification substantielle sera notifiée aux Utilisateurs par notification push, e-mail ou affichage dans l'application, au moins quinze (15) jours avant son entrée en vigueur.</p>
      <p>En cas de modification affectant les finalités du traitement ou les catégories de données traitées, un nouveau consentement explicite sera demandé à l'Utilisateur.</p>
    </section>

    <!-- ARTICLE 12 -->
    <section class="article" id="art12">
      <h2><span class="art-num">Article 12</span> Déclaration auprès de l'ARTCI</h2>
      <p>Conformément à la Loi n&deg;2013-450, l'Éditeur s'engage à effectuer les déclarations nécessaires auprès de l'ARTCI pour le traitement des données à caractère personnel, et à se conformer à toute demande ou recommandation de ladite autorité.</p>
      <p>Le numéro de déclaration sera communiqué dès obtention et intégré dans la présente Politique.</p>
    </section>

    <!-- ARTICLE 13 -->
    <section class="article" id="art13">
      <h2><span class="art-num">Article 13</span> Contact</h2>
      <p>Pour toute question relative à la présente Politique de Confidentialité ou pour exercer vos droits :</p>
      <ul>
        <li><strong>Délégué à la Protection des Données (DPO) :</strong> <a href="mailto:dpo@oon.click" style="color:var(--sky2);font-weight:700;">dpo@oon.click</a></li>
        <li><strong>Support général :</strong> <a href="mailto:support@oon.click" style="color:var(--sky2);font-weight:700;">support@oon.click</a></li>
        <li><strong>Courrier :</strong> OON CLICK, Abidjan, Côte d'Ivoire</li>
        <li><strong>ARTCI (autorité de contrôle) :</strong> <a href="https://www.artci.ci" target="_blank" style="color:var(--sky2);font-weight:700;">www.artci.ci</a></li>
      </ul>
    </section>

  </main>
</div>

<!-- FOOTER -->
<footer class="legal-footer">
  <p>
    &copy; {{ date('Y') }} oon.click &mdash; Politique de Confidentialité Version 1.0 &mdash; 3 avril 2026 &mdash; République de Côte d'Ivoire
    &nbsp;|&nbsp;
    <a href="{{ route('legal.cgu') }}">Conditions Générales d'Utilisation</a>
    &nbsp;|&nbsp;
    <a href="{{ route('home') }}">Retour à l'accueil</a>
  </p>
</footer>

</body>
</html>
