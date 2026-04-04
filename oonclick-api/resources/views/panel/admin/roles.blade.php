@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Rôles & Permissions')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'roles'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Rôles & Permissions</span>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">Rôles &amp; Permissions</h1>
    </div>

    {{-- KPI STRIP --}}
    <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="kpi-card">
            <div class="accent sky"></div>
            <div class="kpi-header">
                <div class="kpi-icon sky">🛡️</div>
                <span class="kpi-label">Total rôles</span>
            </div>
            <div class="kpi-value">{{ $totalRoles }}</div>
            <div class="kpi-change neutral">Rôles configurés</div>
        </div>
        <div class="kpi-card">
            <div class="accent purple"></div>
            <div class="kpi-header">
                <div class="kpi-icon purple">🔑</div>
                <span class="kpi-label">Total permissions</span>
            </div>
            <div class="kpi-value">{{ $totalPerms }}</div>
            <div class="kpi-change neutral">Permissions définies</div>
        </div>
        <div class="kpi-card">
            <div class="accent green"></div>
            <div class="kpi-header">
                <div class="kpi-icon green">👤</div>
                <span class="kpi-label">Utilisateurs assignés</span>
            </div>
            <div class="kpi-value">{{ $roles->sum(fn($r) => $r->users->count()) }}</div>
            <div class="kpi-change neutral">Total avec un rôle Spatie</div>
        </div>
    </div>

    {{-- ROLES TABLE --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Rôles Spatie</div>
        </div>
        @if($roles->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Rôle</th>
                    <th>Guard</th>
                    <th>Permissions</th>
                    <th>Utilisateurs</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                <tr>
                    <td>
                        @php
                            $roleBadge = match($role->name) {
                                'admin'      => 'badge-admin',
                                'advertiser' => 'badge-adv',
                                'subscriber' => 'badge-sub',
                                default      => 'badge-info',
                            };
                        @endphp
                        <span class="badge {{ $roleBadge }}">{{ ucfirst($role->name) }}</span>
                    </td>
                    <td><span class="badge badge-gray" style="font-family:monospace;font-size:10px">{{ $role->guard_name }}</span></td>
                    <td>
                        @if($role->permissions->count() > 0)
                            <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:300px">
                                @foreach($role->permissions->take(5) as $permission)
                                    <span class="badge badge-info" style="font-size:9px;padding:2px 6px">{{ $permission->name }}</span>
                                @endforeach
                                @if($role->permissions->count() > 5)
                                    <span class="badge badge-gray" style="font-size:9px;padding:2px 6px">+{{ $role->permissions->count() - 5 }} autres</span>
                                @endif
                            </div>
                        @else
                            <span style="color:#CBD5E1;font-size:12px">Aucune permission directe</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-weight:700;color:#0F172A;font-size:16px">{{ $role->users->count() }}</span>
                            <span style="font-size:11px;color:#94A3B8">utilisateurs</span>
                        </div>
                    </td>
                    <td style="color:#94A3B8;font-size:12px">{{ $role->created_at?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        <button class="action-btn primary btn-sm" onclick="togglePerms('perms-{{ $role->id }}')">Détails</button>
                    </td>
                </tr>
                @if($role->permissions->count() > 0)
                <tr id="perms-{{ $role->id }}" style="display:none">
                    <td colspan="6" style="background:#F8FAFC;padding:12px 16px">
                        <div style="font-size:11px;font-weight:700;color:#64748B;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px">Toutes les permissions du rôle {{ ucfirst($role->name) }}</div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px">
                            @foreach($role->permissions as $permission)
                                <span class="badge badge-info" style="font-family:monospace;font-size:10px">{{ $permission->name }}</span>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="icon">🛡️</div>
            <p>Aucun rôle configuré dans Spatie</p>
        </div>
        @endif
    </div>

    {{-- ALL PERMISSIONS --}}
    @if($totalPerms > 0)
    <div class="card">
        <div class="card-header">
            <div class="card-title">Toutes les permissions ({{ $totalPerms }})</div>
        </div>
        <div class="card-body" style="display:flex;flex-wrap:wrap;gap:6px">
            @foreach(\Spatie\Permission\Models\Permission::orderBy('name')->get() as $perm)
                <span class="badge badge-gray" style="font-family:monospace;font-size:11px">{{ $perm->name }}</span>
            @endforeach
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
function togglePerms(id) {
    var row = document.getElementById(id);
    if (row) {
        row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
    }
}
</script>
@endpush
