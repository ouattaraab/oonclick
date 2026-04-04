<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Conditions Générales d'Utilisation — oon.click</title>
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
      background: linear-gradient(135deg, var(--navy) 0%, var(--sky3) 100%);
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
    .content {
      flex: 1;
      min-width: 0;
    }

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
    .doc-header .doc-meta {
      font-size: 13px;
      color: var(--muted);
      font-weight: 600;
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
    .preamble ul {
      margin-top: 10px;
      padding-left: 20px;
    }
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

    /* Définition table */
    .def-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
      font-size: 13.5px;
    }
    .def-table thead th {
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
    .def-table tbody td {
      padding: 10px 14px;
      border-bottom: 1px solid var(--border);
      font-weight: 600;
      color: #3B4F70;
      vertical-align: top;
      line-height: 1.55;
    }
    .def-table tbody td:first-child {
      font-weight: 800;
      color: var(--navy);
      white-space: nowrap;
      min-width: 160px;
    }
    .def-table tbody tr:last-child td { border-bottom: none; }
    .def-table tbody tr:hover td { background: #FAFCFF; }

    /* KYC levels */
    .kyc-list {
      list-style: none;
      padding: 0;
      margin-top: 8px;
    }
    .kyc-list li {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 8px 0;
      border-bottom: 1px solid var(--border);
      font-size: 13.5px;
    }
    .kyc-list li:last-child { border-bottom: none; }
    .kyc-badge {
      background: var(--pale);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2px 10px;
      font-size: 11px;
      font-weight: 800;
      color: var(--sky2);
      white-space: nowrap;
      flex-shrink: 0;
    }

    /* Forbidden / highlight boxes */
    .warn-box {
      background: #FFF9ED;
      border: 1px solid #FDE68A;
      border-left: 4px solid #F59E0B;
      border-radius: 0 10px 10px 0;
      padding: 14px 18px;
      margin: 14px 0;
      font-size: 13.5px;
      font-weight: 600;
      color: #78350F;
    }
    .warn-box ul { padding-left: 18px; margin-top: 6px; }
    .warn-box ul li { margin-bottom: 4px; }

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
    <strong>Conditions Générales d'Utilisation</strong>
    Version 1.0 &mdash; 3 avril 2026
  </div>
</header>

<div class="layout">

  <!-- TABLE OF CONTENTS -->
  <nav class="toc" aria-label="Table des matières">
    <div class="toc-title">Table des matières</div>
    <ul class="toc-list">
      <li><a href="#preambule">Préambule</a></li>
      <li><a href="#art1">Art. 1 &mdash; Définitions</a></li>
      <li><a href="#art2">Art. 2 &mdash; Objet</a></li>
      <li><a href="#art3">Art. 3 &mdash; Inscription et accès</a></li>
      <li><a href="#art4">Art. 4 &mdash; Fonctionnement Abonnés</a></li>
      <li><a href="#art5">Art. 5 &mdash; Fonctionnement Annonceurs</a></li>
      <li><a href="#art6">Art. 6 &mdash; Obligations de l'Utilisateur</a></li>
      <li><a href="#art7">Art. 7 &mdash; Système de confiance</a></li>
      <li><a href="#art8">Art. 8 &mdash; Propriété intellectuelle</a></li>
      <li><a href="#art9">Art. 9 &mdash; Responsabilité</a></li>
      <li><a href="#art10">Art. 10 &mdash; Données personnelles</a></li>
      <li><a href="#art11">Art. 11 &mdash; Communication publicitaire</a></li>
      <li><a href="#art12">Art. 12 &mdash; Suspension et Résiliation</a></li>
      <li><a href="#art13">Art. 13 &mdash; Modification des CGU</a></li>
      <li><a href="#art14">Art. 14 &mdash; Règlement des différends</a></li>
      <li><a href="#art15">Art. 15 &mdash; Droit applicable</a></li>
      <li><a href="#art16">Art. 16 &mdash; Force majeure</a></li>
      <li><a href="#art17">Art. 17 &mdash; Divisibilité</a></li>
      <li><a href="#art18">Art. 18 &mdash; Intégralité</a></li>
      <li><a href="#art19">Art. 19 &mdash; Acceptation</a></li>
      <li><a href="#art20">Art. 20 &mdash; Contact</a></li>
    </ul>
    <a class="toc-back" href="{{ route('home') }}">&larr; Retour à l'accueil</a>
  </nav>

  <!-- DOCUMENT CONTENT -->
  <main class="content">

    <div class="doc-header">
      <h1>Conditions Générales d'Utilisation (CGU)</h1>
      <div class="doc-meta">
        <span>Version 1.0</span>
        <span>3 avril 2026</span>
        République de Côte d'Ivoire
      </div>
    </div>

    <!-- PREAMBULE -->
    <div class="preamble" id="preambule">
      <strong>Préambule</strong>
      <p style="margin-top:10px;">La plateforme oon.click est un service numérique de publicité rémunérée édité et exploité par la société OON CLICK, société de droit ivoirien dont le siège social est situé à Abidjan, Côte d'Ivoire.</p>
      <p>OON permet aux Abonnés de visionner des contenus publicitaires (images, vidéos, audio, textes enrichis) diffusés par des Annonceurs et de percevoir une rémunération en Francs CFA (FCFA) pour chaque contenu publicitaire entièrement consommé.</p>
      <p>Les présentes CGU sont régies par le droit ivoirien, notamment :</p>
      <ul>
        <li>Loi n&deg;2013-450 du 19 juin 2013 relative à la protection des données à caractère personnel ;</li>
        <li>Loi n&deg;2013-451 du 19 juin 2013 relative à la lutte contre la cybercriminalité (telle que modifiée par la Loi n&deg;2023-593) ;</li>
        <li>Loi n&deg;2013-546 du 30 juillet 2013 relative aux transactions électroniques ;</li>
        <li>Loi n&deg;2016-412 du 15 juin 2016 relative à la consommation ;</li>
        <li>Loi n&deg;2020-522 du 16 juin 2020 portant régime juridique de la communication publicitaire ;</li>
        <li>Loi n&deg;2024-352 du 6 juin 2024 relative aux communications électroniques ;</li>
        <li>Règlements de l'UEMOA et instructions de la BCEAO relatifs à la monnaie électronique.</li>
      </ul>
    </div>

    <!-- ARTICLE 1 -->
    <section class="article" id="art1">
      <h2><span class="art-num">Article 1</span> Définitions</h2>
      <table class="def-table">
        <thead>
          <tr>
            <th>Terme</th>
            <th>Définition</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>« Abonné » ou « Subscriber »</td>
            <td>Toute personne physique inscrite sur la Plateforme en qualité de spectateur de contenus publicitaires, dans le but de percevoir une rémunération pour chaque publicité entièrement consommée.</td>
          </tr>
          <tr>
            <td>« Annonceur » ou « Advertiser »</td>
            <td>Toute personne physique ou morale utilisant la Plateforme pour créer, financer et diffuser des campagnes publicitaires ciblées.</td>
          </tr>
          <tr>
            <td>« Campagne »</td>
            <td>L'ensemble des contenus publicitaires créés et financés par un Annonceur, soumis à modération, puis diffusés aux Abonnés correspondant aux critères de ciblage définis.</td>
          </tr>
          <tr>
            <td>« Contenu Publicitaire »</td>
            <td>Tout média (image, vidéo, audio, texte enrichi) diffusé dans le cadre d'une Campagne.</td>
          </tr>
          <tr>
            <td>« Compte Utilisateur »</td>
            <td>L'espace personnel de l'Utilisateur sur la Plateforme, protégé par des identifiants de connexion.</td>
          </tr>
          <tr>
            <td>« Créateur » ou « Creator »</td>
            <td>Un Abonné disposant d'une audience significative et partenaire officiel de distribution de la Plateforme.</td>
          </tr>
          <tr>
            <td>« Données à Caractère Personnel »</td>
            <td>Toute information relative à une personne physique identifiée ou identifiable, au sens de la Loi n&deg;2013-450.</td>
          </tr>
          <tr>
            <td>« Éditeur »</td>
            <td>La société OON CLICK, éditrice et exploitante de la Plateforme.</td>
          </tr>
          <tr>
            <td>« FCFA » ou « XOF »</td>
            <td>Le Franc de la Communauté Financière Africaine, monnaie légale dans la zone UEMOA.</td>
          </tr>
          <tr>
            <td>« KYC » (Know Your Customer)</td>
            <td>Le processus de vérification de l'identité de l'Utilisateur, progressif selon le niveau de transactions effectuées.</td>
          </tr>
          <tr>
            <td>« Mobile Money »</td>
            <td>Les services de transfert d'argent électronique opérés par les opérateurs de téléphonie mobile (Orange Money, MTN MoMo, Moov Money).</td>
          </tr>
          <tr>
            <td>« OON Trust Score »</td>
            <td>Le score de confiance (0-100) attribué à chaque Abonné en fonction de son comportement sur la Plateforme.</td>
          </tr>
          <tr>
            <td>« Plateforme »</td>
            <td>L'application mobile oon.click et tout site web associé.</td>
          </tr>
          <tr>
            <td>« Portefeuille » ou « Wallet »</td>
            <td>Le solde virtuel de l'Utilisateur sur la Plateforme, exprimé en FCFA.</td>
          </tr>
          <tr>
            <td>« Utilisateur »</td>
            <td>Toute personne accédant à la Plateforme, qu'elle soit Abonné, Annonceur, Créateur, ou Agence Publicitaire.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- ARTICLE 2 -->
    <section class="article" id="art2">
      <h2><span class="art-num">Article 2</span> Objet</h2>
      <p>Les présentes CGU ont pour objet de définir les conditions dans lesquelles l'Éditeur met à disposition la Plateforme et les services associés, ainsi que les droits et obligations respectifs des parties.</p>
      <p>La Plateforme permet notamment :</p>
      <ul>
        <li><strong>Aux Abonnés :</strong> de visionner des Contenus Publicitaires, de percevoir une rémunération en FCFA pour chaque contenu entièrement consommé, de cumuler des gains dans leur Portefeuille et de procéder à des retraits via Mobile Money ou carte VISA ;</li>
        <li><strong>Aux Annonceurs :</strong> de créer des Campagnes publicitaires, de définir des critères de ciblage (démographiques, géographiques, socio-professionnels, centres d'intérêt), de financer la diffusion et d'accéder à des rapports analytiques détaillés ;</li>
        <li><strong>Aux Créateurs :</strong> de distribuer des contenus publicitaires au sein de leur audience en qualité de partenaires officiels ;</li>
        <li><strong>Aux Agences Publicitaires :</strong> de gérer plusieurs comptes Annonceurs depuis un tableau de bord unifié.</li>
      </ul>
    </section>

    <!-- ARTICLE 3 -->
    <section class="article" id="art3">
      <h2><span class="art-num">Article 3</span> Conditions d'inscription et d'accès</h2>

      <h3>3.1 Conditions générales d'éligibilité</h3>
      <p>L'inscription sur la Plateforme est ouverte à toute personne physique âgée d'au moins dix-huit (18) ans, jouissant de la pleine capacité juridique, et résidant dans un pays où le service est disponible. Les personnes morales (Annonceurs, Agences) doivent être régulièrement constituées conformément à la législation applicable.</p>
      <p>L'inscription implique la communication d'informations exactes, complètes et à jour. Toute inscription fondée sur de fausses informations pourra entraîner la suspension ou la clôture immédiate du Compte Utilisateur, sans préjudice des poursuites judiciaires éventuelles.</p>

      <h3>3.2 Processus d'inscription</h3>
      <p>L'inscription s'effectue par téléphone (avec vérification OTP par SMS), par adresse e-mail (avec vérification OTP) ou via l'authentification Google (OAuth). L'Utilisateur choisit son rôle (Abonné ou Annonceur) lors de l'inscription.</p>
      <p>Pour les Abonnés, le complètement du profil (identité, localisation, centres d'intérêt) est requis pour accéder à l'intégralité des fonctionnalités et percevoir la prime d'inscription de 500 FCFA.</p>

      <h3>3.3 Vérification d'identité (KYC)</h3>
      <p>La Plateforme met en oeuvre un processus progressif de vérification d'identité :</p>
      <ul class="kyc-list">
        <li><span class="kyc-badge">Niveau 0</span> Aucune vérification &mdash; accès limité</li>
        <li><span class="kyc-badge">Niveau 1</span> Vérification du numéro de téléphone par OTP &mdash; retraits autorisés jusqu'à 10 000 FCFA</li>
        <li><span class="kyc-badge">Niveau 2</span> Vérification d'identité par document officiel (CNI, passeport) &mdash; retraits supérieurs à 10 000 FCFA</li>
        <li><span class="kyc-badge">Niveau 3</span> Vérification complète (réservée aux Annonceurs &mdash; vérification du registre de commerce, RCCM, NIF)</li>
      </ul>
      <p>L'Utilisateur autorise expressément l'Éditeur à procéder à ces vérifications et à conserver les documents y afférents conformément à la législation en vigueur.</p>

      <h3>3.4 Unicité des comptes</h3>
      <p>Chaque Utilisateur ne peut détenir qu'un seul Compte Utilisateur. La création de comptes multiples est strictement interdite et sera détectée par les mécanismes d'empreinte numérique de l'appareil (device fingerprinting). Toute infraction entraînera la suspension immédiate de tous les comptes concernés et la confiscation des soldes y afférents.</p>
    </section>

    <!-- ARTICLE 4 -->
    <section class="article" id="art4">
      <h2><span class="art-num">Article 4</span> Fonctionnement de la Plateforme pour les Abonnés</h2>

      <h3>4.1 Visionnage des Contenus Publicitaires</h3>
      <p>L'Abonné reçoit dans son fil d'actualité (feed) des Contenus Publicitaires correspondant à son profil. Le crédit de la rémunération n'intervient qu'après consommation complète du contenu :</p>
      <ul>
        <li><strong>Vidéo/Audio :</strong> visionnage ou écoute intégrale (30 secondes maximum) ;</li>
        <li><strong>Image :</strong> affichage pendant au moins 5 secondes ;</li>
        <li><strong>Texte enrichi :</strong> défilement complet jusqu'à la fin avec temps de lecture respecté.</li>
      </ul>
      <p>Chaque Abonné ne peut visionner chaque Campagne qu'une seule fois (déduplication stricte). Le montant crédité par visionnage est configuré par l'Éditeur et peut varier.</p>

      <h3>4.2 Portefeuille et retraits</h3>
      <p>Les gains accumulés sont visibles en temps réel dans le Portefeuille de l'Abonné. Le retrait est soumis aux conditions suivantes :</p>
      <ul>
        <li>Solde minimum requis : <strong>5 000 FCFA</strong> ;</li>
        <li>Méthodes disponibles : Orange Money, MTN MoMo, Moov Money, carte VISA ;</li>
        <li>Délai de traitement : 24 heures maximum (objectif : 4 heures) ;</li>
        <li>Vérification KYC requise selon le montant (cf. Article 3.3).</li>
      </ul>

      <h3>4.3 Système de gamification</h3>
      <p>La Plateforme intègre un système de gamification comprenant des niveaux (Explorer à Elite), des badges, des défis quotidiens, des missions hebdomadaires, des classements régionaux et des jeux (Scratch &amp; Win, Roue de la Fortune). Ces mécanismes sont de nature incitative et ne créent aucun droit acquis pour l'Abonné. L'Éditeur se réserve le droit de modifier, suspendre ou supprimer tout élément de gamification à tout moment.</p>
    </section>

    <!-- ARTICLE 5 -->
    <section class="article" id="art5">
      <h2><span class="art-num">Article 5</span> Fonctionnement de la Plateforme pour les Annonceurs</h2>

      <h3>5.1 Création et diffusion de Campagnes</h3>
      <p>L'Annonceur peut créer des Campagnes en définissant le contenu média, les critères de ciblage et le budget. Chaque Campagne est soumise à une modération préalable (délai maximum de 2 heures) avant diffusion. L'Éditeur se réserve le droit de refuser toute Campagne non conforme à la législation en vigueur, notamment la Loi n&deg;2020-522, ou aux standards de qualité de la Plateforme.</p>

      <h3>5.2 Obligations des Annonceurs</h3>
      <p>L'Annonceur garantit et déclare que :</p>
      <ul>
        <li>Les contenus publicitaires diffusés sont licites, véridiques, non trompeurs et conformes à l'ensemble des dispositions législatives et réglementaires applicables ;</li>
        <li>Il dispose de l'ensemble des droits de propriété intellectuelle nécessaires sur les contenus publiés ;</li>
        <li>Les produits ou services promus ne sont ni contrefaits, ni prohibés, ni contraires aux bonnes moeurs ;</li>
        <li>Les contenus respectent les droits des tiers, notamment le droit à l'image, le droit à la vie privée et les droits de propriété intellectuelle ;</li>
        <li>Il assume l'entière responsabilité du contenu de ses Campagnes, conformément à l'article 30 de la Loi n&deg;2020-522.</li>
      </ul>

      <h3>5.3 Tarification et paiement</h3>
      <p>Le coût d'une Campagne est calculé en temps réel en fonction du nombre d'Abonnés ciblés et du format publicitaire choisi. Le budget minimum est de <strong>5 000 FCFA</strong> (50 abonnés ciblés). Le paiement s'effectue via les moyens de paiement intégrés (Mobile Money, carte VISA/Mastercard) avant la diffusion de la Campagne. Les fonds sont placés sous séquestre (escrow) pendant la durée de la Campagne.</p>
    </section>

    <!-- ARTICLE 6 -->
    <section class="article" id="art6">
      <h2><span class="art-num">Article 6</span> Obligations de l'Utilisateur</h2>

      <h3>6.1 Obligations générales</h3>
      <p>L'Utilisateur s'engage à :</p>
      <ul>
        <li>Fournir des informations exactes, complètes et à jour lors de l'inscription et tout au long de l'utilisation de la Plateforme ;</li>
        <li>Préserver la confidentialité de ses identifiants de connexion et être seul responsable de toute activité effectuée depuis son Compte Utilisateur ;</li>
        <li>Utiliser la Plateforme conformément aux présentes CGU, aux lois et règlements applicables, et aux bonnes moeurs ;</li>
        <li>Ne pas utiliser la Plateforme à des fins frauduleuses, illicites ou portant atteinte aux droits de tiers ;</li>
        <li>Ne pas tenter de contourner les mécanismes de sécurité, de vérification ou de déduplication de la Plateforme ;</li>
        <li>Ne pas utiliser de robots, scripts automatisés, VPN, proxys ou tout dispositif visant à fausser les statistiques de visionnage ;</li>
        <li>Respecter les droits de propriété intellectuelle de l'Éditeur, des Annonceurs et des tiers.</li>
      </ul>

      <h3>6.2 Comportements interdits</h3>
      <div class="warn-box">
        <strong>Sont expressément interdits et passibles de sanctions (suspension, clôture de compte, confiscation des gains, poursuites judiciaires) :</strong>
        <ul>
          <li>La création de comptes multiples ;</li>
          <li>La manipulation du système de visionnage (visionnages automatiques, farming) ;</li>
          <li>L'utilisation de VPN ou de proxys pour dissimuler sa localisation réelle ;</li>
          <li>La falsification de documents KYC ;</li>
          <li>Le blanchiment d'argent ou le financement d'activités illicites ;</li>
          <li>La diffusion de contenus haineux, discriminatoires, violents, pornographiques ou incitant à la haine ;</li>
          <li>Le harcèlement, l'usurpation d'identité ou l'atteinte à la vie privée d'autrui ;</li>
          <li>Toute tentative d'intrusion, de déstabilisation ou de piratage du système informatique de la Plateforme.</li>
        </ul>
      </div>
    </section>

    <!-- ARTICLE 7 -->
    <section class="article" id="art7">
      <h2><span class="art-num">Article 7</span> Système de confiance et lutte anti-fraude</h2>
      <p>La Plateforme met en oeuvre un système de scoring de confiance (OON Trust Score) fondé sur le comportement de l'Abonné. Ce score, compris entre 0 et 100, est recalculé périodiquement et prend en compte les événements positifs (visionnages réguliers, profil complet, absence de fraude) et négatifs (comportements suspects, signalements, tentatives de fraude).</p>
      <div class="info-box">Un score inférieur à 20 entraîne automatiquement la suspension de l'accès aux fonctionnalités de la Plateforme.</div>
      <p>L'Éditeur utilise également des technologies d'empreinte numérique de l'appareil, de détection de VPN, d'analyse comportementale et d'intelligence artificielle pour détecter et prévenir les fraudes. L'Utilisateur reconnaît et accepte que ces mécanismes anti-fraude sont essentiels au bon fonctionnement de la Plateforme et consent expressément à leur mise en oeuvre.</p>
    </section>

    <!-- ARTICLE 8 -->
    <section class="article" id="art8">
      <h2><span class="art-num">Article 8</span> Propriété intellectuelle</h2>
      <p>L'ensemble des éléments composant la Plateforme (logiciel, interface, design, algorithmes, bases de données, marques, logos, noms de domaine, contenus éditoriaux) est la propriété exclusive de l'Éditeur ou de ses concédants de licence, et est protégé par le droit ivoirien et les conventions internationales relatives à la propriété intellectuelle.</p>
      <p>L'Utilisateur s'interdit de reproduire, représenter, modifier, adapter, distribuer, décompiler, désassembler ou procéder à l'ingénierie inverse de tout ou partie de la Plateforme, sauf autorisation préalable et écrite de l'Éditeur.</p>
      <p>Les Contenus Publicitaires diffusés sur la Plateforme demeurent la propriété des Annonceurs respectifs. L'Annonceur accorde à l'Éditeur une licence non exclusive, mondiale et pour la durée de la Campagne, de reproduction et de communication au public de ses contenus aux fins de diffusion sur la Plateforme.</p>
    </section>

    <!-- ARTICLE 9 -->
    <section class="article" id="art9">
      <h2><span class="art-num">Article 9</span> Responsabilité et limitation de responsabilité</h2>

      <h3>9.1 Rôle d'intermédiaire technique</h3>
      <p>L'Éditeur agit en qualité d'intermédiaire technique et d'hébergeur au sens de la Loi n&deg;2013-451 relative à la lutte contre la cybercriminalité. Conformément à l'article 47 de ladite loi, l'Éditeur n'est pas responsable des contenus stockés ou transités par la Plateforme à la demande des Utilisateurs, dès lors qu'il n'avait pas connaissance de leur caractère illicite ou qu'il a agi promptement pour les retirer.</p>

      <h3>9.2 Exclusion de responsabilité</h3>
      <p>Dans les limites autorisées par la loi, l'Éditeur décline toute responsabilité en cas de :</p>
      <ul>
        <li>Préjudice résultant des contenus publicitaires diffusés par les Annonceurs ;</li>
        <li>Préjudice résultant de l'utilisation frauduleuse de la Plateforme par un Utilisateur ;</li>
        <li>Préjudice lié à l'achat de produits ou services promus dans les Campagnes ;</li>
        <li>Dysfonctionnements techniques, interruptions de service, erreurs ou omissions ;</li>
        <li>Pertes de données, pertes de gains ou tout préjudice indirect, accessoire ou consécutif.</li>
      </ul>

      <h3>9.3 Garantie d'indemnisation</h3>
      <p>L'Utilisateur s'engage à garantir, défendre et indemniser l'Éditeur, ses dirigeants, employés, agents et partenaires contre toute réclamation, demande, perte, dommage, coût ou dépense résultant de la violation des présentes CGU, de l'utilisation frauduleuse ou illégale de la Plateforme, ou des Contenus Publicitaires diffusés par l'Annonceur.</p>

      <h3>9.4 Plafonnement de la responsabilité</h3>
      <p>En tout état de cause, la responsabilité totale de l'Éditeur ne saurait excéder le montant total des sommes effectivement perçues par l'Utilisateur concerné au cours des douze (12) mois précédant le fait générateur du dommage, ou à défaut, la somme de cent mille (100 000) FCFA.</p>
    </section>

    <!-- ARTICLE 10 -->
    <section class="article" id="art10">
      <h2><span class="art-num">Article 10</span> Données à caractère personnel</h2>
      <p>Le traitement des Données à Caractère Personnel collectées sur la Plateforme est régi par la <a href="{{ route('legal.privacy') }}" style="color:var(--sky2);font-weight:700;">Politique de Confidentialité</a>, document distinct et complémentaire aux présentes CGU.</p>
      <p>En acceptant les présentes CGU, l'Utilisateur reconnaît avoir également pris connaissance et accepté la Politique de Confidentialité de la Plateforme, qui détaille notamment la nature des données collectées, les finalités du traitement, les destinataires, les durées de conservation et les droits de l'Utilisateur en application de la Loi n&deg;2013-450.</p>
      <p>L'Utilisateur consent expressément à ce que ses données de profil (démographiques, géographiques, socio-professionnelles et centres d'intérêt) soient utilisées à des fins de ciblage publicitaire et mises à disposition des Annonceurs sous forme agréée et anonymisée. Aucune donnée nominative n'est transmise directement aux Annonceurs sans le consentement explicite et spécifique de l'Abonné.</p>
    </section>

    <!-- ARTICLE 11 -->
    <section class="article" id="art11">
      <h2><span class="art-num">Article 11</span> Conformité à la Loi sur la communication publicitaire</h2>
      <p>La Plateforme se conforme aux dispositions de la Loi n&deg;2020-522 du 16 juin 2020 portant régime juridique de la communication publicitaire en Côte d'Ivoire. À ce titre :</p>
      <ul>
        <li>L'Éditeur procède à une modération systématique de tout Contenu Publicitaire avant diffusion, incluant une vérification automatique (IA de modération de contenu) et une validation manuelle par un modérateur ;</li>
        <li>L'Annonceur est informé de son obligation de conformité à la charte publicitaire et aux dispositions sectorielles applicables ;</li>
        <li>Les Contenus Publicitaires faisant la promotion de produits ou services réglementés (alcool, tabac, jeux, santé, alimentation) sont soumis à des restrictions supplémentaires ;</li>
        <li>L'Éditeur se réserve le droit de signaler à l'Autorité de la Communication Publicitaire (ACP) tout contenu suspecté de contrevenir à la loi.</li>
      </ul>
      <p>L'Annonceur est seul responsable des infractions commises au titre de ses Campagnes, conformément à l'article 30 de la Loi n&deg;2020-522.</p>
    </section>

    <!-- ARTICLE 12 -->
    <section class="article" id="art12">
      <h2><span class="art-num">Article 12</span> Suspension et Résiliation</h2>

      <h3>12.1 Suspension par l'Éditeur</h3>
      <p>L'Éditeur se réserve le droit de suspendre ou de clôturer, de manière temporaire ou définitive, le Compte Utilisateur de tout Utilisateur, sans préavis ni indemnité, en cas de :</p>
      <ul>
        <li>Violation des présentes CGU ;</li>
        <li>Activité frauduleuse avérée ou suspectée ;</li>
        <li>Score de confiance inférieur au seuil défini ;</li>
        <li>Non-respect des obligations KYC ;</li>
        <li>Inactivité prolongée (12 mois sans connexion) ;</li>
        <li>Demande légitime des autorités judiciaires ou administratives.</li>
      </ul>

      <h3>12.2 Résiliation par l'Utilisateur</h3>
      <p>L'Utilisateur peut à tout moment demander la clôture de son Compte Utilisateur. En cas de clôture volontaire, le solde du Portefeuille sera versé à l'Utilisateur sous réserve du respect du seuil minimum de retrait et de la vérification KYC applicable. Les soldes inférieurs au seuil de retrait ne seront pas remboursés.</p>

      <h3>12.3 Effets de la résiliation</h3>
      <p>La résiliation entraîne la désactivation du Compte Utilisateur et la suppression des données personnelles conformément à la Politique de Confidentialité, sous réserve des obligations légales de conservation. Les gains non retirés en dessous du seuil minimum seront définitivement perdus.</p>
    </section>

    <!-- ARTICLE 13 -->
    <section class="article" id="art13">
      <h2><span class="art-num">Article 13</span> Modification des CGU</h2>
      <p>L'Éditeur se réserve le droit de modifier les présentes CGU à tout moment. Les modifications seront notifiées aux Utilisateurs par notification push, e-mail ou affichage sur la Plateforme, au moins quinze (15) jours avant leur entrée en vigueur.</p>
      <p>La poursuite de l'utilisation de la Plateforme après l'entrée en vigueur des CGU modifiées vaut acceptation tacite desdites modifications. En cas de désaccord, l'Utilisateur devra cesser d'utiliser la Plateforme et demander la clôture de son Compte Utilisateur.</p>
    </section>

    <!-- ARTICLE 14 -->
    <section class="article" id="art14">
      <h2><span class="art-num">Article 14</span> Règlement des différends</h2>

      <h3>14.1 Règlement amiable</h3>
      <p>En cas de différend relatif à l'interprétation, l'exécution ou la résiliation des présentes CGU, les parties s'engagent à rechercher une solution amiable préalablement à toute action judiciaire. L'Utilisateur peut saisir le service client de la Plateforme par e-mail. L'Éditeur s'engage à répondre dans un délai de quinze (15) jours ouvrés.</p>

      <h3>14.2 Médiation</h3>
      <p>À défaut de résolution amiable dans un délai de trente (30) jours, les parties pourront recourir à une procédure de médiation auprès d'un médiateur agréé en Côte d'Ivoire.</p>

      <h3>14.3 Juridiction compétente</h3>
      <p>À défaut de résolution amiable ou de médiation, tout litige relatif aux présentes CGU sera soumis à la compétence exclusive des tribunaux d'Abidjan, Côte d'Ivoire, quels que soient le lieu d'exécution ou le domicile du défendeur.</p>
    </section>

    <!-- ARTICLE 15 -->
    <section class="article" id="art15">
      <h2><span class="art-num">Article 15</span> Droit applicable</h2>
      <p>Les présentes CGU sont régies et interprétées conformément au droit de la République de Côte d'Ivoire. Les dispositions législatives et réglementaires ivoiriennes, ainsi que les normes communautaires de l'UEMOA et de la CEDEAO, s'appliquent de manière complémentaire.</p>
    </section>

    <!-- ARTICLE 16 -->
    <section class="article" id="art16">
      <h2><span class="art-num">Article 16</span> Force majeure</h2>
      <p>L'Éditeur ne saurait être tenu responsable de l'inexécution totale ou partielle de ses obligations au titre des présentes, si cette inexécution est imputable à un événement de force majeure tel que défini par la législation ivoirienne, notamment : catastrophe naturelle, guerre, épidémie, grève générale, défaillance des réseaux de télécommunication, coupure d'électricité généralisée, décision gouvernementale ou réglementaire affectant le fonctionnement du service.</p>
    </section>

    <!-- ARTICLE 17 -->
    <section class="article" id="art17">
      <h2><span class="art-num">Article 17</span> Divisibilité</h2>
      <p>Si l'une quelconque des stipulations des présentes CGU est déclarée nulle ou inapplicable par une décision de justice devenue définitive, les autres stipulations demeureront en vigueur et continueront de produire leurs effets.</p>
    </section>

    <!-- ARTICLE 18 -->
    <section class="article" id="art18">
      <h2><span class="art-num">Article 18</span> Intégralité</h2>
      <p>Les présentes CGU, la Politique de Confidentialité et les formulaires de consentement constituent l'intégralité de l'accord entre l'Utilisateur et l'Éditeur concernant l'utilisation de la Plateforme, et remplacent tout accord antérieur.</p>
    </section>

    <!-- ARTICLE 19 -->
    <section class="article" id="art19">
      <h2><span class="art-num">Article 19</span> Acceptation et consentement</h2>
      <div class="info-box">
        En cochant la case « J'accepte les Conditions Générales d'Utilisation et la Politique de Confidentialité » lors de l'inscription, l'Utilisateur manifeste son consentement libre, éclairé, spécifique et univoque aux présentes CGU, conformément à la Loi n&deg;2013-450 et à la Loi n&deg;2013-546.
      </div>
      <p>Ce consentement est horodaté et archivé par l'Éditeur et constitue une preuve électronique recevable devant les juridictions ivoiriennes, conformément à l'article 27 de la Loi n&deg;2013-546 relatif à la valeur probante de l'écrit électronique.</p>
      <p>L'Utilisateur reconnaît que l'Éditeur conserve un journal d'audit immuable enregistrant la date, l'heure, l'adresse IP, l'identifiant de l'appareil et la version des CGU acceptées, et que ces éléments font foi jusqu'à preuve du contraire en cas de litige.</p>
    </section>

    <!-- ARTICLE 20 -->
    <section class="article" id="art20">
      <h2><span class="art-num">Article 20</span> Contact</h2>
      <p>Pour toute question relative aux présentes CGU, l'Utilisateur peut contacter l'Éditeur :</p>
      <ul>
        <li>Par e-mail : <a href="mailto:support@oon.click" style="color:var(--sky2);font-weight:700;">support@oon.click</a></li>
        <li>Par courrier : OON CLICK, Abidjan, Côte d'Ivoire</li>
        <li>Via le service client intégré à la Plateforme</li>
      </ul>
    </section>

  </main>
</div>

<!-- FOOTER -->
<footer class="legal-footer">
  <p>
    &copy; {{ date('Y') }} oon.click &mdash; CGU Version 1.0 &mdash; 3 avril 2026 &mdash; République de Côte d'Ivoire
    &nbsp;|&nbsp;
    <a href="{{ route('legal.privacy') }}">Politique de Confidentialité</a>
    &nbsp;|&nbsp;
    <a href="{{ route('home') }}">Retour à l'accueil</a>
  </p>
</footer>

</body>
</html>
