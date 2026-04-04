@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Envoyer une notification')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'notifications'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Notifications</span>
@endsection

@push('styles')
<style>
    .notif-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        border: 1px solid #E2E8F0;
        max-width: 680px;
    }
    .form-group { margin-bottom: 20px; }
    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 7px;
    }
    .form-input, .form-select, .form-textarea {
        width: 100%;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 14px;
        font-family: inherit;
        color: #0F172A;
        outline: none;
        background: #F8FAFC;
        transition: border-color .15s, box-shadow .15s;
        box-sizing: border-box;
    }
    .form-textarea { resize: vertical; min-height: 100px; }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color: #0EA5E9;
        box-shadow: 0 0 0 3px rgba(14,165,233,0.10);
        background: #fff;
    }
    .form-hint {
        font-size: 12px;
        color: #94A3B8;
        margin-top: 5px;
    }
    .char-count {
        font-size: 11px;
        color: #94A3B8;
        float: right;
    }
    .target-pills {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .target-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1.5px solid #E2E8F0;
        border-radius: 10px;
        padding: 8px 14px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        background: #F8FAFC;
        transition: all .15s;
        flex: 1;
        min-width: 120px;
    }
    .target-pill:has(input:checked) {
        border-color: #0EA5E9;
        background: #EFF9FF;
        color: #0369A1;
    }
    .target-pill input[type=radio] { display: none; }
    .target-pill-icon { font-size: 18px; }
    #user-id-row { display: none; }
    .submit-btn {
        background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 12px 28px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: opacity .15s;
    }
    .submit-btn:hover { opacity: .9; }
    .preview-box {
        background: #0F172A;
        border-radius: 14px;
        padding: 16px 18px;
        margin-top: 24px;
    }
    .preview-title {
        font-size: 12px;
        font-weight: 700;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 12px;
    }
    .preview-notification {
        background: #1E293B;
        border-radius: 12px;
        padding: 14px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    .preview-icon {
        width: 36px;
        height: 36px;
        background: #0EA5E9;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .preview-content {}
    .preview-notif-title {
        font-size: 13px;
        font-weight: 700;
        color: #F1F5F9;
        margin-bottom: 3px;
    }
    .preview-notif-body {
        font-size: 12px;
        color: #94A3B8;
        line-height: 1.4;
    }
    .stats-strip {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        margin-bottom: 28px;
    }
    .stat-card {
        background: #fff;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 16px 18px;
    }
    .stat-label { font-size: 11px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.05em; }
    .stat-value { font-size: 24px; font-weight: 800; color: #0F172A; margin-top: 4px; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <h1 class="page-title">Notifications push</h1>
    </div>

    @if(session('success'))
    <div style="background:#DCFCE7;color:#15803D;border:1px solid #BBF7D0;border-radius:10px;padding:12px 18px;margin-bottom:24px;font-size:13px;font-weight:600;">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div style="background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;padding:12px 18px;margin-bottom:24px;font-size:13px;font-weight:600;">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    {{-- Stats strip --}}
    <div class="stats-strip">
        <div class="stat-card">
            <div class="stat-label">Tokens actifs</div>
            <div class="stat-value">{{ number_format($totalTokens) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Abonnés connectés</div>
            <div class="stat-value">{{ number_format($subscriberTokens) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Annonceurs connectés</div>
            <div class="stat-value">{{ number_format($advertiserTokens) }}</div>
        </div>
    </div>

    <div class="notif-card">
        <form method="POST" action="{{ route('panel.admin.notifications.dispatch') }}" id="notif-form">
            @csrf

            {{-- Title --}}
            <div class="form-group">
                <label class="form-label" for="title">
                    Titre <span style="color:#EF4444">*</span>
                </label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    class="form-input"
                    placeholder="Ex : Nouvelle fonctionnalité disponible !"
                    maxlength="100"
                    value="{{ old('title') }}"
                    required
                    oninput="document.getElementById('title-count').textContent = this.value.length"
                >
                <span class="char-count"><span id="title-count">0</span>/100</span>
                <div class="form-hint">Texte affiché en gras dans la notification.</div>
            </div>

            {{-- Body --}}
            <div class="form-group" style="clear:both">
                <label class="form-label" for="body">
                    Message <span style="color:#EF4444">*</span>
                </label>
                <textarea
                    id="body"
                    name="body"
                    class="form-textarea"
                    placeholder="Ex : Découvrez les nouvelles campagnes disponibles et gagnez plus de FCFA !"
                    maxlength="500"
                    required
                    oninput="document.getElementById('body-count').textContent = this.value.length"
                >{{ old('body') }}</textarea>
                <span class="char-count"><span id="body-count">0</span>/500</span>
                <div class="form-hint" style="clear:both">Corps de la notification visible après le titre.</div>
            </div>

            {{-- Target --}}
            <div class="form-group" style="clear:both">
                <label class="form-label">
                    Destinataires <span style="color:#EF4444">*</span>
                </label>
                <div class="target-pills">
                    <label class="target-pill">
                        <input type="radio" name="target" value="all" {{ old('target', 'all') === 'all' ? 'checked' : '' }} onchange="toggleUserField(this.value)">
                        <span class="target-pill-icon">🌐</span>
                        <div>
                            <div>Tous</div>
                            <div style="font-size:11px;font-weight:400;color:#94A3B8">Abonnés + annonceurs</div>
                        </div>
                    </label>
                    <label class="target-pill">
                        <input type="radio" name="target" value="subscribers" {{ old('target') === 'subscribers' ? 'checked' : '' }} onchange="toggleUserField(this.value)">
                        <span class="target-pill-icon">👥</span>
                        <div>
                            <div>Abonnés</div>
                            <div style="font-size:11px;font-weight:400;color:#94A3B8">Visionneurs de pubs</div>
                        </div>
                    </label>
                    <label class="target-pill">
                        <input type="radio" name="target" value="advertisers" {{ old('target') === 'advertisers' ? 'checked' : '' }} onchange="toggleUserField(this.value)">
                        <span class="target-pill-icon">📢</span>
                        <div>
                            <div>Annonceurs</div>
                            <div style="font-size:11px;font-weight:400;color:#94A3B8">Créateurs de campagnes</div>
                        </div>
                    </label>
                    <label class="target-pill">
                        <input type="radio" name="target" value="user" {{ old('target') === 'user' ? 'checked' : '' }} onchange="toggleUserField(this.value)">
                        <span class="target-pill-icon">👤</span>
                        <div>
                            <div>Utilisateur</div>
                            <div style="font-size:11px;font-weight:400;color:#94A3B8">Un seul utilisateur</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Specific user ID (shown only when target = user) --}}
            <div class="form-group" id="user-id-row">
                <label class="form-label" for="user_id">ID utilisateur</label>
                <input
                    type="number"
                    id="user_id"
                    name="user_id"
                    class="form-input"
                    placeholder="Ex : 42"
                    value="{{ old('user_id') }}"
                    min="1"
                    style="max-width:200px"
                >
                <div class="form-hint">Identifiant numérique de l'utilisateur dans la base de données.</div>
            </div>

            {{-- Preview --}}
            <div class="preview-box">
                <div class="preview-title" style="color:#64748B">Aperçu</div>
                <div class="preview-notification">
                    <div class="preview-icon">🔔</div>
                    <div class="preview-content">
                        <div class="preview-notif-title" id="preview-title">Titre de la notification</div>
                        <div class="preview-notif-body" id="preview-body">Le message apparaîtra ici...</div>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div style="margin-top:28px">
                <button type="submit" class="submit-btn">
                    Envoyer la notification
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    // Live preview
    document.getElementById('title').addEventListener('input', function () {
        document.getElementById('preview-title').textContent = this.value || 'Titre de la notification';
    });
    document.getElementById('body').addEventListener('input', function () {
        document.getElementById('preview-body').textContent = this.value || 'Le message apparaîtra ici...';
    });

    // Show/hide user_id field
    function toggleUserField(value) {
        const row = document.getElementById('user-id-row');
        const input = document.getElementById('user_id');
        if (value === 'user') {
            row.style.display = 'block';
            input.required = true;
        } else {
            row.style.display = 'none';
            input.required = false;
        }
    }

    // Initialise on page load (in case of validation error redirect)
    (function () {
        const checked = document.querySelector('input[name=target]:checked');
        if (checked) toggleUserField(checked.value);
    })();
</script>
@endpush
