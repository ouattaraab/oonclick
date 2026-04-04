@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Configuration plateforme')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'config'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Configuration</span>
@endsection

@push('styles')
<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:100; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal { background:#fff; border-radius:16px; padding:28px; width:100%; max-width:480px; box-shadow:0 20px 60px rgba(0,0,0,0.15); }
    .modal-title { font-size:17px; font-weight:800; color:#0F172A; margin-bottom:18px; }
    .form-group { margin-bottom:16px; }
    .form-label { display:block; font-size:12px; font-weight:600; color:#64748B; margin-bottom:6px; }
    .form-input { width:100%; border:1px solid #E2E8F0; border-radius:10px; padding:9px 14px; font-size:13px; font-family:inherit; color:#0F172A; outline:none; transition:border-color .15s; background:#F8FAFC; }
    .form-input:focus { border-color:#0EA5E9; box-shadow:0 0 0 3px rgba(14,165,233,0.08); background:#fff; }
    .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
    .key-mono { font-family:monospace; font-size:12px; color:#0F172A; background:#F1F5F9; padding:2px 7px; border-radius:5px; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <h1 class="page-title">Configuration plateforme</h1>
    </div>

    @if(session('success'))
    <div style="background:#DCFCE7;color:#15803D;border:1px solid #BBF7D0;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        ✓ {{ session('success') }}
    </div>
    @endif

    {{-- KPI STRIP --}}
    <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="kpi-card">
            <div class="accent sky"></div>
            <div class="kpi-header">
                <div class="kpi-icon sky">⚙️</div>
                <span class="kpi-label">Total paramètres</span>
            </div>
            <div class="kpi-value">{{ $totalConfigs }}</div>
            <div class="kpi-change neutral">Clés configurées</div>
        </div>
        <div class="kpi-card">
            <div class="accent green"></div>
            <div class="kpi-header">
                <div class="kpi-icon green">👁️</div>
                <span class="kpi-label">Paramètres publics</span>
            </div>
            <div class="kpi-value">{{ $publicCount }}</div>
            <div class="kpi-change neutral">Exposés à l'API publique</div>
        </div>
        <div class="kpi-card">
            <div class="accent amber"></div>
            <div class="kpi-header">
                <div class="kpi-icon amber">🔒</div>
                <span class="kpi-label">Paramètres privés</span>
            </div>
            <div class="kpi-value">{{ $totalConfigs - $publicCount }}</div>
            <div class="kpi-change neutral">Internes uniquement</div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Paramètres de configuration</div>
        </div>
        @if($configs->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Clé</th>
                    <th>Valeur</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Public</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($configs as $config)
                <tr>
                    <td><span class="key-mono">{{ $config->key }}</span></td>
                    <td style="max-width:200px">
                        <span style="font-size:12px;color:#334155;font-family:monospace;word-break:break-all">
                            {{ Str::limit($config->value ?? '—', 50) }}
                        </span>
                    </td>
                    <td>
                        @php
                            $typeColor = match($config->type) {
                                'integer' => 'badge-info',
                                'boolean' => 'badge-active',
                                'json'    => 'badge-adv',
                                default   => 'badge-gray',
                            };
                        @endphp
                        <span class="badge {{ $typeColor }}">{{ ucfirst($config->type ?? 'string') }}</span>
                    </td>
                    <td style="max-width:240px">
                        <span style="font-size:12px;color:#64748B">{{ Str::limit($config->description ?? '—', 55) }}</span>
                    </td>
                    <td style="text-align:center">
                        @if($config->is_public)
                            <span style="font-size:16px" title="Public">👁️</span>
                        @else
                            <span style="font-size:16px;opacity:0.25" title="Privé">🔒</span>
                        @endif
                    </td>
                    <td>
                        <button class="action-btn primary btn-sm"
                            onclick="openEditModal({{ $config->id }}, '{{ addslashes($config->key) }}', '{{ addslashes($config->value ?? '') }}', '{{ addslashes($config->description ?? '') }}')">
                            Modifier
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($configs->hasPages())
        <div class="pagination">
            <span>Affichage de {{ $configs->firstItem() }} à {{ $configs->lastItem() }} sur {{ $configs->total() }}</span>
            <div>{{ $configs->links('pagination::simple-default') }}</div>
        </div>
        @endif
        @else
        <div class="empty-state">
            <div class="icon">⚙️</div>
            <p>Aucun paramètre configuré</p>
        </div>
        @endif
    </div>

    {{-- EDIT MODAL --}}
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-title">Modifier le paramètre</div>
            <form method="POST" id="editForm" action="">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Clé</label>
                    <div id="modalKey" class="key-mono" style="display:inline-block;margin-bottom:4px"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <p id="modalDesc" style="font-size:12px;color:#64748B;line-height:1.5"></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="configValue">Nouvelle valeur</label>
                    <textarea class="form-input" id="configValue" name="value" rows="3" required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openEditModal(id, key, value, desc) {
        document.getElementById('editForm').action = '/panel/admin/config/' + id;
        document.getElementById('modalKey').textContent = key;
        document.getElementById('modalDesc').textContent = desc || '—';
        document.getElementById('configValue').value = value;
        document.getElementById('editModal').classList.add('open');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('open');
    }

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
</script>
@endpush
