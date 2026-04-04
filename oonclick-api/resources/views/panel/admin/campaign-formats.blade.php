@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Formats publicitaires')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'formats'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Formats pub</span>
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
    .checkbox-group { display:flex; flex-wrap:wrap; gap:10px; }
    .checkbox-item { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:500; color:#334155; cursor:pointer; }
    .checkbox-item input[type=checkbox] { width:16px; height:16px; accent-color:#0EA5E9; cursor:pointer; }
    .toggle-form { display:inline; margin:0; }
    .toggle-btn { padding:4px 10px; border-radius:6px; font-size:11px; font-weight:600; border:none; cursor:pointer; font-family:inherit; }
    .toggle-btn.active { background:#DCFCE7; color:#15803D; }
    .toggle-btn.inactive { background:#FEE2E2; color:#B91C1C; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <h1 class="page-title">Formats publicitaires</h1>
        <button class="btn-primary" onclick="openCreateModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouveau format
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
                <div class="kpi-icon sky">&#127760;</div>
                <span class="kpi-label">Total formats</span>
            </div>
            <div class="kpi-value">{{ $totalFormats }}</div>
            <div class="kpi-change neutral">Formats configurés</div>
        </div>
        <div class="kpi-card">
            <div class="accent green"></div>
            <div class="kpi-header">
                <div class="kpi-icon green">&#9989;</div>
                <span class="kpi-label">Actifs</span>
            </div>
            <div class="kpi-value">{{ $activeCount }}</div>
            <div class="kpi-change neutral">Disponibles pour les campagnes</div>
        </div>
        <div class="kpi-card">
            <div class="accent amber"></div>
            <div class="kpi-header">
                <div class="kpi-icon amber">&#9940;</div>
                <span class="kpi-label">Inactifs</span>
            </div>
            <div class="kpi-value">{{ $inactiveCount }}</div>
            <div class="kpi-change neutral">Non disponibles</div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Liste des formats</div>
        </div>
        @if($formats->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Ordre</th>
                    <th>Icône</th>
                    <th>Label</th>
                    <th>Slug</th>
                    <th>Multiplicateur</th>
                    <th>Durée déf.</th>
                    <th>Médias acceptés</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($formats as $format)
                <tr>
                    <td style="color:#94A3B8;font-size:12px;">{{ $format->sort_order }}</td>
                    <td style="font-size:22px;text-align:center;">{{ $format->icon ?? '—' }}</td>
                    <td>
                        <span style="font-weight:600;color:#0F172A;">{{ $format->label }}</span>
                        @if($format->description)
                            <div style="font-size:11px;color:#94A3B8;margin-top:2px;">{{ Str::limit($format->description, 45) }}</div>
                        @endif
                    </td>
                    <td><span style="font-family:monospace;font-size:12px;background:#F1F5F9;padding:2px 7px;border-radius:5px;color:#0F172A;">{{ $format->slug }}</span></td>
                    <td>
                        <span style="font-weight:700;color:{{ $format->multiplier > 1 ? '#15803D' : ($format->multiplier < 1 ? '#B91C1C' : '#1D4ED8') }}">
                            x{{ number_format($format->multiplier, 2) }}
                        </span>
                    </td>
                    <td>{{ $format->default_duration ? $format->default_duration . 's' : '—' }}</td>
                    <td>
                        @if($format->accepted_media)
                            @foreach($format->accepted_media as $media)
                                <span class="badge badge-info" style="margin-right:3px;">{{ $media }}</span>
                            @endforeach
                        @else
                            <span style="color:#94A3B8;">—</span>
                        @endif
                    </td>
                    <td>
                        <form class="toggle-form" method="POST" action="{{ route('panel.admin.campaign-formats.toggle', $format) }}">
                            @csrf
                            <button type="submit" class="toggle-btn {{ $format->is_active ? 'active' : 'inactive' }}">
                                {{ $format->is_active ? 'Actif' : 'Inactif' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <button class="action-btn primary btn-sm"
                            onclick="openEditModal({{ $format->id }})">
                            Modifier
                        </button>
                    </td>
                </tr>

                {{-- Hidden edit data for JS --}}
                <script type="application/json" id="format-data-{{ $format->id }}">{!! json_encode(['id' => $format->id, 'slug' => $format->slug, 'label' => $format->label, 'description' => $format->description ?? '', 'icon' => $format->icon ?? '', 'multiplier' => $format->multiplier, 'default_duration' => $format->default_duration, 'accepted_media' => $format->accepted_media ?? [], 'is_active' => $format->is_active, 'sort_order' => $format->sort_order ?? 0], JSON_UNESCAPED_UNICODE) !!}</script>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="icon">&#127760;</div>
            <p>Aucun format publicitaire configuré</p>
        </div>
        @endif
    </div>

    {{-- CREATE MODAL --}}
    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <div class="modal-title">Nouveau format publicitaire</div>
            <form method="POST" action="{{ route('panel.admin.campaign-formats.store') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="create_slug">Slug <span style="color:#EF4444">*</span></label>
                        <input class="form-input" type="text" id="create_slug" name="slug" placeholder="ex: video_30s" required pattern="[a-zA-Z0-9_\-]+" title="Lettres, chiffres, tirets et underscores uniquement">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="create_icon">Icône (emoji)</label>
                        <input class="form-input" type="text" id="create_icon" name="icon" placeholder="&#127760;" maxlength="10">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="create_label">Label <span style="color:#EF4444">*</span></label>
                    <input class="form-input" type="text" id="create_label" name="label" placeholder="ex: Vidéo 30 secondes" required maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label" for="create_description">Description</label>
                    <textarea class="form-input" id="create_description" name="description" rows="2" placeholder="Description optionnelle..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="create_multiplier">Multiplicateur <span style="color:#EF4444">*</span></label>
                        <input class="form-input" type="number" id="create_multiplier" name="multiplier" step="0.01" min="0.1" max="5.0" placeholder="1.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="create_default_duration">Durée par défaut (s)</label>
                        <input class="form-input" type="number" id="create_default_duration" name="default_duration" min="1" placeholder="ex: 30">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Médias acceptés <span style="color:#EF4444">*</span></label>
                    <div class="checkbox-group">
                        <label class="checkbox-item">
                            <input type="checkbox" name="accepted_media[]" value="video"> Vidéo
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="accepted_media[]" value="image"> Image
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="accepted_media[]" value="audio"> Audio
                        </label>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="create_sort_order">Ordre d'affichage</label>
                        <input class="form-input" type="number" id="create_sort_order" name="sort_order" min="0" value="0">
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;padding-top:22px;">
                        <label class="checkbox-item">
                            <input type="checkbox" name="is_active" value="1" checked> Actif
                        </label>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeCreateModal()">Annuler</button>
                    <button type="submit" class="btn-primary">Créer le format</button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-title">Modifier le format</div>
            <form method="POST" id="editForm" action="">
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_slug">Slug <span style="color:#EF4444">*</span></label>
                        <input class="form-input" type="text" id="edit_slug" name="slug" required pattern="[a-zA-Z0-9_\-]+" title="Lettres, chiffres, tirets et underscores uniquement">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_icon">Icône (emoji)</label>
                        <input class="form-input" type="text" id="edit_icon" name="icon" maxlength="10">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_label">Label <span style="color:#EF4444">*</span></label>
                    <input class="form-input" type="text" id="edit_label" name="label" required maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_description">Description</label>
                    <textarea class="form-input" id="edit_description" name="description" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_multiplier">Multiplicateur <span style="color:#EF4444">*</span></label>
                        <input class="form-input" type="number" id="edit_multiplier" name="multiplier" step="0.01" min="0.1" max="5.0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_default_duration">Durée par défaut (s)</label>
                        <input class="form-input" type="number" id="edit_default_duration" name="default_duration" min="1">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Médias acceptés <span style="color:#EF4444">*</span></label>
                    <div class="checkbox-group">
                        <label class="checkbox-item">
                            <input type="checkbox" id="edit_media_video" name="accepted_media[]" value="video"> Vidéo
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" id="edit_media_image" name="accepted_media[]" value="image"> Image
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" id="edit_media_audio" name="accepted_media[]" value="audio"> Audio
                        </label>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_sort_order">Ordre d'affichage</label>
                        <input class="form-input" type="number" id="edit_sort_order" name="sort_order" min="0">
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;padding-top:22px;">
                        <label class="checkbox-item">
                            <input type="checkbox" id="edit_is_active" name="is_active" value="1"> Actif
                        </label>
                    </div>
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
    function openCreateModal() {
        document.getElementById('createModal').classList.add('open');
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.remove('open');
    }

    function openEditModal(id) {
        const raw = document.getElementById('format-data-' + id);
        if (!raw) return;
        const data = JSON.parse(raw.textContent);

        document.getElementById('editForm').action = '/panel/admin/campaign-formats/' + id;
        document.getElementById('edit_slug').value            = data.slug;
        document.getElementById('edit_label').value           = data.label;
        document.getElementById('edit_description').value     = data.description;
        document.getElementById('edit_icon').value            = data.icon;
        document.getElementById('edit_multiplier').value      = data.multiplier;
        document.getElementById('edit_default_duration').value = data.default_duration ?? '';
        document.getElementById('edit_sort_order').value      = data.sort_order;
        document.getElementById('edit_is_active').checked     = data.is_active;

        // Reset media checkboxes
        ['video', 'image', 'audio'].forEach(function(m) {
            document.getElementById('edit_media_' + m).checked = data.accepted_media.indexOf(m) !== -1;
        });

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
