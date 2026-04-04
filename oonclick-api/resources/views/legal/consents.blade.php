<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestion des consentements — oon.click</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet" />
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
      --success:#16A34A;
      --danger: #DC2626;
    }
    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--navy);
      min-height: 100vh;
    }

    /* Header */
    .site-header {
      background: linear-gradient(135deg, var(--navy) 0%, var(--sky3) 100%);
      padding: 20px 48px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
    }
    .header-brand { font-size: 24px; font-weight: 900; text-decoration: none; }
    .header-brand .oon   { color: var(--sky); }
    .header-brand .click { color: rgba(255,255,255,0.9); }
    .header-nav { display: flex; gap: 16px; align-items: center; }
    .header-nav a {
      font-size: 13px; font-weight: 700; color: rgba(255,255,255,0.8);
      text-decoration: none; padding: 6px 14px; border-radius: 20px;
      border: 1px solid rgba(255,255,255,0.2);
    }
    .header-nav a:hover { background: rgba(255,255,255,0.12); color: white; }

    /* Page container */
    .page { max-width: 860px; margin: 0 auto; padding: 40px 24px 60px; }

    .page-title { font-size: 24px; font-weight: 900; margin-bottom: 6px; }
    .page-subtitle { font-size: 14px; font-weight: 600; color: var(--muted); margin-bottom: 32px; }

    /* Alert */
    .alert-success {
      background: #F0FDF4; border: 1px solid #BBF7D0; border-left: 4px solid var(--success);
      border-radius: 0 10px 10px 0; padding: 12px 18px;
      font-size: 14px; font-weight: 700; color: #14532D; margin-bottom: 24px;
    }

    /* Section card */
    .consent-section {
      background: white; border: 1px solid var(--border); border-radius: 16px;
      margin-bottom: 24px; overflow: hidden;
      box-shadow: 0 2px 10px rgba(27,42,110,0.04);
    }
    .consent-section-header {
      padding: 18px 24px;
      background: var(--pale);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 10px;
    }
    .consent-section-header h2 {
      font-size: 15px; font-weight: 800; color: var(--navy);
    }
    .section-badge {
      padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 800;
    }
    .badge-mandatory { background: #FEE2E2; color: #991B1B; }
    .badge-optional  { background: #D1FAE5; color: #065F46; }

    /* Individual consent row */
    .consent-row {
      padding: 18px 24px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: flex-start; gap: 16px;
    }
    .consent-row:last-child { border-bottom: none; }

    .consent-row-info { flex: 1; }
    .consent-row-title { font-size: 14px; font-weight: 800; color: var(--navy); margin-bottom: 4px; }
    .consent-row-desc { font-size: 13px; font-weight: 600; color: var(--muted); line-height: 1.5; }
    .consent-row-status { margin-top: 8px; font-size: 12px; font-weight: 700; }
    .status-granted { color: var(--success); }
    .status-revoked { color: var(--danger); }
    .status-unknown { color: var(--muted); }

    /* Toggle switch */
    .toggle-wrapper {
      display: flex; flex-direction: column; align-items: center; gap: 4px;
      flex-shrink: 0;
    }
    .toggle-label { font-size: 10px; font-weight: 700; color: var(--muted); }

    .toggle {
      position: relative; display: inline-block;
      width: 46px; height: 26px;
    }
    .toggle input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
      position: absolute; cursor: pointer;
      inset: 0;
      background: #CBD5E1; border-radius: 26px;
      transition: background 0.2s;
    }
    .toggle-slider::before {
      content: '';
      position: absolute;
      height: 20px; width: 20px;
      left: 3px; bottom: 3px;
      background: white; border-radius: 50%;
      transition: transform 0.2s;
      box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    }
    .toggle input:checked + .toggle-slider { background: var(--sky); }
    .toggle input:checked + .toggle-slider::before { transform: translateX(20px); }
    .toggle input:disabled + .toggle-slider { background: #E2E8F0; cursor: not-allowed; opacity: 0.6; }

    /* Locked consent info */
    .locked-note {
      font-size: 11px; font-weight: 600; color: var(--muted);
      background: #F8FAFC; border: 1px solid var(--border);
      border-radius: 8px; padding: 8px 12px; margin-top: 8px;
    }

    /* Form actions */
    .form-actions {
      display: flex; gap: 14px; justify-content: flex-end; margin-top: 28px;
      flex-wrap: wrap;
    }
    .btn-save {
      padding: 12px 28px; border-radius: 12px; border: none;
      background: linear-gradient(135deg, var(--sky), var(--sky3));
      color: white; font-family: 'Nunito', sans-serif;
      font-size: 14px; font-weight: 800; cursor: pointer;
      box-shadow: 0 4px 14px rgba(42,171,240,0.3);
      transition: opacity 0.2s;
    }
    .btn-save:hover { opacity: 0.9; }
    .btn-back {
      padding: 12px 24px; border-radius: 12px;
      border: 1.5px solid var(--border); background: white;
      color: var(--muted); font-family: 'Nunito', sans-serif;
      font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-back:hover { border-color: var(--sky); color: var(--sky2); }

    /* Legal note */
    .legal-note {
      background: var(--pale); border: 1px solid var(--border); border-radius: 12px;
      padding: 16px 20px; margin-top: 24px;
      font-size: 12.5px; font-weight: 600; color: var(--muted); line-height: 1.6;
    }
    .legal-note a { color: var(--sky2); font-weight: 700; text-decoration: none; }

    @media (max-width: 600px) {
      .site-header { padding: 16px 20px; }
      .page { padding: 24px 16px 48px; }
      .consent-row { flex-wrap: wrap; }
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
  <a class="header-brand" href="{{ route('home') }}">
    <span class="oon">oon</span><span class="click">.click</span>
  </a>
  <nav class="header-nav">
    <a href="{{ route('home') }}">&larr; Accueil</a>
    <a href="{{ route('legal.cgu') }}">CGU</a>
    <a href="{{ route('legal.privacy') }}">Confidentialité</a>
  </nav>
</header>

<div class="page">

  <h1 class="page-title">Gestion de mes consentements</h1>
  <p class="page-subtitle">Vous pouvez gérer vos préférences de consentement ici. Les consentements obligatoires (C1 à C4) ne peuvent pas être révoqués sans supprimer votre compte.</p>

  @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
  @endif

  <form method="POST" action="{{ route('legal.consents.update') }}">
    @csrf

    <!-- MANDATORY CONSENTS (C1-C4) -->
    <div class="consent-section">
      <div class="consent-section-header">
        <h2>Consentements obligatoires</h2>
        <span class="section-badge badge-mandatory">Obligatoires</span>
      </div>

      @php
        $mandatory = [
          'C1' => [
            'title' => 'Acceptation des CGU et Politique de Confidentialité',
            'desc'  => "J'ai lu et j'accepte les Conditions Générales d'Utilisation et la Politique de Confidentialité de oon.click. Je reconnais que mes données de profil seront utilisées pour le ciblage publicitaire en contrepartie d'une rémunération.",
          ],
          'C2' => [
            'title' => 'Ciblage publicitaire personnalisé',
            'desc'  => "J'accepte que mes données de profil (démographiques, localisation, centres d'intérêt) soient utilisées pour me proposer des publicités ciblées et pertinentes.",
          ],
          'C3' => [
            'title' => 'Transfert international de données',
            'desc'  => "J'accepte que mes données soient transférées et traitées par des prestataires situés en dehors de la Côte d'Ivoire, dans le respect de garanties de sécurité adéquates.",
          ],
          'C4' => [
            'title' => 'Empreinte numérique de l\'appareil',
            'desc'  => "J'accepte que oon.click collecte une empreinte numérique de mon appareil à des fins de sécurité et de prévention de la fraude.",
          ],
        ];
      @endphp

      @foreach($mandatory as $type => $info)
        @php $consent = $consents->get($type); @endphp
        <div class="consent-row">
          <div class="consent-row-info">
            <div class="consent-row-title">{{ $type }} &mdash; {{ $info['title'] }}</div>
            <div class="consent-row-desc">{{ $info['desc'] }}</div>
            <div class="consent-row-status">
              @if($consent && $consent->granted)
                <span class="status-granted">Accordé le {{ $consent->granted_at?->format('d/m/Y a H:i') ?? '—' }}</span>
              @elseif($consent && !$consent->granted)
                <span class="status-revoked">Révoqué le {{ $consent->revoked_at?->format('d/m/Y a H:i') ?? '—' }}</span>
              @else
                <span class="status-unknown">Non enregistré</span>
              @endif
            </div>
            <div class="locked-note">Ce consentement est obligatoire pour utiliser oon.click. Sa révocation entraîne la clôture du compte.</div>
          </div>
          <div class="toggle-wrapper">
            <label class="toggle">
              <input type="checkbox" disabled {{ ($consent && $consent->granted) ? 'checked' : '' }} />
              <span class="toggle-slider"></span>
            </label>
            <span class="toggle-label">{{ ($consent && $consent->granted) ? 'Actif' : 'Inactif' }}</span>
          </div>
        </div>
      @endforeach
    </div>

    <!-- OPTIONAL CONSENTS (C5, C6) -->
    <div class="consent-section">
      <div class="consent-section-header">
        <h2>Consentements optionnels</h2>
        <span class="section-badge badge-optional">Facultatifs</span>
      </div>

      @php
        $optional = [
          'C5' => [
            'title' => 'Notifications push, e-mails et SMS',
            'desc'  => "J'accepte de recevoir des notifications push, e-mails et/ou SMS de la part de oon.click concernant les nouvelles publicités disponibles, mon solde et les actualités du service.",
            'field' => 'consent_c5',
          ],
          'C6' => [
            'title' => 'Communications commerciales et promotionnelles',
            'desc'  => "J'accepte de recevoir des communications commerciales et promotionnelles de la part de oon.click et de ses partenaires.",
            'field' => 'consent_c6',
          ],
        ];
      @endphp

      @foreach($optional as $type => $info)
        @php $consent = $consents->get($type); $isGranted = $consent && $consent->granted; @endphp
        <div class="consent-row">
          <div class="consent-row-info">
            <div class="consent-row-title">{{ $type }} &mdash; {{ $info['title'] }}</div>
            <div class="consent-row-desc">{{ $info['desc'] }}</div>
            <div class="consent-row-status">
              @if($isGranted)
                <span class="status-granted">Accordé le {{ $consent->granted_at?->format('d/m/Y a H:i') ?? '—' }}</span>
              @elseif($consent && !$consent->granted)
                <span class="status-revoked">Révoqué le {{ $consent->revoked_at?->format('d/m/Y a H:i') ?? '—' }}</span>
              @else
                <span class="status-unknown">Jamais configuré</span>
              @endif
            </div>
          </div>
          <div class="toggle-wrapper">
            <label class="toggle">
              <input type="checkbox" name="{{ $info['field'] }}" value="1" {{ $isGranted ? 'checked' : '' }} />
              <span class="toggle-slider"></span>
            </label>
            <span class="toggle-label">{{ $isGranted ? 'Actif' : 'Inactif' }}</span>
          </div>
        </div>
      @endforeach
    </div>

    <div class="form-actions">
      <a href="{{ route('home') }}" class="btn-back">&larr; Retour</a>
      <button type="submit" class="btn-save">Enregistrer mes préférences</button>
    </div>
  </form>

  <div class="legal-note">
    Conformément à la <a href="{{ route('legal.privacy') }}">Politique de Confidentialité</a> de oon.click et à la Loi n&deg;2013-450 relative à la protection des données à caractère personnel, vous disposez du droit de retirer votre consentement à tout moment. Chaque modification est horodatée et archivée dans notre journal d'audit conformément à la Loi n&deg;2013-546. Pour exercer d'autres droits (accès, rectification, suppression), contactez notre DPO à<a href="mailto:dpo@oon.click">dpo@oon.click</a>.
  </div>

</div>

</body>
</html>
