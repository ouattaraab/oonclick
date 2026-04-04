<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Inscription Annonceur — oon.click</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --sky: #2AABF0;
      --sky2: #1A95D8;
      --sky3: #0E7AB8;
      --sky-pale: #EBF7FE;
      --sky-mid: #C5E8FA;
      --navy: #1B2A6E;
      --navy2: #162058;
      --border: #C8E4F6;
      --muted: #5A7098;
      --bg: #F0F8FF;
      --success: #16A34A;
      --danger: #EF4444;
    }

    html, body { min-height: 100%; }

    body {
      font-family: 'Nunito', sans-serif;
      background: linear-gradient(160deg, #D6EEFA 0%, #EAF4FF 55%, #F4F8FF 100%);
      min-height: 100vh;
      padding: 28px 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
      color: var(--navy);
    }

    /* ── Navbar ─────────────────────────────────────────────────────────────── */
    .navbar {
      width: 100%;
      max-width: 1040px;
      height: 62px;
      background: #fff;
      border-bottom: 1.5px solid var(--border);
      border-radius: 14px 14px 0 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 28px;
      flex-shrink: 0;
    }
    .navbar-logo {
      font-size: 22px; font-weight: 900; letter-spacing: -0.5px;
      line-height: 1; text-decoration: none;
    }
    .navbar-logo .oon { color: var(--sky); }
    .navbar-logo .dot { color: var(--navy); }
    .navbar-right { font-size: 13.5px; color: var(--muted); font-weight: 600; }
    .navbar-right a { color: var(--sky); text-decoration: none; font-weight: 700; }
    .navbar-right a:hover { color: var(--sky2); text-decoration: underline; }

    /* ── Progress bar ───────────────────────────────────────────────────────── */
    .progress-track {
      width: 100%; max-width: 1040px; height: 4px;
      background: var(--sky-mid); overflow: hidden; flex-shrink: 0;
    }
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--sky) 0%, var(--navy) 100%);
      border-radius: 0 4px 4px 0;
      transition: width 0.45s cubic-bezier(.4,0,.2,1);
      width: 25%;
    }

    /* ── Card ───────────────────────────────────────────────────────────────── */
    .card {
      width: 100%; max-width: 1040px; min-height: 600px;
      display: flex; border-radius: 0 0 18px 18px; overflow: hidden;
      box-shadow: 0 8px 48px rgba(27,42,110,.10), 0 2px 8px rgba(42,171,240,.06);
      flex: 1;
    }

    /* ── Left panel ─────────────────────────────────────────────────────────── */
    .left-panel {
      width: 300px; flex-shrink: 0;
      background: linear-gradient(175deg, var(--navy) 0%, var(--navy2) 100%);
      padding: 36px 28px 28px;
      display: flex; flex-direction: column; gap: 28px;
    }
    .left-logo { font-size: 26px; font-weight: 900; letter-spacing: -0.5px; line-height: 1; }
    .left-logo .oon { color: var(--sky); }
    .left-logo .dot { color: #fff; }
    .left-tagline { color: rgba(255,255,255,.70); font-size: 14px; font-weight: 600; line-height: 1.6; margin-top: -12px; }

    /* Steps list */
    .steps-list { list-style: none; display: flex; flex-direction: column; gap: 0; flex: 1; }
    .step-item { display: flex; align-items: flex-start; gap: 14px; padding: 12px 0; position: relative; }
    .step-item:not(:last-child)::after {
      content: ''; position: absolute; left: 7px; top: 30px;
      width: 2px; bottom: -12px; background: rgba(255,255,255,.12);
    }
    .step-item.active:not(:last-child)::after { background: rgba(42,171,240,.30); }
    .step-item.done:not(:last-child)::after   { background: rgba(42,171,240,.40); }

    .step-dot {
      width: 16px; height: 16px; border-radius: 50%; flex-shrink: 0;
      margin-top: 2px; display: flex; align-items: center; justify-content: center;
      font-size: 9px; font-weight: 900; color: var(--navy);
    }
    .step-item.active .step-dot {
      background: #fff; box-shadow: 0 0 0 3px rgba(255,255,255,.22);
    }
    .step-item.done .step-dot {
      background: var(--sky); box-shadow: 0 0 0 3px rgba(42,171,240,.28);
    }
    .step-item.todo .step-dot {
      background: rgba(255,255,255,.18); border: 2px solid rgba(255,255,255,.22);
    }

    .step-text { display: flex; flex-direction: column; gap: 2px; }
    .step-name { font-size: 13px; font-weight: 800; }
    .step-item.active .step-name { color: #fff; }
    .step-item.done .step-name   { color: rgba(255,255,255,.80); }
    .step-item.todo .step-name   { color: rgba(255,255,255,.42); }
    .step-desc { font-size: 11.5px; font-weight: 600; }
    .step-item.active .step-desc { color: rgba(255,255,255,.70); }
    .step-item.done .step-desc   { color: rgba(255,255,255,.40); }
    .step-item.todo .step-desc   { color: rgba(255,255,255,.28); }

    /* Guarantee box */
    .guarantee-box {
      background: rgba(42,171,240,.12); border: 1px solid rgba(42,171,240,.22);
      border-radius: 10px; padding: 14px 16px;
      font-size: 12px; font-weight: 700; color: rgba(255,255,255,.82); line-height: 1.8;
    }
    .guarantee-box span { color: var(--sky); margin-right: 4px; }

    /* ── Right form panel ───────────────────────────────────────────────────── */
    .form-panel {
      flex: 1; background: var(--bg); padding: 40px 44px;
      display: flex; flex-direction: column; gap: 24px; overflow-y: auto;
    }
    .form-header { display: flex; flex-direction: column; gap: 6px; }
    .step-label {
      font-size: 11px; font-weight: 800; letter-spacing: .08em;
      text-transform: uppercase; color: var(--sky);
    }
    .form-title { font-size: 22px; font-weight: 900; color: var(--navy); letter-spacing: -.3px; line-height: 1.2; }
    .form-subtitle { font-size: 13px; font-weight: 600; color: var(--muted); }

    /* ── Step panels ────────────────────────────────────────────────────────── */
    .step-panel { display: none; flex-direction: column; gap: 24px; }
    .step-panel.active { display: flex; }

    /* ── Form grid ──────────────────────────────────────────────────────────── */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 20px; }

    .field { display: flex; flex-direction: column; gap: 6px; }
    .field.full { grid-column: 1 / -1; }

    label {
      font-size: 11px; font-weight: 800; letter-spacing: .06em;
      text-transform: uppercase; color: var(--navy);
    }
    label .req { color: var(--sky); margin-left: 2px; }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="tel"],
    input[type="date"],
    input[type="url"],
    select {
      font-family: 'Nunito', sans-serif;
      font-size: 14px; font-weight: 600; color: var(--navy);
      background: #fff; border: 1.5px solid var(--border); border-radius: 10px;
      padding: 10px 14px; width: 100%; outline: none;
      transition: border-color .18s, box-shadow .18s;
      appearance: none; -webkit-appearance: none;
    }
    select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%235A7098' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 12px center;
      padding-right: 36px; cursor: pointer;
    }
    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus,
    input[type="tel"]:focus,
    input[type="date"]:focus,
    input[type="url"]:focus,
    select:focus {
      border-color: var(--sky); box-shadow: 0 0 0 3px rgba(42,171,240,.12);
    }
    input.has-error, select.has-error { border-color: var(--danger); }
    input::placeholder { color: #a8c2d8; font-weight: 600; }

    .field-hint { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: -2px; }
    .field-error { font-size: 12px; color: var(--danger); font-weight: 600; }

    /* Phone input */
    .phone-wrapper {
      display: flex; border: 1.5px solid var(--border); border-radius: 10px;
      overflow: hidden; background: #fff;
      transition: border-color .18s, box-shadow .18s;
    }
    .phone-wrapper:focus-within {
      border-color: var(--sky); box-shadow: 0 0 0 3px rgba(42,171,240,.12);
    }
    .phone-prefix {
      padding: 10px 12px; background: rgba(42,171,240,.08);
      border-right: 1.5px solid var(--border); font-size: 13px; font-weight: 800;
      color: var(--navy); display: flex; align-items: center; white-space: nowrap; flex-shrink: 0;
    }
    .phone-wrapper input { border: none; border-radius: 0; box-shadow: none; padding: 10px 14px; }
    .phone-wrapper input:focus { box-shadow: none; border-color: transparent; }

    /* Radio cards — advertiser type */
    .radio-cards {
      display: grid; grid-template-columns: 1fr 1fr; gap: 12px; grid-column: 1 / -1;
    }
    .radio-card { position: relative; }
    .radio-card input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
    .radio-card-label {
      display: flex; align-items: center; gap: 14px;
      border: 2px solid var(--border); border-radius: 12px;
      padding: 16px; cursor: pointer;
      background: #fff; transition: border-color .2s, box-shadow .2s;
    }
    .radio-card input[type="radio"]:checked + .radio-card-label {
      border-color: var(--sky); box-shadow: 0 0 0 3px rgba(42,171,240,.13);
    }
    .radio-card-icon {
      width: 40px; height: 40px; border-radius: 10px;
      background: var(--sky-pale); display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 18px;
    }
    .radio-card-info { display: flex; flex-direction: column; gap: 2px; }
    .radio-card-title { font-size: 13.5px; font-weight: 800; color: var(--navy); }
    .radio-card-desc  { font-size: 11.5px; font-weight: 600; color: var(--muted); }

    /* CGU row */
    .cgu-row { display: flex; align-items: flex-start; gap: 10px; grid-column: 1 / -1; }
    .cgu-row input[type="checkbox"] {
      width: 18px; height: 18px; accent-color: var(--sky);
      cursor: pointer; flex-shrink: 0; margin-top: 2px;
    }
    .cgu-text {
      font-size: 12px; font-weight: 600; color: var(--muted);
      line-height: 1.5; text-transform: none; letter-spacing: 0;
    }
    .cgu-text a { color: var(--sky2); text-decoration: none; font-weight: 700; }

    /* Checkboxes sectors */
    .checkbox-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; grid-column: 1 / -1; }
    .checkbox-item { display: flex; align-items: center; gap: 8px; }
    .checkbox-item input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--sky); cursor: pointer; flex-shrink: 0; }
    .checkbox-item label { font-size: 13px; font-weight: 600; text-transform: none; letter-spacing: 0; cursor: pointer; }

    /* Info banner */
    .info-banner {
      background: var(--sky-pale); border: 1px solid var(--sky-mid); border-radius: 10px;
      padding: 14px 16px; font-size: 13px; font-weight: 600; color: var(--navy);
      line-height: 1.5; grid-column: 1 / -1; display: flex; align-items: flex-start; gap: 10px;
    }
    .info-banner-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }

    /* Alert error */
    .alert-error {
      background: #FEF2F2; border: 1px solid #FECACA; border-radius: 10px;
      padding: 10px 14px; font-size: 13px; color: #B91C1C; font-weight: 600;
    }

    /* Divider */
    .divider {
      display: flex; align-items: center; gap: 14px;
    }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px; background: var(--border);
    }
    .divider span { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; }

    /* Form actions */
    .form-actions {
      display: flex; align-items: center; justify-content: space-between;
      padding-top: 8px; margin-top: auto; gap: 12px;
    }
    .form-actions-right { display: flex; align-items: center; gap: 16px; }

    .btn-back {
      font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 700;
      color: var(--muted); background: none; border: none; cursor: pointer;
      display: flex; align-items: center; gap: 6px; padding: 10px 0;
      transition: color .16s; text-decoration: none;
    }
    .btn-back:hover { color: var(--navy); }

    .btn-continue {
      font-family: 'Nunito', sans-serif; font-size: 14.5px; font-weight: 800;
      color: #fff;
      background: linear-gradient(135deg, var(--sky) 0%, var(--sky2) 60%, var(--sky3) 100%);
      border: none; border-radius: 12px; padding: 12px 28px; cursor: pointer;
      display: flex; align-items: center; gap: 8px;
      box-shadow: 0 4px 18px rgba(42,171,240,.32);
      transition: opacity .16s, transform .14s; letter-spacing: .01em;
    }
    .btn-continue:hover  { opacity: .92; transform: translateY(-1px); }
    .btn-continue:active { transform: translateY(0); opacity: 1; }

    .btn-skip {
      font-family: 'Nunito', sans-serif; font-size: 13px; font-weight: 700;
      color: var(--muted); background: none; border: none; cursor: pointer;
      padding: 8px 0; text-decoration: underline; text-underline-offset: 2px;
      transition: color .16s;
    }
    .btn-skip:hover { color: var(--navy); }

    /* Step 4 — success */
    .success-wrapper {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      text-align: center; gap: 20px; flex: 1; padding: 32px 0;
    }
    .success-icon {
      width: 80px; height: 80px; border-radius: 50%;
      background: linear-gradient(135deg, var(--sky) 0%, var(--navy) 100%);
      display: flex; align-items: center; justify-content: center; font-size: 36px;
      box-shadow: 0 8px 32px rgba(42,171,240,.35);
    }
    .success-title { font-size: 24px; font-weight: 900; color: var(--navy); letter-spacing: -.3px; }
    .success-sub   { font-size: 14px; font-weight: 600; color: var(--muted); max-width: 380px; line-height: 1.6; }
    .success-redirect { font-size: 13px; font-weight: 700; color: var(--sky); }
    .btn-dashboard {
      font-family: 'Nunito', sans-serif; font-size: 15px; font-weight: 800;
      color: #fff;
      background: linear-gradient(135deg, var(--sky) 0%, var(--sky2) 60%, var(--sky3) 100%);
      border: none; border-radius: 12px; padding: 14px 36px; cursor: pointer;
      box-shadow: 0 4px 18px rgba(42,171,240,.32);
      transition: opacity .16s, transform .14s; text-decoration: none; display: inline-block;
    }
    .btn-dashboard:hover { opacity: .92; transform: translateY(-1px); }

    /* Responsive ─────────────────────────────────────────────────────────────── */
    @media (max-width: 768px) {
      body { padding: 0; }
      .navbar { border-radius: 0; padding: 0 18px; }
      .progress-track { border-radius: 0; }
      .card { border-radius: 0; flex-direction: column; box-shadow: none; }
      .left-panel { width: 100%; padding: 20px; gap: 14px; }
      .steps-list { flex-direction: row; overflow-x: auto; gap: 6px; flex: unset; }
      .step-item { flex-direction: column; align-items: center; min-width: 72px; padding: 6px 4px; text-align: center; }
      .step-item::after { display: none !important; }
      .step-text { align-items: center; }
      .form-panel { padding: 24px 18px; }
      .form-grid { grid-template-columns: 1fr; }
      .field.full { grid-column: 1; }
      .radio-cards { grid-template-columns: 1fr; grid-column: 1; }
      .checkbox-grid { grid-template-columns: 1fr 1fr; grid-column: 1; }
      .form-actions { grid-column: 1; flex-wrap: wrap; }
      .cgu-row { grid-column: 1; }
      .info-banner { grid-column: 1; }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar">
    <a class="navbar-logo" href="{{ route('home') }}">
      <span class="oon">oon</span><span class="dot">.click</span>
    </a>
    <div class="navbar-right">
      Déjà inscrit ?&nbsp;<a href="{{ route('panel.login') }}">Se connecter</a>
    </div>
  </nav>

  <!-- Progress bar -->
  <div class="progress-track">
    <div class="progress-fill" id="progressFill"></div>
  </div>

  <!-- Main card -->
  <div class="card">

    <!-- Left panel -->
    <aside class="left-panel">
      <div>
        <div class="left-logo">
          <span class="oon">oon</span><span class="dot">.click</span>
        </div>
        <p class="left-tagline" style="margin-top:10px;">
          La plateforme de publicité ciblée qui rémunère vos audiences en FCFA
        </p>
      </div>

      <ul class="steps-list" id="sidebarSteps">
        <li class="step-item active" data-sidebar-step="1">
          <div class="step-dot"></div>
          <div class="step-text">
            <span class="step-name">Identifiants</span>
            <span class="step-desc">Informations de connexion</span>
          </div>
        </li>
        <li class="step-item todo" data-sidebar-step="2">
          <div class="step-dot"></div>
          <div class="step-text">
            <span class="step-name">Profil</span>
            <span class="step-desc">Informations complémentaires</span>
          </div>
        </li>
        <li class="step-item todo" data-sidebar-step="3">
          <div class="step-dot"></div>
          <div class="step-text">
            <span class="step-name">Campagne</span>
            <span class="step-desc">Votre première campagne</span>
          </div>
        </li>
        <li class="step-item todo" data-sidebar-step="4">
          <div class="step-dot"></div>
          <div class="step-text">
            <span class="step-name">Confirmation</span>
            <span class="step-desc">Compte créé avec succès</span>
          </div>
        </li>
      </ul>

      <div class="guarantee-box">
        <span>&#10003;</span>Aucun frais d'inscription<br />
        <span>&#10003;</span>Campagne live en 24h<br />
        <span>&#10003;</span>Support dédié annonceurs
      </div>
    </aside>

    <!-- Right form panel -->
    <section class="form-panel">

      @if($errors->any())
      <div class="alert-error">
        <strong>Erreur :</strong>
        <ul style="margin-top:4px;padding-left:16px;">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
      @endif

      <form method="POST" action="{{ route('register.advertiser.submit') }}" id="registrationForm" novalidate>
        @csrf

        {{-- ══════════════════════════════════════════════════════════════════
             ÉTAPE 1 — Identifiants
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="step-panel active" id="step1">

          <div class="form-header">
            <span class="step-label">Étape 1 sur 4</span>
            <h1 class="form-title">Créer votre compte annonceur</h1>
            <p class="form-subtitle">Renseignez vos identifiants pour démarrer</p>
          </div>

          <div class="form-grid">

            <!-- Nom complet -->
            <div class="field full">
              <label for="name">Nom complet <span class="req">*</span></label>
              <input
                type="text"
                id="name"
                name="name"
                placeholder="Ex : Kouame Bernard"
                value="{{ old('name') }}"
                autocomplete="name"
              />
              <div class="field-error" id="err-name"></div>
            </div>

            <!-- Email -->
            <div class="field">
              <label for="email">Email <span class="req">*</span></label>
              <input
                type="email"
                id="email"
                name="email"
                placeholder="contact@domaine.ci"
                value="{{ old('email') }}"
                autocomplete="email"
              />
              <div class="field-hint">Au moins un email ou téléphone est requis</div>
              <div class="field-error" id="err-email"></div>
            </div>

            <!-- Telephone -->
            <div class="field">
              <label for="phone">Téléphone</label>
              <div class="phone-wrapper">
                <span class="phone-prefix">&#127368; +225</span>
                <input
                  type="tel"
                  id="phone"
                  name="phone"
                  placeholder="07 01 23 45 67"
                  maxlength="10"
                  value="{{ old('phone') }}"
                  autocomplete="tel"
                />
              </div>
              <div class="field-error" id="err-phone"></div>
            </div>

            <!-- Mot de passe -->
            <div class="field">
              <label for="password">Mot de passe <span class="req">*</span></label>
              <input
                type="password"
                id="password"
                name="password"
                placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
                autocomplete="new-password"
              />
              <div class="field-hint">Minimum 6 caractères</div>
              <div class="field-error" id="err-password"></div>
            </div>

            <!-- Confirmation mot de passe -->
            <div class="field">
              <label for="password_confirmation">Confirmer le mot de passe <span class="req">*</span></label>
              <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
                autocomplete="new-password"
              />
              <div class="field-error" id="err-password-confirm"></div>
            </div>

            <!-- Type d'annonceur -->
            <div class="field full">
              <label>Type d'annonceur <span class="req">*</span></label>
              <div class="radio-cards">
                <div class="radio-card">
                  <input type="radio" id="type_individual" name="advertiser_type" value="individual"
                    {{ old('advertiser_type', 'individual') === 'individual' ? 'checked' : '' }} />
                  <label for="type_individual" class="radio-card-label">
                    <div class="radio-card-icon">&#128100;</div>
                    <div class="radio-card-info">
                      <span class="radio-card-title">Personne physique</span>
                      <span class="radio-card-desc">Entrepreneur individuel</span>
                    </div>
                  </label>
                </div>
                <div class="radio-card">
                  <input type="radio" id="type_company" name="advertiser_type" value="company"
                    {{ old('advertiser_type') === 'company' ? 'checked' : '' }} />
                  <label for="type_company" class="radio-card-label">
                    <div class="radio-card-icon">&#127970;</div>
                    <div class="radio-card-info">
                      <span class="radio-card-title">Société</span>
                      <span class="radio-card-desc">Entreprise enregistrée</span>
                    </div>
                  </label>
                </div>
              </div>
              <div class="field-error" id="err-type"></div>
            </div>

            <!-- Consentements obligatoires -->
            <div class="cgu-row">
              <input type="checkbox" name="consent_cgu" id="consent_cgu" />
              <label for="consent_cgu" class="cgu-text">
                J'ai lu et j'accepte les <a href="{{ route('legal.cgu') }}" target="_blank">Conditions Générales d'Utilisation</a>
                et la <a href="{{ route('legal.privacy') }}" target="_blank">Politique de Confidentialité</a> de oon.click.
                <span style="color:#EF4444;font-weight:800;margin-left:2px;">*</span>
              </label>
            </div>

            <div class="cgu-row">
              <input type="checkbox" name="consent_targeting" id="consent_targeting" />
              <label for="consent_targeting" class="cgu-text">
                J'accepte que mes données professionnelles soient utilisées pour configurer des campagnes publicitaires ciblées.
                <span style="color:#EF4444;font-weight:800;margin-left:2px;">*</span>
              </label>
            </div>

            <div class="cgu-row">
              <input type="checkbox" name="consent_transfer" id="consent_transfer" />
              <label for="consent_transfer" class="cgu-text">
                J'accepte que mes données soient transférées et traitées par des prestataires situés en dehors de la Côte d'Ivoire, dans le respect de garanties de sécurité adéquates.
                <span style="color:#EF4444;font-weight:800;margin-left:2px;">*</span>
              </label>
            </div>

            <div class="cgu-row">
              <input type="checkbox" name="consent_fingerprint" id="consent_fingerprint" />
              <label for="consent_fingerprint" class="cgu-text">
                J'accepte que oon.click collecte une empreinte numérique de mon appareil à des fins de sécurité et de prévention de la fraude.
                <span style="color:#EF4444;font-weight:800;margin-left:2px;">*</span>
              </label>
            </div>

            <div class="field-error" id="err-cgu" style="grid-column:1/-1;"></div>
            <div style="grid-column:1/-1;font-size:11px;font-weight:600;color:var(--muted);">
              <span style="color:#EF4444;font-weight:800;">*</span> Consentements obligatoires pour créer un compte annonceur
            </div>

          </div><!-- /form-grid -->

          <!-- Google button -->
          <div class="divider"><span>ou</span></div>
          <a href="{{ route('auth.google', ['role' => 'advertiser']) }}"
             style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:12px;border:1.5px solid var(--border);border-radius:12px;background:#fff;font-family:'Nunito',sans-serif;font-size:14px;font-weight:700;color:var(--navy);cursor:pointer;text-decoration:none;transition:background .2s;">
            <svg viewBox="0 0 24 24" width="20" height="20"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Continuer avec Google
          </a>

          <div class="form-actions" style="margin-top:auto;">
            <a href="{{ route('home') }}" class="btn-back">&#8592; Retour</a>
            <button type="button" class="btn-continue" onclick="goToStep(2)">
              Continuer &#8594;
            </button>
          </div>

        </div><!-- /step1 -->

        {{-- ══════════════════════════════════════════════════════════════════
             ÉTAPE 2 — Informations complémentaires
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="step-panel" id="step2">

          <div class="form-header">
            <span class="step-label" id="step2-label">Étape 2 sur 4</span>
            <h1 class="form-title" id="step2-title">Informations complémentaires</h1>
            <p class="form-subtitle" id="step2-subtitle">Complétez votre profil annonceur</p>
          </div>

          <!-- ── Personne physique ── -->
          <div id="fields-individual" class="form-grid">

            <div class="field">
              <label for="birth_date">Date de naissance</label>
              <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date') }}" />
            </div>

            <div class="field">
              <label for="city">Ville / Commune</label>
              <input type="text" id="city" name="city" placeholder="Ex : Abidjan, Cocody" value="{{ old('city') }}" />
            </div>

            <div class="field">
              <label for="id_type">Type de pièce d'identité</label>
              <select id="id_type">
                <option value="" disabled selected>Choisir…</option>
                <option value="cni">CNI (Carte Nationale d'Identité)</option>
                <option value="passport">Passeport</option>
              </select>
            </div>

            <div class="field">
              <label for="id_number">Numéro de la pièce</label>
              <input type="text" id="id_number" name="id_number" placeholder="Ex : CI-0123456789" value="{{ old('id_number') }}" />
            </div>

            <div class="field full">
              <label for="sector_ind">Secteur d'activité</label>
              <select id="sector_ind">
                <option value="" disabled selected>Choisir un secteur…</option>
                <option value="banque-finance">Banque &amp; Finance</option>
                <option value="telecom">Télécom</option>
                <option value="commerce-distribution">Commerce &amp; Distribution</option>
                <option value="sante">Santé</option>
                <option value="education">Éducation</option>
                <option value="transport">Transport</option>
                <option value="alimentation">Alimentation</option>
                <option value="immobilier">Immobilier</option>
                <option value="technologie">Technologie</option>
                <option value="autre">Autre</option>
              </select>
              <!-- hidden field to capture value for both individual and company paths -->
            </div>

            <div class="field full">
              <label for="ad_objective">Objectif publicitaire</label>
              <select id="ad_objective" name="ad_objective">
                <option value="" disabled selected>Choisir un objectif…</option>
                <option value="produit">Promouvoir un produit</option>
                <option value="service">Promouvoir un service</option>
                <option value="notoriete">Notoriété de marque</option>
                <option value="evenement">Événement</option>
                <option value="autre">Autre</option>
              </select>
            </div>

          </div><!-- /fields-individual -->

          <!-- ── Société ── -->
          <div id="fields-company" class="form-grid" style="display:none;">

            <div class="field full">
              <label for="company">Raison sociale <span class="req">*</span></label>
              <input type="text" id="company" name="company" placeholder="Ex : OrangeCi SA" value="{{ old('company') }}" />
              <div class="field-error" id="err-company"></div>
            </div>

            <div class="field full">
              <label for="sector">Secteur d'activité <span class="req">*</span></label>
              <select id="sector" name="sector">
                <option value="" disabled {{ old('sector') ? '' : 'selected' }}>Choisir un secteur…</option>
                <option value="banque-finance"        {{ old('sector') == 'banque-finance'        ? 'selected' : '' }}>Banque &amp; Finance</option>
                <option value="telecom"               {{ old('sector') == 'telecom'               ? 'selected' : '' }}>Télécom</option>
                <option value="commerce-distribution" {{ old('sector') == 'commerce-distribution' ? 'selected' : '' }}>Commerce &amp; Distribution</option>
                <option value="sante"                 {{ old('sector') == 'sante'                 ? 'selected' : '' }}>Santé</option>
                <option value="education"             {{ old('sector') == 'education'             ? 'selected' : '' }}>Éducation</option>
                <option value="transport"             {{ old('sector') == 'transport'             ? 'selected' : '' }}>Transport</option>
                <option value="alimentation"          {{ old('sector') == 'alimentation'          ? 'selected' : '' }}>Alimentation</option>
                <option value="immobilier"            {{ old('sector') == 'immobilier'            ? 'selected' : '' }}>Immobilier</option>
                <option value="technologie"           {{ old('sector') == 'technologie'           ? 'selected' : '' }}>Technologie</option>
                <option value="autre"                 {{ old('sector') == 'autre'                 ? 'selected' : '' }}>Autre</option>
              </select>
              <div class="field-error" id="err-sector"></div>
            </div>

            <div class="field">
              <label for="rccm">RCCM</label>
              <input type="text" id="rccm" name="rccm" placeholder="Ex : CI-ABJ-2024-B-12345" value="{{ old('rccm') }}" />
            </div>

            <div class="field">
              <label for="nif">NIF</label>
              <input type="text" id="nif" name="nif" placeholder="Ex : 123456789" value="{{ old('nif') }}" />
            </div>

            <div class="field">
              <label for="website">Site web <small style="font-weight:600;text-transform:none;letter-spacing:0;">(optionnel)</small></label>
              <input type="url" id="website" name="website" placeholder="https://monentreprise.ci" value="{{ old('website') }}" />
            </div>

            <div class="field">
              <label for="address">Adresse</label>
              <input type="text" id="address" name="address" placeholder="Ex : Plateau, Rue des Jardins" value="{{ old('address') }}" />
            </div>

            <div class="field">
              <label for="company_city">Ville</label>
              <input type="text" id="company_city" name="city" placeholder="Ex : Abidjan" value="{{ old('city') }}" />
            </div>

            <div class="field">
              <label for="company_size">Taille de l'entreprise</label>
              <select id="company_size" name="company_size">
                <option value="" disabled {{ old('company_size') ? '' : 'selected' }}>Choisir…</option>
                <option value="1-10"  {{ old('company_size') == '1-10'  ? 'selected' : '' }}>1 – 10 employés</option>
                <option value="11-50" {{ old('company_size') == '11-50' ? 'selected' : '' }}>11 – 50 employés</option>
                <option value="51-200"{{ old('company_size') == '51-200'? 'selected' : '' }}>51 – 200 employés</option>
                <option value="200+"  {{ old('company_size') == '200+'  ? 'selected' : '' }}>200+ employés</option>
              </select>
            </div>

          </div><!-- /fields-company -->

          <!-- Hidden field that syncs selected sector for individual path -->
          <input type="hidden" id="sector_hidden" name="sector" value="{{ old('sector') }}" />

          <div class="form-actions">
            <button type="button" class="btn-back" onclick="goToStep(1)">&#8592; Retour</button>
            <button type="button" class="btn-continue" onclick="goToStep(3)">
              Continuer &#8594;
            </button>
          </div>

        </div><!-- /step2 -->

        {{-- ══════════════════════════════════════════════════════════════════
             ÉTAPE 3 — Première campagne (optionnelle)
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="step-panel" id="step3">

          <div class="form-header">
            <span class="step-label">Étape 3 sur 4</span>
            <h1 class="form-title">Votre première campagne</h1>
            <p class="form-subtitle">Dites-nous en plus sur vos intentions publicitaires (optionnel)</p>
          </div>

          <div class="form-grid">

            <div class="field full">
              <label for="monthly_budget">Budget mensuel estimé</label>
              <select id="monthly_budget" name="monthly_budget">
                <option value="" {{ old('monthly_budget') ? '' : 'selected' }}>Je préfère ne pas répondre</option>
                <option value="moins-50000"       {{ old('monthly_budget') == 'moins-50000'       ? 'selected' : '' }}>Moins de 50 000 FCFA</option>
                <option value="50000-200000"      {{ old('monthly_budget') == '50000-200000'      ? 'selected' : '' }}>50 000 – 200 000 FCFA</option>
                <option value="200000-500000"     {{ old('monthly_budget') == '200000-500000'     ? 'selected' : '' }}>200 000 – 500 000 FCFA</option>
                <option value="plus-500000"       {{ old('monthly_budget') == 'plus-500000'       ? 'selected' : '' }}>Plus de 500 000 FCFA</option>
              </select>
            </div>

            <div class="field full">
              <label>Secteurs ciblés</label>
              <div class="checkbox-grid">
                @php
                  $sectors = ['finance' => 'Finance', 'telecom' => 'Télécom', 'commerce' => 'Commerce', 'sante' => 'Santé', 'education' => 'Éducation', 'tech' => 'Technologie', 'autre' => 'Autre'];
                  $oldSectors = old('target_sectors', []);
                @endphp
                @foreach($sectors as $val => $label)
                <div class="checkbox-item">
                  <input type="checkbox" id="ts_{{ $val }}" name="target_sectors[]" value="{{ $val }}"
                    {{ in_array($val, $oldSectors) ? 'checked' : '' }} />
                  <label for="ts_{{ $val }}">{{ $label }}</label>
                </div>
                @endforeach
              </div>
            </div>

            <div class="info-banner full">
              <span class="info-banner-icon">&#128161;</span>
              <span>Vous pourrez créer votre première campagne directement depuis votre tableau de bord après inscription. Ces informations nous aident uniquement à personnaliser votre expérience.</span>
            </div>

          </div><!-- /form-grid -->

          <div class="form-actions">
            <button type="button" class="btn-back" onclick="goToStep(2)">&#8592; Retour</button>
            <div class="form-actions-right">
              <button type="button" class="btn-skip" onclick="goToStep(4, true)">Passer cette étape</button>
              <button type="button" class="btn-continue" onclick="goToStep(4)">
                Créer mon compte &#8594;
              </button>
            </div>
          </div>

        </div><!-- /step3 -->

        {{-- ══════════════════════════════════════════════════════════════════
             ÉTAPE 4 — Confirmation (soumet le formulaire)
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="step-panel" id="step4">

          <div class="success-wrapper">
            <div class="success-icon">&#10003;</div>
            <h2 class="success-title">Compte créé avec succès !</h2>
            <p class="success-sub">Votre compte annonceur oon.click a été créé. Vous allez être redirigé vers votre tableau de bord dans quelques instants.</p>
            <p class="success-redirect" id="redirectCountdown">Redirection dans 3 secondes…</p>
            <a href="{{ route('panel.advertiser.dashboard') }}" class="btn-dashboard" id="dashboardBtn">
              Accéder à mon espace &#8594;
            </a>
          </div>

        </div><!-- /step4 -->

      </form>

    </section><!-- /form-panel -->

  </div><!-- /card -->

  <script>
  (function () {
    'use strict';

    /* ── State ──────────────────────────────────────────────────────────────── */
    let currentStep = 1;
    const TOTAL_STEPS = 4;

    /* ── Helpers ────────────────────────────────────────────────────────────── */
    function $(sel) { return document.querySelector(sel); }
    function $$(sel) { return document.querySelectorAll(sel); }

    function setError(id, msg) {
      const el = document.getElementById(id);
      if (el) el.textContent = msg || '';
    }

    function markField(inputId, hasError) {
      const el = document.getElementById(inputId);
      if (!el) return;
      el.classList.toggle('has-error', hasError);
    }

    function clearErrors() {
      $$('.field-error').forEach(function(el) { el.textContent = ''; });
      $$('.has-error').forEach(function(el) { el.classList.remove('has-error'); });
    }

    /* ── Sidebar update ─────────────────────────────────────────────────────── */
    function updateSidebar(step) {
      $$('[data-sidebar-step]').forEach(function(li) {
        const s = parseInt(li.dataset.sidebarStep, 10);
        li.classList.remove('active', 'done', 'todo');
        if (s < step) li.classList.add('done');
        else if (s === step) li.classList.add('active');
        else li.classList.add('todo');
      });
    }

    /* ── Progress bar ───────────────────────────────────────────────────────── */
    function updateProgress(step) {
      const pct = Math.round((step / TOTAL_STEPS) * 100);
      $('#progressFill').style.width = pct + '%';
    }

    /* ── Step 2 dynamic content ─────────────────────────────────────────────── */
    function syncStep2ToType() {
      const type = document.querySelector('input[name="advertiser_type"]:checked');
      const val  = type ? type.value : 'individual';

      const indFields  = $('#fields-individual');
      const compFields = $('#fields-company');
      const label      = $('#step2-label');
      const title      = $('#step2-title');
      const sub        = $('#step2-subtitle');

      if (val === 'company') {
        indFields.style.display  = 'none';
        compFields.style.display = 'grid';
        title.textContent = 'Informations de votre société';
        sub.textContent   = 'Renseignez les informations légales de votre entreprise';
      } else {
        indFields.style.display  = 'grid';
        compFields.style.display = 'none';
        title.textContent = 'Vos informations personnelles';
        sub.textContent   = 'Quelques détails pour compléter votre profil';
      }

      // Sync individual sector to hidden input
      const sectorInd = $('#sector_ind');
      const sectorHid = $('#sector_hidden');
      if (val === 'individual' && sectorInd) {
        sectorHid.disabled = false;
        // Disable the company sector field so it doesn't override
        const sectorComp = $('#sector');
        if (sectorComp) sectorComp.disabled = true;
      } else {
        sectorHid.disabled = true;
        const sectorComp = $('#sector');
        if (sectorComp) sectorComp.disabled = false;
      }
    }

    /* ── Validation ─────────────────────────────────────────────────────────── */
    function validateStep1() {
      clearErrors();
      let ok = true;

      const name  = $('#name').value.trim();
      const email = $('#email').value.trim();
      const phone = $('#phone').value.trim();
      const pass  = $('#password').value;
      const conf  = $('#password_confirmation').value;
      const type  = document.querySelector('input[name="advertiser_type"]:checked');
      const cguChecked         = $('#consent_cgu') ? $('#consent_cgu').checked : false;
      const targetingChecked   = $('#consent_targeting') ? $('#consent_targeting').checked : false;
      const transferChecked    = $('#consent_transfer') ? $('#consent_transfer').checked : false;
      const fingerprintChecked = $('#consent_fingerprint') ? $('#consent_fingerprint').checked : false;

      if (!name) {
        setError('err-name', 'Le nom complet est obligatoire.');
        markField('name', true); ok = false;
      }

      if (!email && !phone) {
        setError('err-email', 'Veuillez renseigner au moins un email ou un numéro de téléphone.');
        markField('email', true); ok = false;
      } else if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        setError('err-email', 'Adresse email invalide.');
        markField('email', true); ok = false;
      }

      if (!pass || pass.length < 6) {
        setError('err-password', 'Le mot de passe doit contenir au moins 6 caractères.');
        markField('password', true); ok = false;
      }

      if (pass !== conf) {
        setError('err-password-confirm', 'Les mots de passe ne correspondent pas.');
        markField('password_confirmation', true); ok = false;
      }

      if (!type) {
        setError('err-type', 'Veuillez choisir un type de compte annonceur.'); ok = false;
      }

      if (!cguChecked || !targetingChecked || !transferChecked || !fingerprintChecked) {
        setError('err-cgu', 'Vous devez accepter les quatre consentements obligatoires pour continuer.'); ok = false;
      }

      return ok;
    }

    function validateStep2() {
      clearErrors();
      const type = document.querySelector('input[name="advertiser_type"]:checked');
      const val  = type ? type.value : 'individual';

      if (val === 'company') {
        const company = $('#company').value.trim();
        const sector  = $('#sector').value;
        let ok = true;
        if (!company) {
          setError('err-company', 'La raison sociale est obligatoire.');
          markField('company', true); ok = false;
        }
        if (!sector) {
          setError('err-sector', "Le secteur d'activité est obligatoire.");
          markField('sector', true); ok = false;
        }
        return ok;
      }

      // Individual — no required fields on step 2
      return true;
    }

    /* ── Sync individual sector to hidden before submit ─────────────────────── */
    function syncIndividualSector() {
      const type = document.querySelector('input[name="advertiser_type"]:checked');
      if (type && type.value === 'individual') {
        const sectorInd = $('#sector_ind');
        $('#sector_hidden').value = sectorInd ? sectorInd.value : '';
      }
    }

    /* ── Navigation ─────────────────────────────────────────────────────────── */
    window.goToStep = function(target, skip) {
      // Validate before advancing
      if (target > currentStep) {
        if (currentStep === 1 && !validateStep1()) return;
        if (currentStep === 2 && !validateStep2()) return;
      }

      // Hide all
      $$('.step-panel').forEach(function(p) { p.classList.remove('active'); });

      // Show target
      const panel = document.getElementById('step' + target);
      if (panel) panel.classList.add('active');

      currentStep = target;
      updateSidebar(target);
      updateProgress(target);

      // Step 4 — show success screen, then submit the form
      if (target === 4) {
        syncIndividualSector();

        // If skipping step 3, clear budget/sectors
        if (skip) {
          $('#monthly_budget').value = '';
          $$('input[name="target_sectors[]"]').forEach(function(cb) { cb.checked = false; });
        }

        // Countdown and then submit
        var countdown = 3;
        var countEl   = $('#redirectCountdown');
        var interval  = setInterval(function() {
          countdown--;
          if (countEl) {
            countEl.textContent = countdown > 0
              ? 'Redirection dans ' + countdown + ' seconde' + (countdown > 1 ? 's' : '') + '…'
              : 'Redirection en cours…';
          }
          if (countdown <= 0) {
            clearInterval(interval);
            document.getElementById('registrationForm').submit();
          }
        }, 1000);
      }

      // Scroll to top of right panel
      $('.form-panel').scrollTo({ top: 0, behavior: 'smooth' });
    };

    /* ── Listen to advertiser type changes ──────────────────────────────────── */
    $$('input[name="advertiser_type"]').forEach(function(radio) {
      radio.addEventListener('change', syncStep2ToType);
    });

    /* ── Sync individual sector on change ───────────────────────────────────── */
    const sectorInd = $('#sector_ind');
    if (sectorInd) {
      sectorInd.addEventListener('change', function() {
        $('#sector_hidden').value = this.value;
      });
    }

    /* ── Init ───────────────────────────────────────────────────────────────── */
    syncStep2ToType();
    updateSidebar(1);
    updateProgress(1);

    // If server returned validation errors, show them on step 1
    @if($errors->any())
      // Let step 1 be visible (it already is by default)
    @endif

  }());
  </script>

</body>
</html>
