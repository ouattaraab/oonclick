@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Gestion des fonctionnalités')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'features'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Fonctionnalités</span>
@endsection

@push('styles')
<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:100; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal { background:#fff; border-radius:16px; padding:28px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.15); }
    .modal-title { font-size:17px; font-weight:800; color:#0F172A; margin-bottom:6px; }
    .modal-subtitle { font-size:12px; color:#64748B; margin-bottom:18px; }
    .form-group { margin-bottom:16px; }
    .form-label { display:block; font-size:12px; font-weight:600; color:#64748B; margin-bottom:6px; }
    .form-input { width:100%; border:1px solid #E2E8F0; border-radius:10px; padding:9px 14px; font-size:12px; font-family:monospace; color:#0F172A; outline:none; transition:border-color .15s; background:#F8FAFC; resize:vertical; }
    .form-input:focus { border-color:#0EA5E9; box-shadow:0 0 0 3px rgba(14,165,233,0.08); background:#fff; }
    .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
    .slug-mono { font-family:monospace; font-size:12px; color:#0F172A; background:#F1F5F9; padding:2px 7px; border-radius:5px; }
    .toggle-form { display:inline; margin:0; }
    .toggle-btn { padding:4px 12px; border-radius:6px; font-size:11px; font-weight:600; border:none; cursor:pointer; font-family:inherit; }
    .toggle-btn.enabled  { background:#DCFCE7; color:#15803D; }
    .toggle-btn.disabled { background:#FEE2E2; color:#B91C1C; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <h1 class="page-title">Gestion des fonctionnalités</h1>
    </div>

    @if(session('success'))
    <div style="background:#DCFCE7;color:#15803D;border:1px solid #BBF7D0;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        &#10003; {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div style="background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        @foreach($errors->all() as $error)
            <div>&#x26A0; {{ $error }}</div>
        @endforeach
    </div>
    @endif

    {{-- KPI STRIP --}}
    <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="kpi-card">
            <div class="accent sky"></div>
            <div class="kpi-header">
                <div class="kpi-icon sky">&#9881;&#65039;</div>
                <span class="kpi-label">Total fonctionnalités</span>
            </div>
            <div class="kpi-value">{{ $totalFeatures }}</div>
            <div class="kpi-change neutral">Fonctionnalités configurées</div>
        </div>
        <div class="kpi-card">
            <div class="accent green"></div>
            <div class="kpi-header">
                <div class="kpi-icon green">&#9989;</div>
                <span class="kpi-label">Activées</span>
            </div>
            <div class="kpi-value">{{ $enabledCount }}</div>
            <div class="kpi-change neutral">Disponibles pour les utilisateurs</div>
        </div>
        <div class="kpi-card">
            <div class="accent amber"></div>
            <div class="kpi-header">
                <div class="kpi-icon amber">&#9940;</div>
                <span class="kpi-label">Désactivées</span>
            </div>
            <div class="kpi-value">{{ $disabledCount }}</div>
            <div class="kpi-change neutral">Non disponibles</div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Liste des fonctionnalités</div>
        </div>
        @if($features->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Ordre</th>
                    <th>Label</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($features as $feature)
                <tr>
                    <td style="color:#94A3B8;font-size:12px;">{{ $feature->sort_order }}</td>
                    <td>
                        <span style="font-weight:600;color:#0F172A;">{{ $feature->label }}</span>
                        <div style="margin-top:3px;"><span class="slug-mono">{{ $feature->feature_slug }}</span></div>
                    </td>
                    <td style="max-width:300px">
                        <span style="font-size:12px;color:#64748B;">{{ Str::limit($feature->description ?? '—', 80) }}</span>
                    </td>
                    <td>
                        <form class="toggle-form" method="POST" action="{{ route('panel.admin.features.toggle', $feature) }}">
                            @csrf
                            <button type="submit" class="toggle-btn {{ $feature->is_enabled ? 'enabled' : 'disabled' }}">
                                {{ $feature->is_enabled ? 'Actif' : 'Inactif' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <button class="action-btn primary btn-sm"
                            onclick="openConfigModal({{ $feature->id }}, '{{ addslashes($feature->label) }}', '{{ addslashes($feature->feature_slug) }}')">
                            Configurer
                        </button>
                    </td>
                </tr>

                {{-- Config JSON data for JS --}}
                <script type="application/json" id="feature-config-{{ $feature->id }}">
                {!! json_encode($feature->config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
                </script>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="icon">&#9881;&#65039;</div>
            <p>Aucune fonctionnalité configurée</p>
        </div>
        @endif
    </div>

    {{-- CONFIG MODAL --}}
    <div class="modal-overlay" id="configModal">
        <div class="modal">
            <div class="modal-title" id="configModalTitle">Configuration</div>
            <div class="modal-subtitle" id="configModalSlug"></div>
            <form method="POST" id="configForm" action="">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label" for="configJson">Configuration JSON</label>
                    <textarea class="form-input" id="configJson" name="config" rows="16" required
                        placeholder='{"key": "value"}'></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeConfigModal()">Annuler</button>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openConfigModal(id, label, slug) {
        var raw = document.getElementById('feature-config-' + id);
        var config = raw ? raw.textContent.trim() : '{}';

        document.getElementById('configForm').action = '/panel/admin/features/' + id + '/config';
        document.getElementById('configModalTitle').textContent = label;
        document.getElementById('configModalSlug').textContent = slug;
        document.getElementById('configJson').value = config;
        document.getElementById('configModal').classList.add('open');
    }

    function closeConfigModal() {
        document.getElementById('configModal').classList.remove('open');
    }

    document.getElementById('configModal').addEventListener('click', function(e) {
        if (e.target === this) closeConfigModal();
    });
</script>
@endpush
