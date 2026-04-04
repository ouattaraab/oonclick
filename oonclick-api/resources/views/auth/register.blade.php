<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>oon.click — Inscription Abonné</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --sky:      #2AABF0;
      --sky2:     #1A95D8;
      --sky3:     #0E7AB8;
      --skyPale:  #EBF7FE;
      --navy:     #1B2A6E;
      --navy2:    #162058;
      --border:   #C8E4F6;
      --muted:    #5A7098;
      --bg:       #F0F8FF;
      --success:  #22C55E;
      --danger:   #EF4444;
    }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      min-height: 100vh;
      display: flex;
    }

    /* LEFT PANEL */
    .left-panel {
      width: 38%;
      min-height: 100vh;
      background: linear-gradient(160deg, var(--navy) 0%, var(--sky3) 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 48px 40px;
      position: relative;
      overflow: hidden;
    }

    .left-panel::before {
      content: '';
      position: absolute;
      width: 280px; height: 280px;
      border-radius: 50%;
      border: 40px solid rgba(255,255,255,0.06);
      top: -80px; right: -80px;
    }
    .left-panel::after {
      content: '';
      position: absolute;
      width: 180px; height: 180px;
      border-radius: 50%;
      border: 28px solid rgba(255,255,255,0.05);
      bottom: 60px; left: -60px;
    }

    .brand {
      font-size: 32px;
      font-weight: 900;
      margin-bottom: 6px;
    }
    .brand-oon  { color: var(--sky); }
    .brand-dot  { color: rgba(255,255,255,0.7); }

    .tagline {
      font-size: 14px;
      font-weight: 700;
      color: rgba(255,255,255,0.7);
      letter-spacing: 0.5px;
      margin-bottom: 36px;
    }

    .quote-box {
      border: 1.5px solid rgba(255,255,255,0.18);
      background: rgba(255,255,255,0.07);
      border-radius: 14px;
      padding: 16px 18px;
      font-size: 14px;
      font-weight: 600;
      font-style: italic;
      color: rgba(255,255,255,0.85);
      line-height: 1.55;
      margin-bottom: 32px;
    }
    .quote-box cite {
      display: block;
      margin-top: 8px;
      font-style: normal;
      font-size: 12px;
      color: var(--sky);
      font-weight: 700;
    }

    .features-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .feature-item {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .feature-icon {
      width: 36px; height: 36px;
      border-radius: 10px;
      background: rgba(255,255,255,0.12);
      display: flex; align-items: center; justify-content: center;
      font-size: 17px;
      flex-shrink: 0;
    }
    .feature-text {
      font-size: 13px;
      font-weight: 700;
      color: rgba(255,255,255,0.88);
      line-height: 1.35;
    }
    .feature-text span {
      display: block;
      font-size: 11px;
      font-weight: 500;
      color: rgba(255,255,255,0.55);
      margin-top: 1px;
    }

    .left-footer {
      margin-top: 40px;
      font-size: 11px;
      color: rgba(255,255,255,0.4);
    }

    /* RIGHT PANEL */
    .right-panel {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 24px;
      background: #fff;
      overflow-y: auto;
    }

    .auth-box {
      width: 100%;
      max-width: 420px;
    }

    /* Form header */
    .form-title {
      font-size: 24px;
      font-weight: 900;
      color: var(--navy);
      margin-bottom: 4px;
    }
    .form-subtitle {
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      margin-bottom: 24px;
    }

    /* Input group */
    .input-group {
      margin-bottom: 16px;
    }
    .input-label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: var(--navy);
      margin-bottom: 7px;
    }
    .input-wrapper {
      display: flex;
      align-items: stretch;
      border: 1.5px solid var(--border);
      border-radius: 12px;
      background: var(--skyPale);
      overflow: hidden;
      transition: border-color 0.2s;
    }
    .input-wrapper:focus-within {
      border-color: var(--sky);
      background: #fff;
    }
    .input-wrapper.has-error {
      border-color: var(--danger);
    }
    .input-prefix {
      padding: 0 14px;
      background: rgba(42,171,240,0.12);
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 14px;
      font-weight: 800;
      color: var(--navy);
      border-right: 1.5px solid var(--border);
      flex-shrink: 0;
      white-space: nowrap;
    }
    .input-wrapper input,
    .input-wrapper-plain input {
      flex: 1;
      border: none;
      outline: none;
      background: transparent;
      padding: 12px 14px;
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
      font-weight: 700;
      color: var(--navy);
      width: 100%;
    }
    .input-wrapper-plain {
      display: flex;
      align-items: stretch;
      border: 1.5px solid var(--border);
      border-radius: 12px;
      background: var(--skyPale);
      overflow: hidden;
      transition: border-color 0.2s;
    }
    .input-wrapper-plain:focus-within {
      border-color: var(--sky);
      background: #fff;
    }
    .input-wrapper-plain.has-error {
      border-color: var(--danger);
    }
    .input-wrapper input::placeholder,
    .input-wrapper-plain input::placeholder {
      color: #9BB5CC;
      font-weight: 500;
    }

    /* CGU row */
    .cgu-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 20px;
    }
    .cgu-row input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: var(--sky);
      cursor: pointer;
      flex-shrink: 0;
      margin-top: 2px;
    }
    .cgu-text {
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      line-height: 1.5;
    }
    .cgu-text a { color: var(--sky2); text-decoration: none; font-weight: 700; }

    /* Submit button */
    .submit-btn {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--sky) 0%, var(--sky3) 100%);
      color: #fff;
      font-family: 'Nunito', sans-serif;
      font-size: 15px;
      font-weight: 900;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(42,171,240,0.35);
      transition: opacity 0.2s, transform 0.1s;
      margin-bottom: 14px;
    }
    .submit-btn:hover { opacity: 0.93; transform: translateY(-1px); }
    .submit-btn:active { transform: translateY(0); }

    .bottom-link {
      text-align: center;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      margin-top: 8px;
    }
    .bottom-link a { color: var(--sky2); font-weight: 700; text-decoration: none; }

    /* Separator */
    .separator { display: flex; align-items: center; gap: 14px; margin: 18px 0; }
    .separator::before, .separator::after { content: ''; flex: 1; height: 1px; background: var(--border); }
    .separator span { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; }

    /* Google button */
    .btn-google {
      width: 100%;
      padding: 12px;
      border: 1.5px solid var(--border);
      border-radius: 12px;
      background: #fff;
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
      font-weight: 700;
      color: var(--navy);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: background 0.2s, border-color 0.2s;
      text-decoration: none;
    }
    .btn-google:hover { background: var(--skyPale); border-color: var(--sky); }
    .btn-google svg { width: 20px; height: 20px; }

    /* Error */
    .alert-error {
      background: #FEF2F2;
      border: 1px solid #FECACA;
      border-radius: 10px;
      padding: 10px 14px;
      margin-bottom: 20px;
      font-size: 13px;
      color: #B91C1C;
      font-weight: 600;
    }
    .field-error {
      font-size: 12px;
      color: var(--danger);
      font-weight: 600;
      margin-top: 4px;
    }

    /* RESPONSIVE */
    @media (max-width: 860px) {
      body { flex-direction: column; }
      .left-panel {
        width: 100%;
        min-height: auto;
        padding: 32px 24px;
      }
      .left-panel::before, .left-panel::after { display: none; }
      .features-list { display: none; }
      .left-footer { display: none; }
      .quote-box { display: none; }
      .right-panel { padding: 28px 20px; align-items: flex-start; }
    }
  </style>
</head>
<body>

  <!-- LEFT — branding panel -->
  <aside class="left-panel">
    <div class="brand">
      <span class="brand-oon">oon</span><span class="brand-dot">.click</span>
    </div>
    <p class="tagline">Regardez · Gagnez · Prospérez</p>

    <div class="quote-box">
      "La première plateforme de monétisation publicitaire pour les abonnés mobiles en Côte d'Ivoire."
      <cite>— Équipe oon.click</cite>
    </div>

    <ul class="features-list">
      <li class="feature-item">
        <div class="feature-icon">📺</div>
        <div class="feature-text">
          Regardez des pubs courtes
          <span>15 à 30 secondes, depuis votre téléphone</span>
        </div>
      </li>
      <li class="feature-item">
        <div class="feature-icon">💰</div>
        <div class="feature-text">
          Gagnez des FCFA
          <span>Crédités directement sur votre compte</span>
        </div>
      </li>
      <li class="feature-item">
        <div class="feature-icon">📲</div>
        <div class="feature-text">
          Retirez via Mobile Money
          <span>Orange, MTN, Moov, Wave</span>
        </div>
      </li>
      <li class="feature-item">
        <div class="feature-icon">🛡️</div>
        <div class="feature-text">
          Système anti-fraude intégré
          <span>Score de confiance et KYC sécurisé</span>
        </div>
      </li>
    </ul>

    <p class="left-footer">© {{ date('Y') }} oon.click · Abidjan, Côte d'Ivoire</p>
  </aside>

  <!-- RIGHT — registration form -->
  <main class="right-panel">
    <div class="auth-box">

      <h1 class="form-title">Créer un compte</h1>
      <p class="form-subtitle">Commencez à gagner des FCFA dès aujourd'hui</p>

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

      <form method="POST" action="{{ route('register.submit') }}">
        @csrf
        <input type="hidden" name="role" value="subscriber">

        <!-- Name -->
        <div class="input-group">
          <label class="input-label">Nom complet</label>
          <div class="input-wrapper-plain {{ $errors->has('name') ? 'has-error' : '' }}">
            <input
              type="text"
              name="name"
              placeholder="Kouassi Amon"
              value="{{ old('name') }}"
              autocomplete="name"
              required
            />
          </div>
          @error('name')
            <div class="field-error">{{ $message }}</div>
          @enderror
        </div>

        <!-- Email -->
        <div class="input-group">
          <label class="input-label">Adresse email</label>
          <div class="input-wrapper-plain {{ $errors->has('email') ? 'has-error' : '' }}">
            <input
              type="email"
              name="email"
              placeholder="exemple@gmail.com"
              value="{{ old('email') }}"
              autocomplete="email"
            />
          </div>
          @error('email')
            <div class="field-error">{{ $message }}</div>
          @enderror
        </div>

        <!-- Phone -->
        <div class="input-group">
          <label class="input-label">Numéro de téléphone</label>
          <div class="input-wrapper {{ $errors->has('phone') ? 'has-error' : '' }}">
            <span class="input-prefix">🇨🇮 +225</span>
            <input
              type="tel"
              name="phone"
              placeholder="07 01 23 45 67"
              maxlength="10"
              value="{{ old('phone') }}"
              autocomplete="tel"
            />
          </div>
          <div style="font-size:11px;color:var(--muted);font-weight:600;margin-top:4px;">Renseignez au moins l'email ou le téléphone</div>
          @error('phone')
            <div class="field-error">{{ $message }}</div>
          @enderror
        </div>

        <!-- Password -->
        <div class="input-group">
          <label class="input-label">Mot de passe</label>
          <div class="input-wrapper-plain {{ $errors->has('password') ? 'has-error' : '' }}">
            <input
              type="password"
              name="password"
              placeholder="••••••••"
              autocomplete="new-password"
            />
          </div>
          @error('password')
            <div class="field-error">{{ $message }}</div>
          @enderror
        </div>

        <!-- Password Confirmation -->
        <div class="input-group">
          <label class="input-label">Confirmer le mot de passe</label>
          <div class="input-wrapper-plain">
            <input
              type="password"
              name="password_confirmation"
              placeholder="••••••••"
              autocomplete="new-password"
            />
          </div>
        </div>

        <!-- Consentements obligatoires -->
        <div class="cgu-row">
          <input type="checkbox" name="consent_cgu" id="consent_cgu" required />
          <label for="consent_cgu" class="cgu-text">
            J'ai lu et j'accepte les <a href="{{ route('legal.cgu') }}" target="_blank">Conditions Générales d'Utilisation</a>
            et la <a href="{{ route('legal.privacy') }}" target="_blank">Politique de Confidentialité</a> de oon.click.
            Je reconnais que mes données de profil seront utilisées pour le ciblage publicitaire en contrepartie d'une rémunération.
            <span style="color:#EF4444;font-weight:800;margin-left:2px;">*</span>
          </label>
        </div>

        <div class="cgu-row">
          <input type="checkbox" name="consent_targeting" id="consent_targeting" required />
          <label for="consent_targeting" class="cgu-text">
            J'accepte que mes données de profil (démographiques, localisation, centres d'intérêt) soient utilisées pour me proposer des publicités ciblées et pertinentes.
            <span style="color:#EF4444;font-weight:800;margin-left:2px;">*</span>
          </label>
        </div>

        <div class="cgu-row">
          <input type="checkbox" name="consent_transfer" id="consent_transfer" required />
          <label for="consent_transfer" class="cgu-text">
            J'accepte que mes données soient transférées et traitées par des prestataires situés en dehors de la Côte d'Ivoire, dans le respect de garanties de sécurité adéquates.
            <span style="color:#EF4444;font-weight:800;margin-left:2px;">*</span>
          </label>
        </div>

        <div class="cgu-row">
          <input type="checkbox" name="consent_fingerprint" id="consent_fingerprint" required />
          <label for="consent_fingerprint" class="cgu-text">
            J'accepte que oon.click collecte une empreinte numérique de mon appareil à des fins de sécurité et de prévention de la fraude.
            <span style="color:#EF4444;font-weight:800;margin-left:2px;">*</span>
          </label>
        </div>

        <!-- Consentements optionnels -->
        <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin:4px 0 8px;">
          Préférences optionnelles
        </div>

        <div class="cgu-row">
          <input type="checkbox" name="consent_notifications" id="consent_notifications" />
          <label for="consent_notifications" class="cgu-text">
            J'accepte de recevoir des notifications push, e-mails et/ou SMS concernant les nouvelles publicités disponibles, mon solde et les actualités du service.
          </label>
        </div>

        <div class="cgu-row">
          <input type="checkbox" name="consent_marketing" id="consent_marketing" />
          <label for="consent_marketing" class="cgu-text">
            J'accepte de recevoir des communications commerciales et promotionnelles de la part de oon.click et de ses partenaires.
          </label>
        </div>

        <div style="font-size:11px;font-weight:600;color:var(--muted);margin-bottom:12px;">
          <span style="color:#EF4444;font-weight:800;">*</span> Consentements obligatoires pour utiliser le service
        </div>

        <button type="submit" class="submit-btn">Créer mon compte</button>
      </form>

      <div class="separator"><span>ou</span></div>

      <a href="{{ route('auth.google', ['role' => 'subscriber']) }}" class="btn-google">
        <svg viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
        Continuer avec Google
      </a>

      <p class="bottom-link" style="margin-top:16px;">
        Déjà un compte ? <a href="{{ route('panel.login') }}">Se connecter</a>
      </p>
      <p class="bottom-link" style="margin-top:8px;">
        Vous êtes annonceur ? <a href="{{ route('register.advertiser') }}">Inscription annonceur →</a>
      </p>

    </div>
  </main>

</body>
</html>
