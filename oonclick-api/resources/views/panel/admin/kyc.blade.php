@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Vérification KYC')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'kyc'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">KYC</span>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">Vérification KYC</h1>
    </div>

    @if(session('success'))
    <div style="background:#DCFCE7;color:#15803D;border:1px solid #BBF7D0;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        {{ session('error') }}
    </div>
    @endif

    {{-- KPI CARDS --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="accent amber"></div>
            <div class="kpi-header">
                <div class="kpi-icon amber">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                </div>
                <span class="kpi-label">En attente</span>
            </div>
            <div class="kpi-value">{{ $pendingCount }}</div>
            <div class="kpi-change neutral">Documents à réviser</div>
        </div>
        <div class="kpi-card">
            <div class="accent green"></div>
            <div class="kpi-header">
                <div class="kpi-icon green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="kpi-label">Approuvés</span>
            </div>
            <div class="kpi-value">{{ $approvedCount }}</div>
            <div class="kpi-change neutral">Documents validés</div>
        </div>
        <div class="kpi-card">
            <div class="accent red"></div>
            <div class="kpi-header">
                <div class="kpi-icon" style="background:#FEE2E2;color:#B91C1C">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="kpi-label">Rejetés</span>
            </div>
            <div class="kpi-value">{{ $rejectedCount }}</div>
            <div class="kpi-change neutral">Documents refusés</div>
        </div>
    </div>

    {{-- TABLE DES DOCUMENTS EN ATTENTE --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Documents en attente de validation</div>
        </div>

        @if($documents->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Niveau KYC</th>
                    <th>Type de document</th>
                    <th>Fichier</th>
                    <th>Soumis le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $document)
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar" style="background:linear-gradient(135deg,#6366F1,#4F46E5)">
                                {{ strtoupper(substr($document->user->name ?? $document->user->phone ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <div class="user-name">{{ $document->user->name ?? '—' }}</div>
                                <div class="user-sub">{{ $document->user->phone }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge" style="background:#EEF2FF;color:#4F46E5">
                            Niveau {{ $document->level }}
                        </span>
                    </td>
                    <td>
                        @php
                            $docLabels = [
                                'national_id'     => "Carte nationale d'identité",
                                'passport'        => 'Passeport',
                                'business_reg'    => 'Registre de commerce',
                                'selfie'          => 'Selfie',
                                'proof_of_address' => 'Justificatif de domicile',
                            ];
                        @endphp
                        <span style="font-weight:600;color:#0F172A">
                            {{ $docLabels[$document->document_type] ?? $document->document_type }}
                        </span>
                    </td>
                    <td>
                        @php
                            $ext = pathinfo($document->file_path, PATHINFO_EXTENSION);
                            $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png']);
                        @endphp
                        @if($isImage)
                            <a href="{{ Storage::disk($document->file_disk)->temporaryUrl($document->file_path, now()->addMinutes(10)) }}"
                               target="_blank"
                               class="action-btn" style="font-size:11px;padding:4px 10px;display:inline-flex;align-items:center;gap:4px">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Voir l'image
                            </a>
                        @else
                            <a href="{{ Storage::disk($document->file_disk)->temporaryUrl($document->file_path, now()->addMinutes(10)) }}"
                               target="_blank"
                               class="action-btn" style="font-size:11px;padding:4px 10px;display:inline-flex;align-items:center;gap:4px">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                Télécharger (PDF)
                            </a>
                        @endif
                    </td>
                    <td style="color:#94A3B8;font-size:12px">
                        {{ $document->submitted_at->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:flex-start">
                            {{-- Bouton Approuver --}}
                            <form method="POST" action="{{ route('panel.admin.kyc.approve', $document) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="action-btn success btn-sm"
                                    onclick="return confirm('Approuver ce document KYC ?')">
                                    Approuver
                                </button>
                            </form>

                            {{-- Formulaire de rejet avec motif --}}
                            <button type="button" class="action-btn danger btn-sm"
                                onclick="document.getElementById('reject-form-{{ $document->id }}').classList.toggle('hidden')">
                                Rejeter
                            </button>

                            <div id="reject-form-{{ $document->id }}" class="hidden" style="width:100%;margin-top:8px">
                                <form method="POST" action="{{ route('panel.admin.kyc.reject', $document) }}">
                                    @csrf
                                    <textarea name="rejection_reason" placeholder="Motif du rejet (obligatoire)"
                                        required maxlength="500"
                                        style="width:100%;border:1px solid #E2E8F0;border-radius:8px;padding:8px;font-size:12px;resize:vertical;min-height:60px;margin-bottom:6px"></textarea>
                                    <button type="submit" class="action-btn danger btn-sm"
                                        onclick="return confirm('Confirmer le rejet ?')">
                                        Confirmer le rejet
                                    </button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($documents->hasPages())
        <div class="pagination">
            <span>Affichage de {{ $documents->firstItem() }} à {{ $documents->lastItem() }} sur {{ $documents->total() }}</span>
            <div>{{ $documents->links('pagination::simple-default') }}</div>
        </div>
        @endif

        @else
        <div class="empty-state">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" stroke="#CBD5E1" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
            </div>
            <p>Aucun document KYC en attente de validation</p>
        </div>
        @endif
    </div>
@endsection
