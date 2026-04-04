<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Réinitialiser le mot de passe — oon.click Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Inter',system-ui,sans-serif;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg, #0A1628 0%, #0F172A 40%, #1A2744 70%, #0D1F3C 100%);
            position:relative;
            overflow:hidden;
        }

        /* Decorative background glows */
        body::before {
            content:'';
            position:fixed;
            top:-200px;
            left:-200px;
            width:600px;
            height:600px;
            background:radial-gradient(circle, rgba(14,165,233,0.12) 0%, transparent 70%);
            pointer-events:none;
        }
        body::after {
            content:'';
            position:fixed;
            bottom:-200px;
            right:-100px;
            width:500px;
            height:500px;
            background:radial-gradient(circle, rgba(56,189,248,0.08) 0%, transparent 70%);
            pointer-events:none;
        }

        /* Card */
        .login-card {
            width:100%;
            max-width:420px;
            background:#fff;
            border-radius:20px;
            padding:40px 36px;
            box-shadow:0 32px 80px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.06);
            position:relative;
            z-index:10;
        }

        /* Brand */
        .brand {
            display:flex;
            align-items:center;
            gap:12px;
            margin-bottom:32px;
            justify-content:center;
        }
        .brand-icon {
            width:44px;
            height:44px;
            background:linear-gradient(135deg,#38BDF8,#0EA5E9);
            border-radius:13px;
            display:flex;
            align-items:center;
            justify-content:center;
            color:#fff;
            font-weight:900;
            font-size:18px;
            box-shadow:0 4px 16px rgba(14,165,233,0.35);
        }
        .brand-text {
            font-size:24px;
            font-weight:800;
            color:#0F172A;
            letter-spacing:-0.8px;
        }
        .brand-text span { color:#0EA5E9; }

        /* Heading */
        .login-heading {
            text-align:center;
            margin-bottom:28px;
        }
        .login-heading h1 {
            font-size:20px;
            font-weight:800;
            color:#0F172A;
            letter-spacing:-0.4px;
            margin-bottom:6px;
        }
        .login-heading p {
            font-size:13px;
            color:#64748B;
            font-weight:500;
        }

        /* Form */
        .form-group { margin-bottom:18px; }
        .form-label {
            display:block;
            font-size:12px;
            font-weight:700;
            color:#374151;
            margin-bottom:7px;
            letter-spacing:0.2px;
        }
        .form-input {
            width:100%;
            border:1.5px solid #E2E8F0;
            border-radius:11px;
            padding:11px 14px;
            font-size:14px;
            font-family:inherit;
            color:#0F172A;
            outline:none;
            transition:border-color .15s, box-shadow .15s;
            background:#F8FAFC;
        }
        .form-input:focus {
            border-color:#0EA5E9;
            box-shadow:0 0 0 4px rgba(14,165,233,0.1);
            background:#fff;
        }
        .form-input::placeholder { color:#CBD5E1; }
        .form-input.error { border-color:#EF4444; box-shadow:0 0 0 4px rgba(239,68,68,0.08); }

        /* Code input — plus grand pour la lisibilité */
        .form-input.code-input {
            text-align:center;
            font-size:24px;
            font-weight:700;
            letter-spacing:6px;
            padding:14px 14px;
        }

        /* Submit button */
        .btn-login {
            width:100%;
            padding:13px;
            background:linear-gradient(135deg,#0EA5E9,#0284C7);
            color:#fff;
            border:none;
            border-radius:12px;
            font-size:14px;
            font-weight:700;
            font-family:inherit;
            cursor:pointer;
            box-shadow:0 4px 16px rgba(14,165,233,0.35);
            transition:all .15s;
            letter-spacing:0.2px;
        }
        .btn-login:hover {
            box-shadow:0 6px 24px rgba(14,165,233,0.45);
            transform:translateY(-1px);
        }
        .btn-login:active { transform:translateY(0); }

        /* Error alert */
        .alert-error {
            background:#FEF2F2;
            border:1px solid #FECACA;
            border-radius:10px;
            padding:10px 14px;
            margin-bottom:20px;
            font-size:12px;
            color:#B91C1C;
            font-weight:600;
            display:flex;
            align-items:center;
            gap:6px;
        }

        /* Back link */
        .back-link {
            display:block;
            text-align:center;
            margin-top:20px;
            font-size:12px;
            color:#0EA5E9;
            font-weight:600;
            text-decoration:none;
            transition:opacity .15s;
        }
        .back-link:hover { opacity:0.75; }

        /* Hint text */
        .field-hint {
            font-size:11px;
            color:#94A3B8;
            margin-top:5px;
            font-weight:500;
        }

        /* Footer */
        .login-footer {
            text-align:center;
            margin-top:24px;
            font-size:11px;
            color:#94A3B8;
            font-weight:500;
        }

        /* Decorative dots inside card */
        .card-deco {
            position:absolute;
            top:0;
            right:0;
            width:120px;
            height:120px;
            overflow:hidden;
            border-radius:0 20px 0 0;
            pointer-events:none;
        }
        .card-deco-inner {
            position:absolute;
            top:-40px;
            right:-40px;
            width:120px;
            height:120px;
            background:radial-gradient(circle, rgba(14,165,233,0.06) 0%, transparent 70%);
            border-radius:50%;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="card-deco"><div class="card-deco-inner"></div></div>

        {{-- Brand --}}
        <div class="brand">
            <div class="brand-icon">O</div>
            <div class="brand-text"><span>oon</span>.click</div>
        </div>

        {{-- Heading --}}
        <div class="login-heading">
            <h1>Nouveau mot de passe</h1>
            <p>Saisissez le code reçu par email et choisissez un nouveau mot de passe</p>
        </div>

        {{-- Error messages --}}
        @if($errors->any())
        <div class="alert-error">
            &#9888; {{ $errors->first() }}
        </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('panel.password.update') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">Adresse email</label>
                <input
                    class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $email ?? '') }}"
                    placeholder="annonceur@exemple.com"
                    autocomplete="email"
                />
            </div>

            <div class="form-group">
                <label class="form-label" for="code">Code de vérification</label>
                <input
                    class="form-input code-input {{ $errors->has('code') ? 'error' : '' }}"
                    type="text"
                    id="code"
                    name="code"
                    value="{{ old('code') }}"
                    placeholder="000000"
                    maxlength="6"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autofocus
                />
                <p class="field-hint">Code à 6 chiffres reçu par email. Valable 15 minutes.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Nouveau mot de passe</label>
                <input
                    class="form-input {{ $errors->has('password') ? 'error' : '' }}"
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Minimum 8 caractères"
                    autocomplete="new-password"
                />
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                <input
                    class="form-input"
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="Répétez le nouveau mot de passe"
                    autocomplete="new-password"
                />
            </div>

            <button type="submit" class="btn-login">Réinitialiser le mot de passe</button>
        </form>

        <a href="{{ route('panel.password.request') }}" class="back-link">&#8592; Renvoyer un nouveau code</a>

        <div class="login-footer">
            oon.click &mdash; Plateforme publicitaire &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
