<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion — oon.click Panel</title>
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

        /* Checkbox row */
        .remember-row {
            display:flex;
            align-items:center;
            gap:8px;
            margin-bottom:22px;
        }
        .remember-row input[type="checkbox"] {
            width:16px;
            height:16px;
            accent-color:#0EA5E9;
            cursor:pointer;
            flex-shrink:0;
        }
        .remember-row label {
            font-size:13px;
            color:#64748B;
            font-weight:500;
            cursor:pointer;
            user-select:none;
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

        /* Success alert */
        .alert-success {
            background:#F0FDF4;
            border:1px solid #BBF7D0;
            border-radius:10px;
            padding:10px 14px;
            margin-bottom:20px;
            font-size:12px;
            color:#15803D;
            font-weight:600;
            display:flex;
            align-items:center;
            gap:6px;
        }

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

        /* Forgot password link */
        .forgot-link {
            display:block;
            text-align:right;
            font-size:12px;
            color:#0EA5E9;
            font-weight:600;
            text-decoration:none;
            margin-top:-10px;
            margin-bottom:20px;
            transition:opacity .15s;
        }
        .forgot-link:hover { opacity:0.75; }

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
            <h1>Connexion au panneau</h1>
            <p>Accès réservé aux administrateurs et annonceurs</p>
        </div>

        {{-- Success message (e.g. after password reset) --}}
        @if(session('status'))
        <div class="alert-success">
            &#10003; {{ session('status') }}
        </div>
        @endif

        {{-- Error messages --}}
        @if($errors->any())
        <div class="alert-error">
            ⚠ {{ $errors->first() }}
        </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('panel.login.submit') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">Email ou téléphone</label>
                <input
                    class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                    type="text"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="admin@oon.click ou +2250XXXXXXXX"
                    autofocus
                    autocomplete="username"
                />
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Mot de passe</label>
                <input
                    class="form-input {{ $errors->has('password') ? 'error' : '' }}"
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••••••"
                    autocomplete="current-password"
                />
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">Se souvenir de moi</label>
            </div>

            <a href="{{ route('panel.password.request') }}" class="forgot-link">Mot de passe oublié ?</a>

            <button type="submit" class="btn-login">Connexion</button>
        </form>

        <div style="display:flex;align-items:center;gap:14px;margin:18px 0;">
            <div style="flex:1;height:1px;background:#E2E8F0;"></div>
            <span style="font-size:11px;font-weight:700;color:#94A3B8;text-transform:uppercase;">ou</span>
            <div style="flex:1;height:1px;background:#E2E8F0;"></div>
        </div>

        <a href="{{ route('auth.google') }}" style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:12px;border:1.5px solid #E2E8F0;border-radius:12px;background:#F8FAFC;font-family:'Inter',sans-serif;font-size:14px;font-weight:600;color:#374151;cursor:pointer;text-decoration:none;transition:background 0.2s;">
            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Continuer avec Google
        </a>

        <div class="login-footer" style="margin-top:20px;">
            <div style="margin-bottom:8px;">
                Pas encore de compte ?
                <a href="{{ route('register') }}" style="color:#0EA5E9;font-weight:600;text-decoration:none;">S'inscrire gratuitement</a>
                &nbsp;·&nbsp;
                <a href="{{ route('register.advertiser') }}" style="color:#0EA5E9;font-weight:600;text-decoration:none;">Inscription annonceur</a>
            </div>
            <div style="margin-bottom:6px;">
                <a href="{{ route('legal.cgu') }}" style="color:#94A3B8;font-size:11px;text-decoration:none;" target="_blank">Conditions d'utilisation</a>
                &nbsp;·&nbsp;
                <a href="{{ route('legal.privacy') }}" style="color:#94A3B8;font-size:11px;text-decoration:none;" target="_blank">Politique de confidentialite</a>
            </div>
            oon.click &mdash; Plateforme publicitaire &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
