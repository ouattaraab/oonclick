@extends('layouts.panel-advertiser', ['walletBalance' => $walletBalance])

@section('title', 'Nouvelle campagne')
@section('topbar-title', 'Nouvelle campagne')

@section('sidebar-nav')
    @include('panel.advertiser._sidebar', ['active' => 'campaigns'])
@endsection

@push('styles')
<style>
    /* ── Wizard progress bar ── */
    .wizard-wrap { max-width:860px; margin:0 auto; }
    .wizard-progress { display:flex; align-items:center; margin-bottom:32px; }
    .wp-step { display:flex; flex-direction:column; align-items:center; flex:1; position:relative; }
    .wp-step:not(:last-child)::after {
        content:''; position:absolute; top:18px; left:50%; width:100%;
        height:2px; background:#E5E9F0; z-index:0;
    }
    .wp-step.done:not(:last-child)::after { background:linear-gradient(90deg,#3B82F6,#06B6D4); }
    .wp-dot {
        width:36px; height:36px; border-radius:50%; border:2px solid #E5E9F0;
        background:#fff; color:#94A3B8; font-size:13px; font-weight:800;
        display:flex; align-items:center; justify-content:center; z-index:1;
        transition:all .25s;
    }
    .wp-step.active .wp-dot { border-color:#3B82F6; background:linear-gradient(135deg,#3B82F6,#2563EB); color:#fff; box-shadow:0 3px 10px rgba(59,130,246,0.35); }
    .wp-step.done .wp-dot  { border-color:#10B981; background:linear-gradient(135deg,#10B981,#059669); color:#fff; }
    .wp-label { font-size:10px; font-weight:700; color:#94A3B8; margin-top:6px; text-align:center; white-space:nowrap; text-transform:uppercase; letter-spacing:0.4px; }
    .wp-step.active .wp-label { color:#3B82F6; }
    .wp-step.done  .wp-label { color:#10B981; }

    /* ── Wizard steps ── */
    .wizard-step { display:none; }
    .wizard-step.active { display:block; }

    /* ── Format selection cards ── */
    .format-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
    .format-card { position:relative; cursor:pointer; }
    .format-card input[type=radio] { position:absolute; opacity:0; width:0; height:0; }
    .format-card-inner {
        border:2px solid #E5E9F0; border-radius:14px; padding:18px 14px;
        text-align:center; transition:all .2s; background:#fff; height:100%;
    }
    .format-card:hover .format-card-inner { border-color:#93C5FD; background:#F8FAFF; }
    .format-card input:checked ~ .format-card-inner {
        border-color:#3B82F6; background:linear-gradient(135deg,#EFF6FF,#F0FDFF);
        box-shadow:0 4px 14px rgba(59,130,246,0.15);
    }
    .fc-icon { font-size:28px; margin-bottom:8px; }
    .fc-name { font-size:13px; font-weight:800; color:#0B1929; margin-bottom:4px; }
    .fc-desc { font-size:10px; color:#64748B; font-weight:600; line-height:1.4; margin-bottom:8px; }
    .fc-badge {
        display:inline-block; padding:3px 9px; border-radius:20px; font-size:10px; font-weight:800;
        background:linear-gradient(135deg,#3B82F6,#06B6D4); color:#fff;
    }
    .format-card input:checked ~ .format-card-inner .fc-badge { background:linear-gradient(135deg,#10B981,#059669); }

    /* ── Content fields ── */
    .content-fields { display:none; }
    .content-fields.visible { display:block; }

    /* ── Drag & drop upload ── */
    .upload-zone {
        border:2px dashed #CBD5E1; border-radius:12px; padding:28px 20px;
        text-align:center; cursor:pointer; transition:all .2s; background:#FAFCFF;
        position:relative;
    }
    .upload-zone:hover, .upload-zone.dragover { border-color:#3B82F6; background:#EFF6FF; }
    .upload-zone input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; }
    .uz-icon { font-size:28px; margin-bottom:6px; }
    .uz-text { font-size:12px; font-weight:700; color:#0B1929; margin-bottom:3px; }
    .uz-hint { font-size:10px; color:#94A3B8; font-weight:600; }
    .upload-preview { margin-top:10px; display:none; align-items:center; gap:10px; padding:10px 14px; background:#F0FDF4; border-radius:8px; border:1px solid #D1FAE5; }
    .upload-preview.visible { display:flex; }
    .preview-thumb { width:50px; height:36px; object-fit:cover; border-radius:6px; display:none; }
    .preview-filename { font-size:12px; font-weight:700; color:#065F46; }
    .preview-filesize { font-size:10px; color:#6EE7B7; font-weight:600; }
    .preview-remove { margin-left:auto; cursor:pointer; color:#EF4444; font-size:16px; font-weight:800; line-height:1; }

    /* ── Quiz builder ── */
    .quiz-section { margin-top:20px; display:none; }
    .quiz-section.visible { display:block; }
    .quiz-section-title { font-size:13px; font-weight:800; color:#0B1929; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
    .quiz-question-block { background:#F8FAFC; border:1px solid #E5E9F0; border-radius:12px; padding:16px; margin-bottom:12px; position:relative; }
    .quiz-q-label { font-size:10px; font-weight:800; color:#64748B; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:8px; }
    .quiz-remove { position:absolute; top:12px; right:12px; background:none; border:none; color:#EF4444; font-size:14px; cursor:pointer; font-weight:800; padding:0; }
    .quiz-answers { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-top:10px; }
    .btn-add-question { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; border:2px dashed #3B82F6; background:transparent; color:#3B82F6; font-size:12px; font-weight:700; cursor:pointer; font-family:inherit; transition:all .2s; }
    .btn-add-question:hover { background:#EFF6FF; }

    /* ── Step 2: Targeting ── */
    .targeting-layout { display:grid; grid-template-columns:1fr 280px; gap:20px; }
    .section-title { font-size:13px; font-weight:800; color:#0B1929; margin-bottom:12px; }

    /* Checkbox cards */
    .check-cards { display:flex; gap:8px; flex-wrap:wrap; }
    .check-card { position:relative; cursor:pointer; }
    .check-card input { position:absolute; opacity:0; width:0; height:0; }
    .check-card-inner { border:2px solid #E5E9F0; border-radius:10px; padding:8px 16px; font-size:12px; font-weight:700; color:#475569; transition:all .2s; background:#fff; white-space:nowrap; }
    .check-card:hover .check-card-inner { border-color:#93C5FD; color:#0B1929; }
    .check-card input:checked ~ .check-card-inner { border-color:#3B82F6; background:#EFF6FF; color:#1E40AF; }

    /* Cities grid */
    .city-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; }
    .city-check { display:flex; align-items:center; gap:6px; padding:7px 10px; border-radius:8px; cursor:pointer; border:1px solid #E5E9F0; background:#fff; transition:all .15s; }
    .city-check:hover { border-color:#93C5FD; background:#F8FAFF; }
    .city-check input { accent-color:#3B82F6; }
    .city-check span { font-size:12px; font-weight:600; color:#334155; }
    .city-check.selected { border-color:#3B82F6; background:#EFF6FF; }

    /* Interest tags */
    .interest-tags { display:flex; flex-wrap:wrap; gap:6px; }
    .interest-tag {
        padding:6px 13px; border-radius:20px; border:2px solid #E5E9F0;
        font-size:11px; font-weight:700; color:#64748B; cursor:pointer;
        background:#fff; transition:all .2s;
    }
    .interest-tag:hover { border-color:#93C5FD; color:#1E40AF; }
    .interest-tag.selected { border-color:#3B82F6; background:#EFF6FF; color:#1E40AF; }

    /* Age range */
    .age-range-wrap { display:flex; align-items:center; gap:12px; }
    .age-range-wrap input[type=number] { width:70px; text-align:center; }
    .age-sep { font-size:12px; font-weight:700; color:#94A3B8; }
    .age-display { font-size:12px; font-weight:700; color:#3B82F6; }

    /* Audience estimation panel */
    .audience-panel {
        background:linear-gradient(135deg,#0F2744,#1E3A5F); border-radius:16px;
        padding:20px; color:#fff; position:sticky; top:80px;
    }
    .ap-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.6px; opacity:0.7; margin-bottom:16px; }
    .ap-count { font-size:32px; font-weight:900; margin-bottom:4px; }
    .ap-count span { font-size:14px; font-weight:600; opacity:0.6; }
    .ap-pct { font-size:13px; font-weight:700; opacity:0.75; margin-bottom:16px; }
    .ap-bar { height:6px; background:rgba(255,255,255,0.15); border-radius:3px; overflow:hidden; margin-bottom:20px; }
    .ap-fill { height:100%; border-radius:3px; background:linear-gradient(90deg,#3B82F6,#06B6D4); width:0%; transition:width .5s ease; }
    .ap-detail { font-size:11px; opacity:0.6; font-weight:600; line-height:1.6; }
    .ap-loading { display:none; font-size:11px; opacity:0.6; margin-top:8px; }

    /* ── Step 3: Budget ── */
    .budget-toggle { display:flex; gap:0; background:#F0F3F7; border-radius:10px; padding:3px; margin-bottom:20px; width:fit-content; }
    .budget-toggle label { cursor:pointer; }
    .budget-toggle input { position:absolute; opacity:0; width:0; height:0; }
    .bt-inner { padding:8px 20px; border-radius:8px; font-size:12px; font-weight:700; color:#64748B; transition:all .2s; white-space:nowrap; }
    .budget-toggle input:checked ~ .bt-inner { background:#fff; color:#0B1929; box-shadow:0 1px 4px rgba(0,0,0,0.08); }

    .cpv-info { display:flex; align-items:center; gap:10px; padding:12px 16px; background:#F8FAFC; border-radius:10px; border:1px solid #E5E9F0; margin-bottom:20px; }
    .cpv-label { font-size:11px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:0.3px; }
    .cpv-value { font-size:20px; font-weight:900; color:#0B1929; }
    .cpv-formula { font-size:10px; color:#94A3B8; font-weight:600; }
    .cpv-badge { margin-left:auto; padding:5px 12px; background:linear-gradient(135deg,#3B82F6,#2563EB); color:#fff; border-radius:20px; font-size:11px; font-weight:800; }

    .budget-result { background:linear-gradient(135deg,#F8FAFF,#F0FDF4); border:2px solid #DBEAFE; border-radius:12px; padding:18px; margin-top:16px; }
    .br-formula { font-size:14px; font-weight:800; color:#0B1929; margin-bottom:16px; }
    .br-formula span { color:#3B82F6; }
    .budget-breakdown { display:flex; flex-direction:column; gap:8px; }
    .bb-row { display:flex; justify-content:space-between; align-items:center; font-size:12px; font-weight:700; color:#475569; }
    .bb-row.total { font-size:15px; color:#0B1929; padding-top:10px; border-top:2px solid #E5E9F0; margin-top:4px; }
    .bb-amount { font-weight:800; color:#0B1929; }
    .bb-row.total .bb-amount { font-size:18px; color:#3B82F6; }
    .bb-bar-wrap { height:4px; background:#E5E9F0; border-radius:2px; flex:1; margin:0 10px; }
    .bb-bar-fill { height:100%; border-radius:2px; }

    .date-mode { display:flex; flex-direction:column; gap:12px; margin-top:20px; }
    .radio-option { display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600; color:#334155; }
    .radio-option input { accent-color:#3B82F6; }
    .date-inputs { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px; display:none; }
    .date-inputs.visible { display:grid; }

    /* ── Step 4: Summary ── */
    .summary-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
    .summary-section { background:#F8FAFC; border:1px solid #E5E9F0; border-radius:12px; padding:18px; }
    .ss-title { font-size:11px; font-weight:800; color:#64748B; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid #E5E9F0; }
    .ss-row { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:9px; gap:10px; }
    .ss-label { font-size:11px; font-weight:700; color:#94A3B8; white-space:nowrap; }
    .ss-value { font-size:12px; font-weight:700; color:#0B1929; text-align:right; }
    .ss-value.highlight { color:#3B82F6; font-size:15px; font-weight:900; }
    .summary-section.full { grid-column:span 2; }
    .summary-tags { display:flex; flex-wrap:wrap; gap:5px; justify-content:flex-end; }
    .summary-tag { padding:3px 9px; border-radius:20px; background:#DBEAFE; color:#1E40AF; font-size:10px; font-weight:700; }

    /* ── Navigation ── */
    .wizard-nav { display:flex; justify-content:space-between; align-items:center; margin-top:28px; padding-top:20px; border-top:2px solid #F0F3F7; }
    .btn-prev { padding:10px 22px; border-radius:10px; font-weight:700; font-size:13px; border:2px solid #E5E9F0; background:#fff; color:#64748B; cursor:pointer; font-family:inherit; transition:all .2s; display:flex; align-items:center; gap:6px; }
    .btn-prev:hover { border-color:#CBD5E1; color:#0B1929; }
    .btn-next { padding:11px 28px; border-radius:10px; font-weight:800; font-size:13px; background:linear-gradient(135deg,#3B82F6,#2563EB); color:#fff; border:none; cursor:pointer; box-shadow:0 3px 10px rgba(59,130,246,0.25); font-family:inherit; transition:all .2s; display:flex; align-items:center; gap:6px; }
    .btn-next:hover { box-shadow:0 5px 16px rgba(59,130,246,0.35); transform:translateY(-1px); }
    .btn-pay { background:linear-gradient(135deg,#10B981,#059669); box-shadow:0 3px 10px rgba(16,185,129,0.3); }
    .btn-pay:hover { box-shadow:0 5px 16px rgba(16,185,129,0.4); }
    .btn-invisible { visibility:hidden; }

    /* Error state */
    .step-error { display:none; margin-bottom:14px; padding:10px 14px; background:#FEF2F2; border:1px solid #FECACA; border-radius:8px; color:#DC2626; font-size:12px; font-weight:700; }
    .step-error.visible { display:block; }

    /* Validation highlight */
    .form-input.invalid { border-color:#EF4444; }

    /* Responsive */
    @media(max-width:768px) {
        .format-grid { grid-template-columns:1fr 1fr; }
        .targeting-layout { grid-template-columns:1fr; }
        .summary-grid { grid-template-columns:1fr; }
        .summary-section.full { grid-column:span 1; }
        .city-grid { grid-template-columns:1fr 1fr; }
    }
    @media(max-width:480px) {
        .format-grid { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="wizard-wrap">

    {{-- Errors from server --}}
    @if($errors->any())
    <div style="margin-bottom:16px;padding:14px 18px;background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;color:#DC2626;font-size:13px;font-weight:700;">
        <div style="margin-bottom:6px;">Veuillez corriger les erreurs suivantes :</div>
        <ul style="padding-left:18px;">
            @foreach($errors->all() as $err)
                <li style="margin-bottom:2px;">{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Progress bar --}}
    <div class="wizard-progress" id="wizardProgress">
        <div class="wp-step active" data-step="1">
            <div class="wp-dot">1</div>
            <div class="wp-label">Format &amp; Contenu</div>
        </div>
        <div class="wp-step" data-step="2">
            <div class="wp-dot">2</div>
            <div class="wp-label">Audience</div>
        </div>
        <div class="wp-step" data-step="3">
            <div class="wp-dot">3</div>
            <div class="wp-label">Budget</div>
        </div>
        <div class="wp-step" data-step="4">
            <div class="wp-dot">4</div>
            <div class="wp-label">Récapitulatif</div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- THE FORM --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <form
        id="campaignForm"
        method="POST"
        action="{{ route('panel.advertiser.campaigns.store') }}"
        enctype="multipart/form-data"
    >
        @csrf

        {{-- Hidden fields populated by JS --}}
        <input type="hidden" name="quiz_data" id="hiddenQuizData">
        {{-- targeting[*] hidden inputs are generated dynamically by syncHiddenFields() --}}

        {{-- ══════════════════════════════════════════ --}}
        {{-- STEP 1 — Format & Contenu --}}
        {{-- ══════════════════════════════════════════ --}}
        <div class="card wizard-step active" id="step1">
            <div class="card-head">
                <div class="card-title">Étape 1 — Format &amp; Contenu</div>
            </div>
            <div class="card-body">
                <div class="step-error" id="err1"></div>

                {{-- Format cards (dynamique depuis la base de données) --}}
                <div class="form-group" style="margin-bottom:20px;">
                    <div class="form-label" style="margin-bottom:10px;">Format publicitaire *</div>
                    <div class="format-grid" id="formatGrid">

                        @foreach($formats as $format)
                        <label class="format-card">
                            <input type="radio" name="format" value="{{ $format->slug }}"
                                   data-duration="{{ $format->default_duration }}"
                                   data-multiplier="{{ $format->multiplier }}"
                                   data-media='@json($format->accepted_media)'>
                            <div class="format-card-inner">
                                <div class="fc-icon">{{ $format->icon }}</div>
                                <div class="fc-name">{{ $format->label }}</div>
                                <div class="fc-desc">{{ $format->description }}</div>
                                <span class="fc-badge">{{ $format->default_duration ? $format->default_duration.'s · ' : '' }}x{{ $format->multiplier }}</span>
                            </div>
                        </label>
                        @endforeach

                    </div>
                </div>

                {{-- Content fields (shown after format selection) --}}
                <div class="content-fields" id="contentFields">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label" for="fieldTitle">Titre de la campagne *</label>
                            <input
                                class="form-input"
                                id="fieldTitle"
                                name="title"
                                type="text"
                                maxlength="255"
                                placeholder="Ex : Lancement produit Côte d'Ivoire"
                                value="{{ old('title') }}"
                                autocomplete="off"
                            >
                        </div>

                        <div class="form-group full">
                            <label class="form-label" for="fieldDescription">Description (optionnel)</label>
                            <textarea
                                class="form-input"
                                id="fieldDescription"
                                name="description"
                                placeholder="Décrivez brièvement votre campagne..."
                                rows="3"
                            >{{ old('description') }}</textarea>
                        </div>

                        <div class="form-group full">
                            <label class="form-label">Upload du média *</label>
                            <div class="upload-zone" id="mediaZone">
                                <input type="file" name="media" id="mediaFileInput" accept="video/*">
                                <div class="uz-icon">🎬</div>
                                <div class="uz-text">Glissez votre fichier ici ou cliquez pour parcourir</div>
                                <div class="uz-hint" id="mediaHint">MP4, MOV, AVI (max 50 Mo)</div>
                            </div>
                            <div class="upload-preview" id="mediaPreview">
                                <img class="preview-thumb" id="mediaThumb" src="" alt="">
                                <div>
                                    <div class="preview-filename" id="mediaFilename"></div>
                                    <div class="preview-filesize" id="mediaFilesize"></div>
                                </div>
                                <div class="preview-remove" id="mediaRemove" title="Supprimer">✕</div>
                            </div>
                        </div>

                        <div class="form-group full">
                            <label class="form-label">Vignette (optionnel)</label>
                            <div class="upload-zone" id="thumbZone">
                                <input type="file" name="thumbnail" id="thumbFileInput" accept="image/*">
                                <div class="uz-icon">🖼️</div>
                                <div class="uz-text">Glissez votre vignette ici ou cliquez</div>
                                <div class="uz-hint">JPG, PNG (max 5 Mo)</div>
                            </div>
                            <div class="upload-preview" id="thumbPreview">
                                <img class="preview-thumb" id="thumbThumb" src="" alt="" style="display:block;">
                                <div>
                                    <div class="preview-filename" id="thumbFilename"></div>
                                    <div class="preview-filesize" id="thumbFilesize"></div>
                                </div>
                                <div class="preview-remove" id="thumbRemove" title="Supprimer">✕</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="fieldDuration">Durée (secondes)</label>
                            <input
                                class="form-input"
                                id="fieldDuration"
                                name="duration_seconds"
                                type="number"
                                min="1"
                                max="300"
                                value="{{ old('duration_seconds', 30) }}"
                            >
                        </div>
                    </div>

                    {{-- Quiz section --}}
                    <div class="quiz-section" id="quizSection">
                        <div class="quiz-section-title">
                            ❓ Questions du quiz
                            <span style="font-size:10px;font-weight:600;color:#64748B;">(jusqu'à 3 questions)</span>
                        </div>
                        <div id="quizQuestions"></div>
                        <button type="button" class="btn-add-question" id="btnAddQuestion">
                            <span>+</span> Ajouter une question
                        </button>
                    </div>
                </div>
            </div>
            <div class="wizard-nav">
                <a href="{{ route('panel.advertiser.campaigns') }}" class="btn-prev">Annuler</a>
                <button type="button" class="btn-next" onclick="goToStep(2)">Suivant →</button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════ --}}
        {{-- STEP 2 — Audience & Ciblage --}}
        {{-- ══════════════════════════════════════════ --}}
        <div class="card wizard-step" id="step2">
            <div class="card-head">
                <div class="card-title">Étape 2 — Audience &amp; Ciblage</div>
            </div>
            <div class="card-body">
                <div class="targeting-layout">
                    <div>
                        {{-- Age --}}
                        <div style="margin-bottom:22px;">
                            <div class="section-title">Tranche d'âge</div>
                            <div class="age-range-wrap">
                                <input
                                    class="form-input"
                                    id="ageMin"
                                    name="targeting[age_min]"
                                    type="number"
                                    min="13"
                                    max="100"
                                    value="16"
                                    style="width:70px;text-align:center;"
                                >
                                <span class="age-sep">–</span>
                                <input
                                    class="form-input"
                                    id="ageMax"
                                    name="targeting[age_max]"
                                    type="number"
                                    min="13"
                                    max="100"
                                    value="65"
                                    style="width:70px;text-align:center;"
                                >
                                <span class="age-display" id="ageDisplay">16 – 65 ans</span>
                            </div>
                        </div>

                        {{-- Genre --}}
                        <div style="margin-bottom:22px;">
                            <div class="section-title">Genre</div>
                            <div class="check-cards" id="genderCards">
                                <label class="check-card">
                                    <input type="checkbox" name="_gender_all" id="genderAll" checked onchange="handleGenderAll(this)">
                                    <div class="check-card-inner">Tous</div>
                                </label>
                                <label class="check-card">
                                    <input type="checkbox" name="_gender_m" id="genderM" onchange="handleGenderSpecific()">
                                    <div class="check-card-inner">Hommes</div>
                                </label>
                                <label class="check-card">
                                    <input type="checkbox" name="_gender_f" id="genderF" onchange="handleGenderSpecific()">
                                    <div class="check-card-inner">Femmes</div>
                                </label>
                            </div>
                        </div>

                        {{-- Villes --}}
                        <div style="margin-bottom:22px;">
                            <div class="section-title" style="display:flex;align-items:center;gap:12px;">
                                Villes
                                <label style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:#64748B;cursor:pointer;">
                                    <input type="checkbox" id="allCities" style="accent-color:#3B82F6;" onchange="toggleAllCities(this)">
                                    Toutes les villes
                                </label>
                            </div>
                            <div class="city-grid" id="cityGrid">
                                @foreach(['Abidjan','Bouaké','Yamoussoukro','Daloa','Korhogo','San-Pédro','Man','Gagnoa','Divo','Abengourou'] as $city)
                                <label class="city-check" id="city_{{ Str::slug($city) }}">
                                    <input type="checkbox" class="city-checkbox" value="{{ $city }}" onchange="onCityChange()">
                                    <span>{{ $city }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Opérateurs --}}
                        <div style="margin-bottom:22px;">
                            <div class="section-title" style="display:flex;align-items:center;gap:12px;">
                                Opérateurs mobiles
                                <label style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:#64748B;cursor:pointer;">
                                    <input type="checkbox" id="allOperators" style="accent-color:#3B82F6;" onchange="toggleAllOperators(this)" checked>
                                    Tous
                                </label>
                            </div>
                            <div class="check-cards" id="operatorCards">
                                <label class="check-card">
                                    <input type="checkbox" class="op-checkbox" value="orange" onchange="onOperatorChange()">
                                    <div class="check-card-inner">🟠 Orange</div>
                                </label>
                                <label class="check-card">
                                    <input type="checkbox" class="op-checkbox" value="mtn" onchange="onOperatorChange()">
                                    <div class="check-card-inner">🟡 MTN</div>
                                </label>
                                <label class="check-card">
                                    <input type="checkbox" class="op-checkbox" value="moov" onchange="onOperatorChange()">
                                    <div class="check-card-inner">🔵 Moov</div>
                                </label>
                            </div>
                        </div>

                        {{-- Centres d'intérêt --}}
                        <div>
                            <div class="section-title">Centres d'intérêt</div>
                            <div class="interest-tags" id="interestTags">
                                @foreach(['Technologie','Sport','Mode','Cuisine','Finance','Musique','Éducation','Santé','Voyage','Auto','Gaming','Beauté'] as $interest)
                                <span class="interest-tag" onclick="toggleInterest(this, '{{ $interest }}')">{{ $interest }}</span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Critères d'audience dynamiques (non-builtin, stockés dans custom_fields) --}}
                        @foreach($criteria->where('storage_column', null) as $criterion)
                        <div style="margin-top:22px;" data-dynamic-criterion="{{ $criterion->name }}">
                            <div class="section-title">{{ strtoupper($criterion->label) }}</div>
                            @if($criterion->type === 'select' || $criterion->type === 'multiselect')
                                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                    @foreach($criterion->options ?? [] as $option)
                                    <span class="interest-tag"
                                          onclick="toggleDynamicCriterion(this, '{{ $criterion->name }}', '{{ $option }}')"
                                          data-criterion="{{ $criterion->name }}"
                                          data-value="{{ $option }}">
                                        {{ $option }}
                                    </span>
                                    @endforeach
                                </div>
                            @elseif($criterion->type === 'text')
                                <input type="text"
                                       class="form-input"
                                       id="dynCriterion_{{ $criterion->name }}"
                                       data-criterion="{{ $criterion->name }}"
                                       placeholder="{{ $criterion->label }}..."
                                       oninput="onDynamicTextChange('{{ $criterion->name }}')">
                            @elseif($criterion->type === 'boolean')
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:#334155;">
                                    <input type="checkbox"
                                           id="dynCriterion_{{ $criterion->name }}"
                                           data-criterion="{{ $criterion->name }}"
                                           style="accent-color:#3B82F6;"
                                           onchange="onDynamicBoolChange('{{ $criterion->name }}')">
                                    {{ $criterion->label }}
                                </label>
                            @endif
                        </div>
                        @endforeach

                    </div>

                    {{-- Audience panel --}}
                    <div>
                        <div class="audience-panel">
                            <div class="ap-title">Audience estimée</div>
                            <div class="ap-count" id="apCount">— <span>abonnés</span></div>
                            <div class="ap-pct" id="apPct">Calculez votre audience</div>
                            <div class="ap-bar"><div class="ap-fill" id="apFill"></div></div>
                            <div class="ap-detail" id="apDetail">
                                Sélectionnez vos critères de ciblage pour estimer le nombre d'abonnés correspondants.
                            </div>
                            <div class="ap-loading" id="apLoading">⏳ Calcul en cours...</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-nav">
                <button type="button" class="btn-prev" onclick="goToStep(1)">← Précédent</button>
                <button type="button" class="btn-next" onclick="goToStep(3)">Suivant →</button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════ --}}
        {{-- STEP 3 — Budget & Planification --}}
        {{-- ══════════════════════════════════════════ --}}
        <div class="card wizard-step" id="step3">
            <div class="card-head">
                <div class="card-title">Étape 3 — Budget &amp; Planification</div>
            </div>
            <div class="card-body">
                <div class="step-error" id="err3"></div>

                {{-- Mode toggle --}}
                <div class="form-label" style="margin-bottom:8px;">Mode de calcul</div>
                <div class="budget-toggle">
                    <label>
                        <input type="radio" name="budget_mode" value="views" id="modeViews" checked onchange="onBudgetModeChange()">
                        <span class="bt-inner">Par nombre de vues</span>
                    </label>
                    <label>
                        <input type="radio" name="budget_mode" value="budget" id="modeBudget" onchange="onBudgetModeChange()">
                        <span class="bt-inner">Par budget</span>
                    </label>
                </div>

                {{-- CPV info --}}
                <div class="cpv-info">
                    <div>
                        <div class="cpv-label">Coût par vue effectif</div>
                        <div class="cpv-value"><span id="cpvDisplay">100</span> FCFA</div>
                        <div class="cpv-formula" id="cpvFormula">100 FCFA × 1.0 (vidéo)</div>
                    </div>
                    <span class="cpv-badge" id="cpvFormatBadge">Vidéo</span>
                </div>

                {{-- Views input --}}
                <div id="viewsInputWrap">
                    <div class="form-group">
                        <label class="form-label" for="fieldViews">Nombre de vues souhaité</label>
                        <input
                            class="form-input"
                            id="fieldViews"
                            name="target_views"
                            type="number"
                            min="1"
                            placeholder="Ex : 1000"
                            value="{{ old('target_views') }}"
                            oninput="recalcBudget()"
                        >
                    </div>
                </div>

                {{-- Budget input --}}
                <div id="budgetInputWrap" style="display:none;">
                    <div class="form-group">
                        <label class="form-label" for="fieldBudget">Budget en FCFA</label>
                        <input
                            class="form-input"
                            id="fieldBudget"
                            name="budget"
                            type="number"
                            min="5000"
                            placeholder="Ex : 50000"
                            value="{{ old('budget') }}"
                            oninput="recalcBudget()"
                        >
                    </div>
                </div>

                {{-- Budget result --}}
                <div class="budget-result" id="budgetResult" style="display:none;">
                    <div class="br-formula" id="brFormula">
                        <span id="brViews">0</span> vues × <span id="brCpv">100</span> FCFA = <span id="brTotal">0</span> FCFA
                    </div>
                    <div class="budget-breakdown">
                        <div class="bb-row">
                            <span>Rémunération abonnés (60%)</span>
                            <div class="bb-bar-wrap"><div class="bb-bar-fill" style="width:60%;background:linear-gradient(90deg,#10B981,#059669);"></div></div>
                            <span class="bb-amount" id="bbSubscribers">0 FCFA</span>
                        </div>
                        <div class="bb-row">
                            <span>Commission plateforme (40%)</span>
                            <div class="bb-bar-wrap"><div class="bb-bar-fill" style="width:40%;background:linear-gradient(90deg,#3B82F6,#2563EB);"></div></div>
                            <span class="bb-amount" id="bbPlatform">0 FCFA</span>
                        </div>
                        <div class="bb-row total">
                            <span>Budget total</span>
                            <span class="bb-amount" id="bbTotal">0 FCFA</span>
                        </div>
                    </div>
                </div>

                {{-- Hidden final fields --}}
                <input type="hidden" name="cost_per_view" id="hiddenCpv" value="100">

                {{-- Mode de fin de campagne --}}
                <div class="form-group full" style="margin-top:24px;">
                    <div class="section-title">Mode de fin de campagne</div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <label style="flex:1;min-width:160px;cursor:pointer;border:2px solid #E5E9F0;border-radius:12px;padding:12px 14px;transition:all .2s;background:#fff;" id="endModeCard_target_reached">
                            <input type="radio" name="end_mode" value="target_reached" checked
                                   onchange="onEndModeChange()"
                                   style="accent-color:#3B82F6;margin-right:8px;">
                            <span style="font-size:12px;font-weight:700;color:#0B1929;">Fin quand le ciblage est atteint</span>
                            <div style="font-size:10px;color:#64748B;font-weight:600;margin-top:4px;margin-left:20px;">Arrêt automatique dès que le quota de vues est atteint.</div>
                        </label>
                        <label style="flex:1;min-width:160px;cursor:pointer;border:2px solid #E5E9F0;border-radius:12px;padding:12px 14px;transition:all .2s;background:#fff;" id="endModeCard_date">
                            <input type="radio" name="end_mode" value="date"
                                   onchange="onEndModeChange()"
                                   style="accent-color:#3B82F6;margin-right:8px;">
                            <span style="font-size:12px;font-weight:700;color:#0B1929;">Fin à une date précise</span>
                            <div style="font-size:10px;color:#64748B;font-weight:600;margin-top:4px;margin-left:20px;">La campagne s'arrête automatiquement à la date de fin définie.</div>
                        </label>
                        <label style="flex:1;min-width:160px;cursor:pointer;border:2px solid #E5E9F0;border-radius:12px;padding:12px 14px;transition:all .2s;background:#fff;" id="endModeCard_manual">
                            <input type="radio" name="end_mode" value="manual"
                                   onchange="onEndModeChange()"
                                   style="accent-color:#3B82F6;margin-right:8px;">
                            <span style="font-size:12px;font-weight:700;color:#0B1929;">Arrêt manuel</span>
                            <div style="font-size:10px;color:#64748B;font-weight:600;margin-top:4px;margin-left:20px;">Vous arrêtez la campagne manuellement depuis votre tableau de bord.</div>
                        </label>
                    </div>
                </div>

                {{-- Dates --}}
                <div style="margin-top:24px;">
                    <div class="section-title">Planification</div>
                    <div class="date-mode">
                        <label class="radio-option">
                            <input type="radio" name="launch_mode" value="immediate" id="launchImmediate" checked onchange="onLaunchModeChange()">
                            Lancer immédiatement après validation
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="launch_mode" value="scheduled" id="launchScheduled" onchange="onLaunchModeChange()">
                            Planifier une date de début / fin
                        </label>
                        <div class="date-inputs" id="dateInputs">
                            <div class="form-group">
                                <label class="form-label" for="fieldStartDate">Date de début</label>
                                <input class="form-input" id="fieldStartDate" name="starts_at" type="date" value="{{ old('starts_at') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="fieldEndDate">Date de fin</label>
                                <input class="form-input" id="fieldEndDate" name="ends_at" type="date" value="{{ old('ends_at') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-nav">
                <button type="button" class="btn-prev" onclick="goToStep(2)">← Précédent</button>
                <button type="button" class="btn-next" onclick="goToStep(4)">Voir le récapitulatif →</button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════ --}}
        {{-- STEP 4 — Récapitulatif --}}
        {{-- ══════════════════════════════════════════ --}}
        <div class="card wizard-step" id="step4">
            <div class="card-head">
                <div class="card-title">Étape 4 — Récapitulatif</div>
            </div>
            <div class="card-body">
                <div class="summary-grid" id="summaryGrid">

                    <div class="summary-section">
                        <div class="ss-title">Votre campagne</div>
                        <div class="ss-row">
                            <span class="ss-label">Format</span>
                            <span class="ss-value" id="sumFormat">—</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Titre</span>
                            <span class="ss-value" id="sumTitle">—</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Description</span>
                            <span class="ss-value" id="sumDesc" style="max-width:200px;">—</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Média</span>
                            <span class="ss-value" id="sumMedia">Non uploadé</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Durée</span>
                            <span class="ss-value" id="sumDuration">—</span>
                        </div>
                    </div>

                    <div class="summary-section">
                        <div class="ss-title">Audience ciblée</div>
                        <div class="ss-row">
                            <span class="ss-label">Âge</span>
                            <span class="ss-value" id="sumAge">—</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Genre</span>
                            <span class="ss-value" id="sumGender">—</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Villes</span>
                            <span class="ss-value" id="sumCities">Toutes</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Opérateurs</span>
                            <span class="ss-value" id="sumOperators">Tous</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Intérêts</span>
                            <div class="summary-tags" id="sumInterests">—</div>
                        </div>
                        <div class="ss-row" style="margin-top:8px;padding-top:8px;border-top:1px solid #E5E9F0;">
                            <span class="ss-label">Audience est.</span>
                            <span class="ss-value highlight" id="sumAudience">—</span>
                        </div>
                    </div>

                    <div class="summary-section">
                        <div class="ss-title">Budget</div>
                        <div class="ss-row">
                            <span class="ss-label">Nombre de vues</span>
                            <span class="ss-value" id="sumViews">—</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Coût par vue</span>
                            <span class="ss-value" id="sumCpv">—</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Dont abonnés (60%)</span>
                            <span class="ss-value" id="sumSubscribers">—</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Dont plateforme (40%)</span>
                            <span class="ss-value" id="sumPlatform">—</span>
                        </div>
                        <div class="ss-row" style="margin-top:8px;padding-top:8px;border-top:1px solid #E5E9F0;">
                            <span class="ss-label">Budget total</span>
                            <span class="ss-value highlight" id="sumTotal">—</span>
                        </div>
                    </div>

                    <div class="summary-section">
                        <div class="ss-title">Planification</div>
                        <div class="ss-row">
                            <span class="ss-label">Lancement</span>
                            <span class="ss-value" id="sumLaunch">Immédiat</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Date début</span>
                            <span class="ss-value" id="sumStart">—</span>
                        </div>
                        <div class="ss-row">
                            <span class="ss-label">Date fin</span>
                            <span class="ss-value" id="sumEnd">—</span>
                        </div>
                    </div>

                </div>

                <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:13px 16px;font-size:12px;font-weight:700;color:#78350F;margin-top:4px;">
                    ⚠️ Vérifiez bien votre campagne avant de payer. Les fonds seront prélevés immédiatement sur votre solde.
                </div>
            </div>
            <div class="wizard-nav">
                <button type="button" class="btn-prev" onclick="goToStep(1)">← Modifier</button>
                <button type="submit" class="btn-next btn-pay" id="btnPay">
                    💳 Payer <span id="btnPayAmount">0</span> FCFA et publier
                </button>
            </div>
        </div>

    </form>
</div>

<script>
(function() {
    'use strict';

    // ── Constants (construits depuis les données serveur) ───────
    // FORMAT_CONFIG est construit dynamiquement depuis les attributs data- des radios
    function buildFormatConfig() {
        const config = {};
        document.querySelectorAll('input[name=format]').forEach(radio => {
            const slug       = radio.value;
            const multiplier = parseFloat(radio.dataset.multiplier) || 1.0;
            const duration   = parseInt(radio.dataset.duration)     || 30;
            const cpv        = Math.round(100 * multiplier);
            const labelEl    = radio.closest('.format-card')?.querySelector('.fc-name');
            const label      = labelEl ? labelEl.textContent.trim() : slug;
            config[slug]     = { multiplier, duration, label, cpv };
        });
        return config;
    }

    // ── State ──────────────────────────────────────────────────
    let FORMAT_CONFIG = {};
    let currentStep = 1;
    let selectedInterests = [];
    let dynamicCriteriaValues = {}; // { criterionName: value | array }
    let quizQuestionCount = 0;
    let audienceDebounceTimer = null;
    let estimatedAudience = '—';

    // ── Helpers ────────────────────────────────────────────────
    function fmt(n) {
        return Number(n).toLocaleString('fr-FR');
    }

    function getFormat() {
        const r = document.querySelector('input[name=format]:checked');
        return r ? r.value : null;
    }

    // ── Step navigation ────────────────────────────────────────
    window.goToStep = function(target) {
        if (target > currentStep) {
            if (!validateStep(currentStep)) return;
        }
        // Save hidden data before advancing
        syncHiddenFields();

        // Update progress dots
        document.querySelectorAll('.wp-step').forEach(el => {
            const s = parseInt(el.dataset.step);
            el.classList.remove('active', 'done');
            if (s < target)  el.classList.add('done');
            if (s === target) el.classList.add('active');
        });

        // Show/hide step cards
        document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
        document.getElementById('step' + target).classList.add('active');

        currentStep = target;

        if (target === 4) buildSummary();

        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // ── Validation ─────────────────────────────────────────────
    function validateStep(step) {
        if (step === 1) {
            const err = document.getElementById('err1');
            const fmt = getFormat();
            if (!fmt) {
                showErr(err, 'Veuillez sélectionner un format publicitaire.');
                return false;
            }
            const title = document.getElementById('fieldTitle').value.trim();
            if (!title) {
                showErr(err, 'Le titre de la campagne est obligatoire.');
                document.getElementById('fieldTitle').classList.add('invalid');
                document.getElementById('fieldTitle').focus();
                return false;
            }
            document.getElementById('fieldTitle').classList.remove('invalid');
            hideErr(err);
            return true;
        }
        if (step === 3) {
            const err = document.getElementById('err3');
            const total = getBudgetTotal();
            if (total < 5000) {
                showErr(err, 'Le budget total doit être d\'au moins 5 000 FCFA.');
                return false;
            }
            hideErr(err);
            return true;
        }
        return true;
    }

    function showErr(el, msg) { el.textContent = msg; el.classList.add('visible'); }
    function hideErr(el) { el.classList.remove('visible'); }

    // ── Format selection ───────────────────────────────────────
    document.querySelectorAll('input[name=format]').forEach(radio => {
        radio.addEventListener('change', onFormatChange);
    });

    function onFormatChange() {
        const f = getFormat();
        if (!f) return;
        const cfg = FORMAT_CONFIG[f];
        if (!cfg) return;

        // Show content fields
        const cf = document.getElementById('contentFields');
        cf.classList.add('visible');

        // Set duration from server data
        document.getElementById('fieldDuration').value = cfg.duration;

        // Update media zone accept & hint based on accepted_media data attribute
        const mediaInput  = document.getElementById('mediaFileInput');
        const mediaHint   = document.getElementById('mediaHint');
        const checkedRadio = document.querySelector('input[name=format]:checked');
        let acceptedMedia = [];
        try { acceptedMedia = JSON.parse(checkedRadio?.dataset?.media || '[]'); } catch(e) {}

        const isImageOnly = acceptedMedia.length > 0 && acceptedMedia.every(m => ['jpg','jpeg','png','gif','webp','image'].includes(m));
        if (isImageOnly) {
            mediaInput.accept = 'image/*';
            mediaHint.textContent = 'JPG, PNG, GIF (max 5 Mo)';
            document.querySelector('#mediaZone .uz-icon').textContent = '🖼️';
        } else {
            mediaInput.accept = 'video/*';
            mediaHint.textContent = 'MP4, MOV, AVI (max 50 Mo)';
            document.querySelector('#mediaZone .uz-icon').textContent = '🎬';
        }

        // Show/hide quiz section (slug 'quiz' keeps this behaviour)
        const qs = document.getElementById('quizSection');
        if (f === 'quiz') {
            qs.classList.add('visible');
            if (quizQuestionCount === 0) addQuizQuestion();
        } else {
            qs.classList.remove('visible');
        }

        // Update CPV display on step 3
        updateCpvDisplay();
    }

    // ── Upload zones ───────────────────────────────────────────
    function setupUpload(zoneId, inputId, previewId, thumbId, filenameId, filesizeId, removeId) {
        const zone     = document.getElementById(zoneId);
        const input    = document.getElementById(inputId);
        const preview  = document.getElementById(previewId);
        const thumb    = document.getElementById(thumbId);
        const fname    = document.getElementById(filenameId);
        const fsize    = document.getElementById(filesizeId);
        const remove   = document.getElementById(removeId);

        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                // Transfer to input
                const dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                input.files = dt.files;
                showPreview(input.files[0]);
            }
        });

        input.addEventListener('change', () => {
            if (input.files.length) showPreview(input.files[0]);
        });

        remove.addEventListener('click', () => {
            input.value = '';
            preview.classList.remove('visible');
            zone.style.display = '';
        });

        function showPreview(file) {
            fname.textContent = file.name;
            fsize.textContent = formatBytes(file.size);
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => { thumb.src = e.target.result; thumb.style.display = 'block'; };
                reader.readAsDataURL(file);
            } else {
                thumb.style.display = 'none';
            }
            preview.classList.add('visible');
        }
    }

    function formatBytes(bytes) {
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
        return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
    }

    setupUpload('mediaZone', 'mediaFileInput', 'mediaPreview', 'mediaThumb', 'mediaFilename', 'mediaFilesize', 'mediaRemove');
    setupUpload('thumbZone', 'thumbFileInput', 'thumbPreview', 'thumbThumb', 'thumbFilename', 'thumbFilesize', 'thumbRemove');

    // ── Quiz builder ───────────────────────────────────────────
    window.addQuizQuestion = function() {
        if (quizQuestionCount >= 3) return;
        quizQuestionCount++;
        const n = quizQuestionCount;
        const container = document.getElementById('quizQuestions');

        const block = document.createElement('div');
        block.className = 'quiz-question-block';
        block.dataset.qn = n;
        block.innerHTML = `
            <div class="quiz-q-label">Question ${n}</div>
            <input class="form-input quiz-question-text" type="text" placeholder="Entrez la question..." style="width:100%;margin-bottom:8px;">
            <div class="quiz-answers">
                <input class="form-input quiz-answer" type="text" placeholder="Réponse A">
                <input class="form-input quiz-answer" type="text" placeholder="Réponse B">
                <input class="form-input quiz-answer" type="text" placeholder="Réponse C">
            </div>
            ${n > 1 ? `<button type="button" class="quiz-remove" onclick="removeQuizQuestion(${n})">✕</button>` : ''}
        `;
        container.appendChild(block);

        const btn = document.getElementById('btnAddQuestion');
        if (quizQuestionCount >= 3) btn.style.display = 'none';
    };

    window.removeQuizQuestion = function(n) {
        const block = document.querySelector(`.quiz-question-block[data-qn="${n}"]`);
        if (block) block.remove();
        quizQuestionCount--;
        document.getElementById('btnAddQuestion').style.display = '';
        // Renumber remaining
        document.querySelectorAll('.quiz-question-block').forEach((b, i) => {
            b.dataset.qn = i + 1;
            b.querySelector('.quiz-q-label').textContent = 'Question ' + (i + 1);
        });
        quizQuestionCount = document.querySelectorAll('.quiz-question-block').length;
    };

    document.getElementById('btnAddQuestion').addEventListener('click', addQuizQuestion);

    // ── Gender handling ────────────────────────────────────────
    window.handleGenderAll = function(el) {
        if (el.checked) {
            document.getElementById('genderM').checked = false;
            document.getElementById('genderF').checked = false;
        }
    };

    window.handleGenderSpecific = function() {
        const m = document.getElementById('genderM').checked;
        const f = document.getElementById('genderF').checked;
        if (m || f) document.getElementById('genderAll').checked = false;
        else document.getElementById('genderAll').checked = true;
    };

    // ── City handling ──────────────────────────────────────────
    window.toggleAllCities = function(el) {
        document.querySelectorAll('.city-checkbox').forEach(c => {
            c.checked = el.checked;
            c.closest('.city-check').classList.toggle('selected', el.checked);
        });
        triggerAudienceEstimate();
    };

    window.onCityChange = function() {
        document.getElementById('allCities').checked = false;
        document.querySelectorAll('.city-checkbox').forEach(c => {
            c.closest('.city-check').classList.toggle('selected', c.checked);
        });
        triggerAudienceEstimate();
    };

    // ── Operator handling ──────────────────────────────────────
    window.toggleAllOperators = function(el) {
        document.querySelectorAll('.op-checkbox').forEach(c => { c.checked = false; });
        triggerAudienceEstimate();
    };

    window.onOperatorChange = function() {
        const any = Array.from(document.querySelectorAll('.op-checkbox')).some(c => c.checked);
        document.getElementById('allOperators').checked = !any;
        triggerAudienceEstimate();
    };

    // ── Interests ──────────────────────────────────────────────
    window.toggleInterest = function(el, interest) {
        el.classList.toggle('selected');
        if (el.classList.contains('selected')) {
            if (!selectedInterests.includes(interest)) selectedInterests.push(interest);
        } else {
            selectedInterests = selectedInterests.filter(i => i !== interest);
        }
        triggerAudienceEstimate();
    };

    // ── Age change ─────────────────────────────────────────────
    document.getElementById('ageMin').addEventListener('input', () => {
        const mn = parseInt(document.getElementById('ageMin').value) || 16;
        const mx = parseInt(document.getElementById('ageMax').value) || 65;
        document.getElementById('ageDisplay').textContent = mn + ' – ' + mx + ' ans';
        triggerAudienceEstimate();
    });
    document.getElementById('ageMax').addEventListener('input', () => {
        const mn = parseInt(document.getElementById('ageMin').value) || 16;
        const mx = parseInt(document.getElementById('ageMax').value) || 65;
        document.getElementById('ageDisplay').textContent = mn + ' – ' + mx + ' ans';
        triggerAudienceEstimate();
    });

    // ── Audience estimate (AJAX, debounced) ────────────────────
    function triggerAudienceEstimate() {
        clearTimeout(audienceDebounceTimer);
        audienceDebounceTimer = setTimeout(fetchAudienceEstimate, 500);
    }

    function getTargetingPayload() {
        const cities = Array.from(document.querySelectorAll('.city-checkbox:checked')).map(c => c.value);
        const allCities = document.getElementById('allCities').checked || cities.length === 0;

        const allOps = document.getElementById('allOperators').checked;
        const ops = allOps ? [] : Array.from(document.querySelectorAll('.op-checkbox:checked')).map(c => c.value);

        const genderAll = document.getElementById('genderAll').checked;
        const genders = genderAll ? [] : [
            document.getElementById('genderM').checked ? 'male' : null,
            document.getElementById('genderF').checked ? 'female' : null
        ].filter(Boolean);

        // Construire le targeting (doit correspondre à $request->input('targeting') côté serveur)
        const targeting = {
            age_min:   parseInt(document.getElementById('ageMin').value) || 16,
            age_max:   parseInt(document.getElementById('ageMax').value) || 65,
            genders:   genders,
            cities:    allCities ? [] : cities,
            operators: ops,
            interests: selectedInterests
        };

        // Ajouter les critères dynamiques non-vides
        Object.entries(dynamicCriteriaValues).forEach(([key, val]) => {
            if (val !== null && val !== undefined && !(Array.isArray(val) && val.length === 0)) {
                targeting[key] = val;
            }
        });

        // Envelopper sous la clé "targeting" comme attendu par estimateAudience()
        return { targeting };
    }

    function fetchAudienceEstimate() {
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const payload = getTargetingPayload();
        const loading = document.getElementById('apLoading');
        loading.style.display = 'block';

        fetch('/panel/advertiser/campaigns/estimate-audience', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';
            const count = data.estimated_audience || 0;
            const pct   = data.percentage || 0;
            estimatedAudience = '~' + fmt(count) + ' abonnés';
            document.getElementById('apCount').innerHTML = fmt(count) + ' <span>abonnés</span>';
            document.getElementById('apPct').textContent = pct.toFixed(1) + '% de l\'audience totale';
            document.getElementById('apFill').style.width = Math.min(pct, 100) + '%';
            document.getElementById('apDetail').textContent =
                count > 0
                    ? 'Ces abonnés correspondent à vos critères de ciblage.'
                    : 'Aucun abonné ne correspond à ces critères. Élargissez votre ciblage.';
        })
        .catch(() => {
            loading.style.display = 'none';
            document.getElementById('apDetail').textContent = 'Impossible de récupérer l\'estimation pour le moment.';
        });
    }

    // ── Budget calculation ─────────────────────────────────────
    window.onBudgetModeChange = function() {
        const mode = document.querySelector('input[name=budget_mode]:checked').value;
        document.getElementById('viewsInputWrap').style.display  = mode === 'views'  ? '' : 'none';
        document.getElementById('budgetInputWrap').style.display = mode === 'budget' ? '' : 'none';
        recalcBudget();
    };

    function getCpv() {
        const f = getFormat();
        return f ? FORMAT_CONFIG[f].cpv : 100;
    }

    function updateCpvDisplay() {
        const f   = getFormat();
        const cfg = f && FORMAT_CONFIG[f]
            ? FORMAT_CONFIG[f]
            : Object.values(FORMAT_CONFIG)[0] || { cpv: 100, multiplier: 1.0, label: 'Vidéo' };
        document.getElementById('cpvDisplay').textContent = cfg.cpv;
        document.getElementById('cpvFormula').textContent = '100 FCFA × ' + cfg.multiplier + ' (' + cfg.label.toLowerCase() + ')';
        document.getElementById('cpvFormatBadge').textContent = cfg.label;
        document.getElementById('hiddenCpv').value = cfg.cpv;
        recalcBudget();
    }

    window.recalcBudget = function() {
        const cpv  = getCpv();
        const mode = document.querySelector('input[name=budget_mode]:checked').value;
        let views  = 0;
        let total  = 0;

        if (mode === 'views') {
            views = parseInt(document.getElementById('fieldViews').value) || 0;
            total = views * cpv;
        } else {
            total  = parseInt(document.getElementById('fieldBudget').value) || 0;
            views  = cpv > 0 ? Math.floor(total / cpv) : 0;
        }

        const subscribers = Math.round(total * 0.6);
        const platform    = Math.round(total * 0.4);

        if (total > 0) {
            document.getElementById('budgetResult').style.display = '';
            document.getElementById('brViews').textContent   = fmt(views);
            document.getElementById('brCpv').textContent     = cpv;
            document.getElementById('brTotal').textContent   = fmt(total);
            document.getElementById('bbSubscribers').textContent = fmt(subscribers) + ' FCFA';
            document.getElementById('bbPlatform').textContent    = fmt(platform) + ' FCFA';
            document.getElementById('bbTotal').textContent       = fmt(total) + ' FCFA';
        } else {
            document.getElementById('budgetResult').style.display = 'none';
        }

        // Update pay button
        document.getElementById('btnPayAmount').textContent = fmt(total);
    };

    function getBudgetTotal() {
        const cpv  = getCpv();
        const mode = document.querySelector('input[name=budget_mode]:checked').value;
        if (mode === 'views') {
            const views = parseInt(document.getElementById('fieldViews').value) || 0;
            return views * cpv;
        } else {
            return parseInt(document.getElementById('fieldBudget').value) || 0;
        }
    }

    // ── Date mode ──────────────────────────────────────────────
    window.onLaunchModeChange = function() {
        const mode = document.querySelector('input[name=launch_mode]:checked').value;
        const di = document.getElementById('dateInputs');
        if (mode === 'scheduled') di.classList.add('visible');
        else di.classList.remove('visible');
    };

    // ── End mode ───────────────────────────────────────────────
    window.onEndModeChange = function() {
        const selected = document.querySelector('input[name=end_mode]:checked')?.value;
        ['target_reached', 'date', 'manual'].forEach(v => {
            const card = document.getElementById('endModeCard_' + v);
            if (card) {
                card.style.borderColor  = v === selected ? '#3B82F6' : '#E5E9F0';
                card.style.background   = v === selected ? '#EFF6FF' : '#fff';
            }
        });
    };

    // Initialiser l'état visuel du mode de fin au chargement
    onEndModeChange();

    // ── Critères dynamiques ────────────────────────────────────
    window.toggleDynamicCriterion = function(el, criterionName, value) {
        el.classList.toggle('selected');

        if (!dynamicCriteriaValues[criterionName]) {
            dynamicCriteriaValues[criterionName] = [];
        }

        const arr = dynamicCriteriaValues[criterionName];
        if (el.classList.contains('selected')) {
            if (!arr.includes(value)) arr.push(value);
        } else {
            dynamicCriteriaValues[criterionName] = arr.filter(v => v !== value);
        }
        triggerAudienceEstimate();
    };

    window.onDynamicTextChange = function(criterionName) {
        const input = document.getElementById('dynCriterion_' + criterionName);
        const val = input ? input.value.trim() : '';
        dynamicCriteriaValues[criterionName] = val || null;
        triggerAudienceEstimate();
    };

    window.onDynamicBoolChange = function(criterionName) {
        const cb = document.getElementById('dynCriterion_' + criterionName);
        dynamicCriteriaValues[criterionName] = cb ? cb.checked : null;
        triggerAudienceEstimate();
    };

    // ── Sync hidden fields ─────────────────────────────────────
    function syncHiddenFields() {
        // Quiz data
        if (getFormat() === 'quiz') {
            const questions = [];
            document.querySelectorAll('.quiz-question-block').forEach(block => {
                const q = block.querySelector('.quiz-question-text').value.trim();
                const answers = Array.from(block.querySelectorAll('.quiz-answer')).map(a => a.value.trim());
                if (q) questions.push({ question: q, answers });
            });
            document.getElementById('hiddenQuizData').value = JSON.stringify(questions);
        }

        // Remove any previously generated targeting hidden inputs
        const form = document.getElementById('campaignForm');
        form.querySelectorAll('input[data-targeting]').forEach(el => el.remove());

        function addTargetingInput(name, value) {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = name;
            inp.value = value;
            inp.dataset.targeting = '1';
            form.appendChild(inp);
        }

        // Cities (array)
        const allCities = document.getElementById('allCities').checked;
        const cities = allCities
            ? []
            : Array.from(document.querySelectorAll('.city-checkbox:checked')).map(c => c.value);
        cities.forEach(v => addTargetingInput('targeting[cities][]', v));

        // Interests (array)
        selectedInterests.forEach(v => addTargetingInput('targeting[interests][]', v));

        // Genders (array)
        const genderAll = document.getElementById('genderAll').checked;
        if (!genderAll) {
            if (document.getElementById('genderM').checked) addTargetingInput('targeting[genders][]', 'male');
            if (document.getElementById('genderF').checked) addTargetingInput('targeting[genders][]', 'female');
        }

        // Operators (array)
        const allOps = document.getElementById('allOperators').checked;
        const ops = allOps
            ? []
            : Array.from(document.querySelectorAll('.op-checkbox:checked')).map(c => c.value);
        ops.forEach(v => addTargetingInput('targeting[operators][]', v));

        // Dynamic criteria (critères non-builtin stockés dans custom_fields)
        Object.entries(dynamicCriteriaValues).forEach(([key, val]) => {
            if (val === null || val === undefined) return;
            if (Array.isArray(val)) {
                if (val.length === 0) return;
                val.forEach(v => addTargetingInput(`targeting[${key}][]`, v));
            } else {
                addTargetingInput(`targeting[${key}]`, val);
            }
        });

        // Budget fields
        const cpv  = getCpv();
        const mode = document.querySelector('input[name=budget_mode]:checked').value;
        let total  = 0;
        let views  = 0;
        if (mode === 'views') {
            views = parseInt(document.getElementById('fieldViews').value) || 0;
            total = views * cpv;
        } else {
            total = parseInt(document.getElementById('fieldBudget').value) || 0;
            views = cpv > 0 ? Math.floor(total / cpv) : 0;
        }
        // Ensure budget field always has the final amount
        document.getElementById('fieldBudget').value = total;
        document.getElementById('fieldViews').value  = views;
        document.getElementById('hiddenCpv').value   = cpv;
    }

    // ── Build summary ──────────────────────────────────────────
    function buildSummary() {
        const f    = getFormat();
        const cfg  = f && FORMAT_CONFIG[f] ? FORMAT_CONFIG[f] : null;

        // Campaign
        document.getElementById('sumFormat').textContent   = cfg ? cfg.label : '—';
        document.getElementById('sumTitle').textContent    = document.getElementById('fieldTitle').value.trim() || '—';
        const desc = document.getElementById('fieldDescription').value.trim();
        document.getElementById('sumDesc').textContent     = desc || '—';
        const mFile = document.getElementById('mediaFileInput').files;
        document.getElementById('sumMedia').textContent    = mFile.length ? mFile[0].name + ' (' + formatBytes(mFile[0].size) + ')' : 'Non uploadé';
        document.getElementById('sumDuration').textContent = document.getElementById('fieldDuration').value + ' secondes';

        // Audience
        const mn = document.getElementById('ageMin').value || 16;
        const mx = document.getElementById('ageMax').value || 65;
        document.getElementById('sumAge').textContent = mn + ' – ' + mx + ' ans';

        const gAll = document.getElementById('genderAll').checked;
        const gM   = document.getElementById('genderM').checked;
        const gF   = document.getElementById('genderF').checked;
        document.getElementById('sumGender').textContent = gAll ? 'Tous' : [gM ? 'Hommes' : '', gF ? 'Femmes' : ''].filter(Boolean).join(' & ') || 'Tous';

        const allC  = document.getElementById('allCities').checked;
        const selC  = Array.from(document.querySelectorAll('.city-checkbox:checked')).map(c => c.value);
        document.getElementById('sumCities').textContent = (allC || selC.length === 0) ? 'Toutes les villes' : selC.join(', ');

        const allO  = document.getElementById('allOperators').checked;
        const selO  = Array.from(document.querySelectorAll('.op-checkbox:checked')).map(c => c.value);
        document.getElementById('sumOperators').textContent = (allO || selO.length === 0) ? 'Tous' : selO.map(o => o.charAt(0).toUpperCase() + o.slice(1)).join(', ');

        const interestEl = document.getElementById('sumInterests');
        if (selectedInterests.length) {
            interestEl.innerHTML = selectedInterests.map(i =>
                `<span class="summary-tag">${i}</span>`
            ).join('');
        } else {
            interestEl.textContent = 'Tous';
        }

        document.getElementById('sumAudience').textContent = estimatedAudience;

        // Budget
        const cpv   = getCpv();
        const mode  = document.querySelector('input[name=budget_mode]:checked').value;
        let total   = 0;
        let views   = 0;
        if (mode === 'views') {
            views = parseInt(document.getElementById('fieldViews').value) || 0;
            total = views * cpv;
        } else {
            total = parseInt(document.getElementById('fieldBudget').value) || 0;
            views = cpv > 0 ? Math.floor(total / cpv) : 0;
        }
        const subs = Math.round(total * 0.6);
        const plat = Math.round(total * 0.4);

        document.getElementById('sumViews').textContent       = fmt(views) + ' vues';
        document.getElementById('sumCpv').textContent         = cpv + ' FCFA';
        document.getElementById('sumSubscribers').textContent = fmt(subs) + ' FCFA';
        document.getElementById('sumPlatform').textContent    = fmt(plat) + ' FCFA';
        document.getElementById('sumTotal').textContent       = fmt(total) + ' FCFA';
        document.getElementById('btnPayAmount').textContent   = fmt(total);

        // Launch
        const lMode = document.querySelector('input[name=launch_mode]:checked').value;
        document.getElementById('sumLaunch').textContent = lMode === 'immediate' ? 'Immédiat' : 'Planifié';
        const sd = document.getElementById('fieldStartDate').value;
        const ed = document.getElementById('fieldEndDate').value;
        document.getElementById('sumStart').textContent = sd || '—';
        document.getElementById('sumEnd').textContent   = ed || '—';
    }

    // ── Init au chargement de la page ─────────────────────────
    // Construire FORMAT_CONFIG depuis les data- attributes des radios
    FORMAT_CONFIG = buildFormatConfig();

    updateCpvDisplay();

})();
</script>
@endsection
