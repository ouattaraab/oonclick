@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Critères d\'audience')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'criteria'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Critères audience</span>
@endsection

@push('styles')
<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:100; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal { background:#fff; border-radius:16px; padding:28px; width:100%; max-width:540px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.15); }
    .modal-title { font-size:17px; font-weight:800; color:#0F172A; margin-bottom:18px; }
    .form-group { margin-bottom:16px; }
    .form-label { display:block; font-size:12px; font-weight:600; color:#64748B; margin-bottom:6px; }
    .form-input { width:100%; border:1px solid #E2E8F0; border-radius:10px; padding:9px 14px; font-size:13px; font-family:inherit; color:#0F172A; outline:none; transition:border-color .15s; background:#F8FAFC; }
    .form-input:focus { border-color:#0EA5E9; box-shadow:0 0 0 3px rgba(14,165,233,0.08); background:#fff; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
    .checkbox-group { display:flex; flex-wrap:wrap; gap:14px; }
    .checkbox-item { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:500; color:#334155; cursor:pointer; }
    .checkbox-item input[type=checkbox] { width:16px; height:16px; accent-color:#0EA5E9; cursor:pointer; }
    .toggle-form { display:inline; margin:0; }
    .toggle-btn { padding:4px 10px; border-radius:6px; font-size:11px; font-weight:600; border:none; cursor:pointer; font-family:inherit; }
    .toggle-btn.active { background:#DCFCE7; color:#15803D; }
    .toggle-btn.inactive { background:#FEE2E2; color:#B91C1C; }
    .options-hint { font-size:11px; color:#94A3B8; margin-top:4px; }
    .builtin-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; background:#F1F5F9; color:#475569; padding:2px 8px; border-radius:20px; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <h1 class="page-title">Critères d'audience</h1>
        <button class="btn-primary" onclick="openCreateModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouveau critère
        </button>
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
                <div class="kpi-icon sky">&#127919;</div>
                <span class="kpi-label">Total critères</span>
            </div>
            <div class="kpi-value">{{ $totalCriteria }}</div>
            <div class="kpi-change neutral">Critères configurés</div>
        </div>
        <div class="kpi-card">
            <div class="accent green"></div>
            <div class="kpi-header">
                <div class="kpi-icon green">&#9989;</div>
                <span class="kpi-label">Actifs</span>
            </div>
            <div class="kpi-value">{{ $activeCount }}</div>
            <div class="kpi-change neutral">Disponibles pour le ciblage</div>
        </div>
        <div class="kpi-card">
            <div class="accent purple"></div>
            <div class="kpi-header">
                <div class="kpi-icon purple">&#128100;</div>
                <span class="kpi-label">Requis pour profil</span>
            </div>
            <div class="kpi-value">{{ $requiredCount }}</div>
            <div class="kpi-change neutral">Champs obligatoires</div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Liste des critères</div>
        </div>
        @if($criteria->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Ordre</th>
                    <th>Label</th>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Catégorie</th>
                    <th style="text-align:center">Requis</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($criteria as $criterion)
                <tr>
                    <td style="color:#94A3B8;font-size:12px;">{{ $criterion->sort_order }}</td>
                    <td>
                        <span style="font-weight:600;color:#0F172A;">{{ $criterion->label }}</span>
                        @if($criterion->isBuiltin())
                            <div style="margin-top:3px;">
                                <span class="builtin-badge">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                    Intégré
                                </span>
                            </div>
                        @endif
                    </td>
                    <td><span style="font-family:monospace;font-size:12px;background:#F1F5F9;padding:2px 7px;border-radius:5px;color:#0F172A;">{{ $criterion->name }}</span></td>
                    <td>
                        @php
                            $typeColor = match($criterion->type) {
                                'select', 'multiselect' => 'badge-adv',
                                'boolean'               => 'badge-active',
                                'number', 'range'       => 'badge-info',
                                default                 => 'badge-gray',
                            };
                        @endphp
                        <span class="badge {{ $typeColor }}">{{ $criterion->type }}</span>
                    </td>
                    <td>
                        @if($criterion->category)
                            <span class="badge badge-gray">{{ $criterion->category }}</span>
                        @else
                            <span style="color:#94A3B8;">—</span>
                        @endif
                    </td>
                    <td style="text-align:center">
                        @if($criterion->is_required_for_profile)
                            <span style="font-size:16px" title="Requis pour le profil">&#9989;</span>
                        @else
                            <span style="font-size:16px;opacity:0.2" title="Optionnel">&#9711;</span>
                        @endif
                    </td>
                    <td>
                        <form class="toggle-form" method="POST" action="{{ route('panel.admin.audience-criteria.toggle', $criterion) }}">
                            @csrf
                            <button type="submit" class="toggle-btn {{ $criterion->is_active ? 'active' : 'inactive' }}">
                                {{ $criterion->is_active ? 'Actif' : 'Inactif' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <button class="action-btn primary btn-sm"
                            onclick="openEditModal({{ $criterion->id }})">
                            Modifier
                        </button>
                    </td>
                </tr>

                {{-- Hidden edit data for JS --}}
                <script type="application/json" id="criterion-data-{{ $criterion->id }}">
                {
                    "id": {{ $criterion->id }},
                    "name": "{{ addslashes($criterion->name) }}",
                    "label": "{{ addslashes($criterion->label) }}",
                    "type": "{{ $criterion->type }}",
                    "options": @json($criterion->options ?? []),
                    "category": "{{ addslashes($criterion->category ?? '') }}",
                    "is_active": {{ $criterion->is_active ? 'true' : 'false' }},
                    "is_required_for_profile": {{ $criterion->is_required_for_profile ? 'true' : 'false' }},
                    "sort_order": {{ $criterion->sort_order ?? 0 }},
                    "is_builtin": {{ $criterion->isBuiltin() ? 'true' : 'false' }}
                }
                </script>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="icon">&#127919;</div>
            <p>Aucun critère d'audience configuré</p>
        </div>
        @endif
    </div>

    {{-- CREATE MODAL --}}
    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <div class="modal-title">Nouveau critère d'audience</div>
            <form method="POST" action="{{ route('panel.admin.audience-criteria.store') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="create_name">Nom (identifiant) <span style="color:#EF4444">*</span></label>
                        <input class="form-input" type="text" id="create_name" name="name" placeholder="ex: age_range" required pattern="[a-zA-Z0-9_\-]+" title="Lettres, chiffres, tirets et underscores uniquement">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="create_type">Type <span style="color:#EF4444">*</span></label>
                        <select class="form-input" id="create_type" name="type" required onchange="toggleOptionsField('create')">
                            <option value="text">text</option>
                            <option value="select">select</option>
                            <option value="multiselect">multiselect</option>
                            <option value="number">number</option>
                            <option value="range">range</option>
                            <option value="boolean">boolean</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="create_label">Label (affiché) <span style="color:#EF4444">*</span></label>
                    <input class="form-input" type="text" id="create_label" name="label" placeholder="ex: Tranche d'âge" required maxlength="100">
                </div>
                <div class="form-group" id="create_options_group" style="display:none">
                    <label class="form-label" for="create_options_raw">Options <span style="color:#EF4444">*</span></label>
                    <textarea class="form-input" id="create_options_raw" name="options_raw" rows="4" placeholder="Une option par ligne :&#10;18-24&#10;25-34&#10;35-44&#10;45+"></textarea>
                    <div class="options-hint">Saisir une valeur par ligne. Elles seront converties en tableau JSON.</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="create_category">Catégorie</label>
                        <input class="form-input" type="text" id="create_category" name="category" placeholder="ex: Démographie" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="create_sort_order">Ordre d'affichage</label>
                        <input class="form-input" type="number" id="create_sort_order" name="sort_order" min="0" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <label class="checkbox-item">
                            <input type="checkbox" name="is_active" value="1" checked> Actif
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="is_required_for_profile" value="1"> Requis pour profil
                        </label>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeCreateModal()">Annuler</button>
                    <button type="submit" class="btn-primary" onclick="prepareOptionsField('create')">Créer le critère</button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-title">Modifier le critère</div>
            <form method="POST" id="editForm" action="">
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_name">Nom (identifiant) <span style="color:#EF4444">*</span></label>
                        <input class="form-input" type="text" id="edit_name" name="name" required pattern="[a-zA-Z0-9_\-]+" title="Lettres, chiffres, tirets et underscores uniquement">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_type">Type <span style="color:#EF4444">*</span></label>
                        <select class="form-input" id="edit_type" name="type" required onchange="toggleOptionsField('edit')">
                            <option value="text">text</option>
                            <option value="select">select</option>
                            <option value="multiselect">multiselect</option>
                            <option value="number">number</option>
                            <option value="range">range</option>
                            <option value="boolean">boolean</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_label">Label (affiché) <span style="color:#EF4444">*</span></label>
                    <input class="form-input" type="text" id="edit_label" name="label" required maxlength="100">
                </div>
                <div class="form-group" id="edit_options_group" style="display:none">
                    <label class="form-label" for="edit_options_raw">Options <span style="color:#EF4444">*</span></label>
                    <textarea class="form-input" id="edit_options_raw" name="options_raw" rows="4" placeholder="Une option par ligne"></textarea>
                    <div class="options-hint">Saisir une valeur par ligne. Elles seront converties en tableau JSON.</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_category">Catégorie</label>
                        <input class="form-input" type="text" id="edit_category" name="category" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_sort_order">Ordre d'affichage</label>
                        <input class="form-input" type="number" id="edit_sort_order" name="sort_order" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <label class="checkbox-item">
                            <input type="checkbox" id="edit_is_active" name="is_active" value="1"> Actif
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" id="edit_is_required" name="is_required_for_profile" value="1"> Requis pour profil
                        </label>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" class="btn-primary" onclick="prepareOptionsField('edit')">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Show/hide options textarea based on type
    function toggleOptionsField(prefix) {
        var type = document.getElementById(prefix + '_type').value;
        var group = document.getElementById(prefix + '_options_group');
        group.style.display = (type === 'select' || type === 'multiselect') ? 'block' : 'none';
    }

    // Convert textarea (one per line) to hidden inputs array before submit
    function prepareOptionsField(prefix) {
        var type = document.getElementById(prefix + '_type').value;
        if (type !== 'select' && type !== 'multiselect') return;

        var raw = document.getElementById(prefix + '_options_raw').value;
        var lines = raw.split('\n').map(function(l) { return l.trim(); }).filter(function(l) { return l !== ''; });

        var form = prefix === 'create'
            ? document.getElementById(prefix + '_options_raw').closest('form')
            : document.getElementById('editForm');

        // Remove previous hidden options inputs
        form.querySelectorAll('input[name="options[]"]').forEach(function(el) { el.remove(); });

        lines.forEach(function(val) {
            var input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'options[]';
            input.value = val;
            form.appendChild(input);
        });
    }

    function openCreateModal() {
        document.getElementById('createModal').classList.add('open');
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.remove('open');
    }

    function openEditModal(id) {
        var raw = document.getElementById('criterion-data-' + id);
        if (!raw) return;
        var data = JSON.parse(raw.textContent);

        document.getElementById('editForm').action = '/panel/admin/audience-criteria/' + id;
        document.getElementById('edit_name').value       = data.name;
        document.getElementById('edit_label').value      = data.label;
        document.getElementById('edit_category').value   = data.category;
        document.getElementById('edit_sort_order').value = data.sort_order;
        document.getElementById('edit_is_active').checked  = data.is_active;
        document.getElementById('edit_is_required').checked = data.is_required_for_profile;

        // Set type
        document.getElementById('edit_type').value = data.type;
        toggleOptionsField('edit');

        // Fill options textarea
        if (data.options && data.options.length > 0) {
            document.getElementById('edit_options_raw').value = data.options.join('\n');
        } else {
            document.getElementById('edit_options_raw').value = '';
        }

        document.getElementById('editModal').classList.add('open');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('open');
    }

    document.getElementById('createModal').addEventListener('click', function(e) {
        if (e.target === this) closeCreateModal();
    });

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
</script>
@endpush
