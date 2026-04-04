<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>oon.click — Regardez des pubs, gagnez en FCFA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--sky:#2AABF0;--sky2:#1A95D8;--sky3:#0E7AB8;--sky-pale:#EBF7FE;--sky-mid:#C5E8FA;--navy:#1B2A6E;--navy2:#162058;--border:#C8E4F6;--muted:#5A7098;--bg:#F0F8FF;}
*{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{font-family:'Nunito',sans-serif;color:var(--navy);}
.logo .oon{color:var(--sky);}.logo .click{color:var(--navy);}

/* NAVBAR */
nav{height:66px;background:white;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 48px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(27,42,110,.07);}
.logo{font-size:24px;font-weight:900;text-decoration:none;}
.nav-links{display:flex;gap:28px;}
.nav-link{font-size:14px;font-weight:700;color:var(--muted);text-decoration:none;}
.nav-link:hover{color:var(--navy);}
.nav-actions{display:flex;gap:10px;align-items:center;}
.btn-ghost{border:2px solid var(--sky);color:var(--sky);padding:8px 18px;border-radius:24px;font-size:13px;font-weight:800;font-family:'Nunito',sans-serif;background:transparent;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;}
.btn-sky{background:linear-gradient(135deg,var(--sky),var(--sky3));color:white;padding:10px 20px;border-radius:24px;font-size:13px;font-weight:800;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(42,171,240,.35);text-decoration:none;display:inline-flex;align-items:center;}

/* HERO */
.hero{background:linear-gradient(135deg,#EBF7FE 0%,#D0ECFB 40%,#E8F4FF 100%);padding:72px 48px;display:flex;gap:48px;align-items:center;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;top:-100px;right:-100px;width:400px;height:400px;background:radial-gradient(circle,rgba(42,171,240,.15),transparent 70%);border-radius:50%;}
.hero-left{flex:1;position:relative;z-index:1;}
.hero-badge{display:inline-flex;align-items:center;gap:6px;background:white;border:1px solid var(--border);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:800;color:var(--sky);margin-bottom:18px;box-shadow:0 2px 10px rgba(42,171,240,.12);}
.hero h1{font-size:42px;font-weight:900;line-height:1.15;}
.hero h1 .grad{background:linear-gradient(90deg,var(--sky),var(--navy));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero p{font-size:15px;color:var(--muted);margin-top:14px;line-height:1.7;font-weight:600;max-width:480px;}
.hero-btns{display:flex;gap:12px;margin-top:24px;flex-wrap:wrap;}
.btn-main{background:linear-gradient(135deg,var(--sky),var(--sky3));color:white;padding:14px 28px;border-radius:28px;font-weight:900;font-size:15px;border:none;cursor:pointer;box-shadow:0 6px 20px rgba(42,171,240,.4);font-family:'Nunito',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.btn-sec{background:white;border:2px solid var(--border);color:var(--navy);padding:14px 28px;border-radius:28px;font-weight:800;font-size:15px;cursor:pointer;font-family:'Nunito',sans-serif;text-decoration:none;display:inline-flex;align-items:center;}
.hero-stats{display:flex;gap:32px;margin-top:32px;flex-wrap:wrap;}
.hstat-v{font-size:26px;font-weight:900;color:var(--navy);}
.hstat-l{font-size:11px;color:var(--muted);font-weight:700;margin-top:2px;text-transform:uppercase;letter-spacing:.5px;}
.hero-right{flex:0 0 340px;position:relative;z-index:1;}
.hero-phone{background:var(--navy2);border-radius:36px;padding:10px;box-shadow:0 20px 50px rgba(27,42,110,.3);}
.hero-screen{background:var(--bg);border-radius:28px;overflow:hidden;}
.hp-topbar{background:white;padding:10px 12px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);}
.hp-logo{font-size:15px;font-weight:900;}
.hp-wallet{margin:10px;background:linear-gradient(135deg,var(--navy),var(--sky2));border-radius:12px;padding:12px;}
.hp-bal-lbl{font-size:9px;color:rgba(255,255,255,.6);font-weight:700;text-transform:uppercase;}
.hp-bal-amt{font-size:22px;font-weight:900;color:white;margin:3px 0;}
.hp-bal-sub{font-size:10px;color:rgba(255,255,255,.6);font-weight:700;}
.hp-ads{padding:4px 10px 10px;}
.hp-ads-title{font-size:10px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;}
.hp-ad{background:white;border-radius:10px;padding:9px;display:flex;gap:8px;align-items:center;border:1px solid var(--border);margin-bottom:6px;}
.hp-ad-ico{width:36px;height:36px;border-radius:8px;background:var(--sky-pale);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.hp-ad-name{font-size:10px;font-weight:800;}
.hp-ad-meta{font-size:9px;color:var(--muted);font-weight:600;}
.hp-ad-earn{font-size:11px;font-weight:900;color:var(--sky);margin-left:auto;}

/* HOW IT WORKS */
.section{padding:72px 48px;}
.section-header{text-align:center;margin-bottom:48px;}
.section-header h2{font-size:32px;font-weight:900;}
.section-header p{font-size:15px;color:var(--muted);font-weight:600;margin-top:8px;}
.steps-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:32px;}
.step-card{text-align:center;padding:32px 24px;background:white;border:1px solid var(--border);border-radius:20px;}
.step-num{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--sky),var(--sky3));color:white;font-size:20px;font-weight:900;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 6px 18px rgba(42,171,240,.35);}
.step-ico{font-size:32px;margin-bottom:12px;}
.step-title{font-size:17px;font-weight:900;margin-bottom:8px;}
.step-desc{font-size:13px;color:var(--muted);font-weight:600;line-height:1.6;}

/* EARNINGS */
.earnings-section{background:var(--sky-pale);padding:72px 48px;}
.earnings-grid{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;}
.calc-card{background:white;border-radius:20px;padding:28px;border:1px solid var(--border);box-shadow:0 8px 30px rgba(27,42,110,.08);}
.calc-row{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);}
.calc-row:last-child{border-bottom:none;background:var(--sky-pale);margin:-4px -28px -28px;padding:16px 28px;border-radius:0 0 20px 20px;}
.calc-label{font-size:14px;font-weight:700;color:var(--muted);}
.calc-value{font-size:18px;font-weight:900;color:var(--navy);}
.calc-value.sky{color:var(--sky);}
.tiers-table{width:100%;border-collapse:collapse;}
.tiers-table th{padding:12px 16px;text-align:left;font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;background:#F8FBFF;border-bottom:1px solid var(--border);}
.tiers-table td{padding:12px 16px;font-size:14px;font-weight:700;border-bottom:1px solid var(--border);}
.tier-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:800;}
.tier-bronze{background:#FEF3C7;color:#92400E;}
.tier-silver{background:#F1F5F9;color:#475569;}
.tier-gold{background:#FEF3C7;color:#B45309;}

/* OPERATORS */
.operators-section{padding:56px 48px;text-align:center;background:white;}
.ops-grid{display:flex;gap:24px;justify-content:center;margin-top:32px;flex-wrap:wrap;}
.op-card{background:white;border:1px solid var(--border);border-radius:16px;padding:20px 32px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 10px rgba(27,42,110,.06);}
.op-icon{font-size:28px;}
.op-name{font-size:14px;font-weight:800;}

/* TESTIMONIALS */
.testi-section{background:var(--sky-pale);padding:72px 48px;}
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:40px;}
.testi-card{background:white;border-radius:16px;padding:24px;border:1px solid var(--border);}
.testi-stars{color:#F59E0B;font-size:16px;margin-bottom:12px;}
.testi-text{font-size:14px;color:var(--muted);font-weight:600;line-height:1.6;font-style:italic;margin-bottom:16px;}
.testi-user{display:flex;align-items:center;gap:10px;}
.testi-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--sky),var(--navy));display:flex;align-items:center;justify-content:center;color:white;font-weight:900;font-size:14px;flex-shrink:0;}
.testi-name{font-size:14px;font-weight:800;}
.testi-city{font-size:11px;color:var(--muted);font-weight:600;}

/* CTA */
.cta-section{background:linear-gradient(135deg,var(--navy),var(--sky3));padding:72px 48px;text-align:center;}
.cta-section h2{font-size:36px;font-weight:900;color:white;margin-bottom:12px;}
.cta-section p{font-size:16px;color:rgba(255,255,255,.7);font-weight:600;margin-bottom:32px;}
.cta-btns{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;}
.cta-btn-white{background:white;color:var(--navy);padding:14px 28px;border-radius:28px;font-weight:900;font-size:15px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;font-family:'Nunito',sans-serif;text-decoration:none;}
.cta-btn-outline{background:transparent;border:2px solid rgba(255,255,255,.4);color:white;padding:14px 28px;border-radius:28px;font-weight:800;font-size:15px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;font-family:'Nunito',sans-serif;text-decoration:none;}

/* FOOTER */
footer{background:var(--navy2);padding:48px;color:rgba(255,255,255,.6);}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px;}
.footer-logo{font-size:22px;font-weight:900;margin-bottom:10px;}
.footer-desc{font-size:13px;line-height:1.6;font-weight:600;}
.footer-col h4{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:white;margin-bottom:14px;}
.footer-link{display:block;font-size:13px;font-weight:600;margin-bottom:8px;color:rgba(255,255,255,.6);text-decoration:none;}
.footer-link:hover{color:var(--sky);}
.footer-bottom{border-top:1px solid rgba(255,255,255,.1);padding-top:24px;display:flex;justify-content:space-between;font-size:12px;font-weight:600;}

/* SUCCESS BANNER */
.success-banner{background:#D1FAE5;border-bottom:1px solid #6EE7B7;padding:12px 24px;text-align:center;font-size:14px;font-weight:700;color:#065F46;}

/* RESPONSIVE */
@media(max-width:900px){
  nav{padding:0 24px;}.nav-links{display:none;}
  .hero{flex-direction:column;padding:48px 24px;}.hero-right{flex:none;width:100%;max-width:320px;margin:0 auto;}
  .steps-grid{grid-template-columns:1fr;}
  .earnings-grid{grid-template-columns:1fr;}
  .testi-grid{grid-template-columns:1fr;}
  .footer-grid{grid-template-columns:1fr 1fr;}
  .section{padding:48px 24px;}
  .earnings-section,.testi-section,.operators-section{padding:48px 24px;}
  .cta-section{padding:48px 24px;}
  footer{padding:36px 24px;}
}
</style>
</head>
<body>

@if(session('success'))
<div class="success-banner">{{ session('success') }}</div>
@endif

<!-- NAVBAR -->
<nav id="top">
  <a class="logo" href="#top"><span class="oon">oon</span><span class="click">.click</span></a>
  <div class="nav-links">
    <a class="nav-link" href="#how">Comment ça marche</a>
    <a class="nav-link" href="#download">Télécharger</a>
    <a class="nav-link" href="@auth{{ auth()->user()->role === 'advertiser' ? route('panel.advertiser.dashboard') : route('register.advertiser') }}@else{{ route('register.advertiser') }}@endauth">Annonceurs</a>
    <a class="nav-link" href="#faq">FAQ</a>
  </div>
  <div class="nav-actions">
    @auth
      @if(auth()->user()->role === 'advertiser')
        <a class="btn-sky" href="{{ route('panel.advertiser.dashboard') }}">Mon espace annonceur</a>
      @elseif(auth()->user()->role === 'admin')
        <a class="btn-sky" href="{{ route('panel.admin.dashboard') }}">Panel admin</a>
      @else
        <span style="font-size:13px;font-weight:700;color:var(--navy);margin-right:6px;">👋 {{ Str::limit(auth()->user()->name ?? auth()->user()->phone, 15) }}</span>
        <form method="POST" action="{{ route('panel.logout') }}" style="display:inline;margin:0;">
          @csrf
          <button type="submit" class="btn-ghost" style="cursor:pointer;font-family:inherit;">Se déconnecter</button>
        </form>
      @endif
    @else
      <a class="btn-ghost" href="{{ route('panel.login') }}">Se connecter</a>
      <a class="btn-sky" href="{{ route('register') }}">S'inscrire gratuitement</a>
    @endauth
  </div>
</nav>

<!-- HERO -->
<section class="hero" id="download">
  <div class="hero-left">
    <div class="hero-badge">🎉 Déjà 8 000+ abonnés en Côte d'Ivoire</div>
    <h1>Regardez des publicités,<br>gagnez de l'argent en <span class="grad">FCFA</span></h1>
    <p>oon.click vous rémunère en FCFA pour regarder des publicités ciblées. Retirez vos gains directement via Orange Money, MTN MoMo, Moov ou Wave.</p>
    <div class="hero-btns">
      <a class="btn-main" href="#download">📱 Télécharger l'app</a>
      <button class="btn-sec" onclick="document.getElementById('how').scrollIntoView({behavior:'smooth'})">Comment ça marche →</button>
    </div>
    <div class="hero-stats">
      <div><div class="hstat-v">8 420</div><div class="hstat-l">Abonnés actifs</div></div>
      <div><div class="hstat-v">35k+</div><div class="hstat-l">Vues / semaine</div></div>
      <div><div class="hstat-v">2,4M FCFA</div><div class="hstat-l">Distribués</div></div>
    </div>
  </div>
  <div class="hero-right">
    <div class="hero-phone">
      <div class="hero-screen">
        <div class="hp-topbar">
          <div class="hp-logo"><span style="color:var(--sky)">oon</span><span style="color:var(--navy)">.click</span></div>
          <div style="font-size:18px">🔔</div>
        </div>
        <div class="hp-wallet">
          <div class="hp-bal-lbl">Mon solde</div>
          <div class="hp-bal-amt">12 450 FCFA</div>
          <div class="hp-bal-sub">+600 FCFA aujourd'hui · 17 vues restantes</div>
        </div>
        <div class="hp-ads">
          <div class="hp-ads-title">Publicités disponibles</div>
          <div class="hp-ad"><div class="hp-ad-ico">🏦</div><div><div class="hp-ad-name">Orange Money CI</div><div class="hp-ad-meta">Finance · 30s</div></div><div class="hp-ad-earn">+60F</div></div>
          <div class="hp-ad"><div class="hp-ad-ico">📱</div><div><div class="hp-ad-name">MTN Côte d'Ivoire</div><div class="hp-ad-meta">Telecom · 15s</div></div><div class="hp-ad-earn">+60F</div></div>
          <div class="hp-ad"><div class="hp-ad-ico">🛒</div><div><div class="hp-ad-name">Carrefour Abidjan</div><div class="hp-ad-meta">Commerce · 20s</div></div><div class="hp-ad-earn">+60F</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" id="how" style="background:white;">
  <div class="section-header">
    <h2>Comment ça fonctionne ?</h2>
    <p>3 étapes simples pour commencer à gagner de l'argent depuis votre téléphone</p>
  </div>
  <div class="steps-grid">
    <div class="step-card">
      <div class="step-num">1</div>
      <div class="step-ico">📱</div>
      <div class="step-title">Inscrivez-vous gratuitement</div>
      <div class="step-desc">Créez votre compte avec votre numéro de téléphone ivoirien. L'inscription prend moins de 2 minutes.</div>
    </div>
    <div class="step-card">
      <div class="step-num">2</div>
      <div class="step-ico">▶️</div>
      <div class="step-title">Regardez des publicités</div>
      <div class="step-desc">Visionnez jusqu'à 30 publicités par jour depuis votre smartphone. Chaque vue complète = +60 FCFA crédité automatiquement.</div>
    </div>
    <div class="step-card">
      <div class="step-num">3</div>
      <div class="step-ico">💸</div>
      <div class="step-title">Retirez via Mobile Money</div>
      <div class="step-desc">Transférez vos gains directement vers Orange Money, MTN MoMo, Moov ou Wave. Minimum de retrait : 5 000 FCFA.</div>
    </div>
  </div>
</section>

<!-- EARNINGS -->
<section class="earnings-section" id="earnings">
  <div class="section-header">
    <h2>Combien pouvez-vous gagner ?</h2>
    <p>Vos gains dépendent de votre activité quotidienne</p>
  </div>
  <div class="earnings-grid">
    <div class="calc-card">
      <div class="calc-row"><span class="calc-label">Publicités par jour</span><span class="calc-value">30 vues</span></div>
      <div class="calc-row"><span class="calc-label">Gain par vue</span><span class="calc-value">60 FCFA</span></div>
      <div class="calc-row"><span class="calc-label">Gains par jour</span><span class="calc-value sky">1 800 FCFA</span></div>
      <div class="calc-row"><span class="calc-label">Gains par semaine</span><span class="calc-value sky">12 600 FCFA</span></div>
      <div class="calc-row"><span class="calc-label" style="font-weight:900;color:var(--navy);">💰 Gains par mois</span><span class="calc-value" style="font-size:24px;color:var(--sky);">54 000 FCFA</span></div>
    </div>
    <div>
      <h3 style="font-size:20px;font-weight:900;margin-bottom:16px;">Niveaux d'abonnés</h3>
      <div style="background:white;border-radius:16px;overflow:hidden;border:1px solid var(--border);">
        <table class="tiers-table">
          <thead><tr><th>Niveau</th><th>Vues/jour</th><th>Gain/mois</th></tr></thead>
          <tbody>
            <tr><td><span class="tier-badge tier-bronze">🥉 Bronze</span></td><td>10 vues</td><td>18 000 FCFA</td></tr>
            <tr><td><span class="tier-badge tier-silver">🥈 Silver</span></td><td>20 vues</td><td>36 000 FCFA</td></tr>
            <tr><td><span class="tier-badge tier-gold">🥇 Gold</span></td><td>30 vues</td><td>54 000 FCFA</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<!-- OPERATORS -->
<section class="operators-section" id="operators">
  <h2 style="font-size:28px;font-weight:900;">Opérateurs compatibles</h2>
  <p style="color:var(--muted);font-weight:600;margin-top:8px;">Retirez vos gains via votre opérateur préféré</p>
  <div class="ops-grid">
    <div class="op-card"><div class="op-icon">🟠</div><div class="op-name">Orange Money</div></div>
    <div class="op-card"><div class="op-icon">🟡</div><div class="op-name">MTN MoMo</div></div>
    <div class="op-card"><div class="op-icon">🔵</div><div class="op-name">Moov Africa</div></div>
    <div class="op-card"><div class="op-icon">🟦</div><div class="op-name">Wave</div></div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="testi-section" id="faq">
  <div class="section-header">
    <h2>Ce que disent nos abonnés</h2>
    <p>Plus de 8 000 abonnés actifs en Côte d'Ivoire</p>
  </div>
  <div class="testi-grid">
    <div class="testi-card">
      <div class="testi-stars">★★★★★</div>
      <div class="testi-text">"Je retire environ 40 000 FCFA par mois en regardant des pubs pendant mes pauses. C'est vraiment simple et le paiement est immédiat sur Orange Money."</div>
      <div class="testi-user"><div class="testi-av">K</div><div><div class="testi-name">Kouassi Amon</div><div class="testi-city">Cocody, Abidjan</div></div></div>
    </div>
    <div class="testi-card">
      <div class="testi-stars">★★★★★</div>
      <div class="testi-text">"Grâce à oon.click j'ai pu payer mes frais de scolarité ce mois. J'ai invité 5 amis et on gagne tous ensemble. Application fiable à 100%."</div>
      <div class="testi-user"><div class="testi-av">A</div><div><div class="testi-name">Adjoua Koffi</div><div class="testi-city">Yopougon, Abidjan</div></div></div>
    </div>
    <div class="testi-card">
      <div class="testi-stars">★★★★☆</div>
      <div class="testi-text">"J'utilisais d'autres applis mais oon.click est la seule qui paie vraiment. 60 FCFA par pub × 30 pubs = 1 800F par jour, ça compte !"</div>
      <div class="testi-user"><div class="testi-av">S</div><div><div class="testi-name">Sékou Traoré</div><div class="testi-city">Bouaké</div></div></div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <h2>Commencez à gagner aujourd'hui</h2>
  <p>Inscription gratuite · Aucune carte bancaire requise · Disponible sur Android &amp; iOS</p>
  <div class="cta-btns">
    <a class="cta-btn-white" href="#download">📱 Google Play</a>
    <a class="cta-btn-white" href="#download">🍎 App Store</a>
    <a class="cta-btn-outline" href="{{ route('register.advertiser') }}">Vous êtes annonceur ? →</a>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-grid">
    <div>
      <div class="footer-logo"><span style="color:var(--sky)">oon</span><span style="color:rgba(255,255,255,.8)">.click</span></div>
      <div class="footer-desc">La première plateforme ivoirienne qui vous rémunère pour regarder des publicités ciblées. Paiement garanti en FCFA via Mobile Money.</div>
    </div>
    <div>
      <h4>Application</h4>
      <a class="footer-link" href="#download">Télécharger</a>
      <a class="footer-link" href="#how">Comment ça marche</a>
      <a class="footer-link" href="#faq">FAQ Abonnés</a>
    </div>
    <div>
      <h4>Annonceurs</h4>
      <a class="footer-link" href="{{ route('register.advertiser') }}">Créer une campagne</a>
      <a class="footer-link" href="#earnings">Tarifs</a>
      <a class="footer-link" href="#faq">FAQ Annonceurs</a>
    </div>
    <div>
      <h4>Légal</h4>
      <a class="footer-link" href="{{ route('legal.cgu') }}">Conditions d'utilisation</a>
      <a class="footer-link" href="{{ route('legal.privacy') }}">Politique de confidentialité</a>
      <a class="footer-link" href="#">Contact</a>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© {{ date('Y') }} oon.click — Tous droits réservés</span>
    <span>Fait avec soin en Côte d'Ivoire</span>
  </div>
</footer>

</body>
</html>
