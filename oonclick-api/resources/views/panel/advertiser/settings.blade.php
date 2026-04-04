@extends('layouts.panel-advertiser', ['walletBalance' => $walletBalance])

@section('title', 'Paramètres')
@section('topbar-title', 'Paramètres du compte')

@section('sidebar-nav')
    @include('panel.advertiser._sidebar', ['active' => 'settings'])
@endsection

@section('content')

    @if(session('success'))
    <div style="background:#D1FAE5;border:1px solid #6EE7B7;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:13px;font-weight:700;color:#065F46;display:flex;align-items:center;gap:8px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div style="background:#FEE2E2;border:1px solid #FECACA;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:13px;font-weight:700;color:#991B1B;">
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:4px;">
            @foreach($errors->all() as $error)
            <li style="display:flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                {{ $error }}
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Photo de profil --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-head">
            <div class="card-title">Photo de profil</div>
        </div>
        <form method="POST" action="{{ route('panel.advertiser.settings.avatar') }}" enctype="multipart/form-data" class="form-section">
            @csrf
            <div style="display:flex;align-items:center;gap:20px;">
                <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#3B82F6,#06B6D4);display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:900;flex-shrink:0;overflow:hidden;">
                    @if($user->avatar_path)
                        <img src="{{ asset('storage/' . $user->avatar_path) }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        {{ strtoupper(substr($user->name ?? 'A', 0, 1)) }}
                    @endif
                </div>
                <div style="flex:1;">
                    <input type="file" name="avatar" id="avatar" accept="image/*" style="margin-bottom:8px;font-size:13px;font-family:inherit;">
                    <p style="font-size:11px;color:#94A3B8;font-weight:600;">JPG, PNG ou WebP. Max 2 Mo.</p>
                    @error('avatar')
                    <span style="font-size:11px;color:#DC2626;font-weight:600;">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="btn-submit" style="flex-shrink:0;">Mettre à jour</button>
            </div>
        </form>
    </div>

    {{-- Informations personnelles --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-head">
            <div class="card-title">Informations personnelles</div>
        </div>
        <form method="POST" action="{{ route('panel.advertiser.settings.profile') }}" class="form-section">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="name">Nom complet</label>
                    <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Adresse email</label>
                    <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Numéro de téléphone</label>
                    <input id="phone" name="phone" type="text" class="form-input" value="{{ old('phone', $user->phone) }}" placeholder="+225 07 00 00 00 00">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit">Enregistrer les modifications</button>
            </div>
        </form>
    </div>

    {{-- Informations de la société --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-head">
            <div class="card-title">Informations de la société</div>
        </div>
        <form method="POST" action="{{ route('panel.advertiser.settings.profile') }}" class="form-section">
            @csrf
            @method('PUT')
            {{-- Les champs personnels sont requis dans la validation, on les renvoie en hidden --}}
            <input type="hidden" name="name" value="{{ $user->name }}">
            <input type="hidden" name="email" value="{{ $user->email }}">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="company">Raison sociale</label>
                    <input id="company" name="company" type="text" class="form-input" value="{{ old('company', $user->company) }}" placeholder="Ex. OonClick SARL">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sector">Secteur d'activité</label>
                    <select id="sector" name="sector" class="form-input">
                        <option value="">— Sélectionner —</option>
                        @foreach(['Banque & Finance','Telecom','Commerce & Distribution','Santé','Education','Transport','Alimentation','Immobilier','Technologie','Autre'] as $s)
                            <option value="{{ $s }}" {{ old('sector', $user->sector) === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="rccm">Numéro RCCM</label>
                    <input id="rccm" name="rccm" type="text" class="form-input" value="{{ old('rccm', $user->rccm) }}" placeholder="CI-ABJ-2026-B-12345">
                </div>
                <div class="form-group">
                    <label class="form-label" for="nif">NIF / Identifiant fiscal</label>
                    <input id="nif" name="nif" type="text" class="form-input" value="{{ old('nif', $user->nif) }}" placeholder="CI-2025-0000">
                </div>
                <div class="form-group">
                    <label class="form-label" for="website">Site web</label>
                    <input id="website" name="website" type="url" class="form-input" value="{{ old('website', $user->website) }}" placeholder="https://www.example.ci">
                </div>
                <div class="form-group">
                    <label class="form-label" for="address">Adresse</label>
                    <input id="address" name="address" type="text" class="form-input" value="{{ old('address', $user->address) }}" placeholder="Cocody, Abidjan, Côte d'Ivoire">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit">Enregistrer les informations</button>
            </div>
        </form>
    </div>

    {{-- Changer le mot de passe --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-head">
            <div class="card-title">Changer le mot de passe</div>
        </div>
        <form method="POST" action="{{ route('panel.advertiser.settings.password') }}" class="form-section">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="current_password">Mot de passe actuel</label>
                    <input id="current_password" name="current_password" type="password" class="form-input" required autocomplete="current-password">
                    @error('current_password')
                    <span style="font-size:11px;color:#DC2626;font-weight:600;margin-top:2px;">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    {{-- spacer --}}
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Nouveau mot de passe</label>
                    <input id="password" name="password" type="password" class="form-input" required autocomplete="new-password" minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirmer le nouveau mot de passe</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" required autocomplete="new-password" minlength="6">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit">Mettre à jour le mot de passe</button>
            </div>
        </form>
    </div>

    {{-- Informations du compte --}}
    <div class="card">
        <div class="card-head">
            <div class="card-title">Compte</div>
        </div>
        <div class="form-section">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Rôle</label>
                    <div class="form-input" style="background:#F8FAFC;color:#64748B;cursor:default;display:flex;align-items:center;gap:6px;">
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#3B82F6;flex-shrink:0;"></span>
                        {{ ucfirst($user->role ?? 'Annonceur') }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Membre depuis</label>
                    <div class="form-input" style="background:#F8FAFC;color:#64748B;cursor:default;">
                        {{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : $user->created_at?->format('d/m/Y') ?? '—' }}
                    </div>
                </div>
            </div>
            <div class="form-actions" style="justify-content:flex-start;margin-top:24px;padding-top:20px;border-top:1px solid #F0F3F7;">
                <form method="POST" action="{{ route('panel.logout') }}">
                    @csrf
                    <button type="submit" class="btn-cancel" style="border-color:#FCA5A5;color:#DC2626;display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>

@endsection
