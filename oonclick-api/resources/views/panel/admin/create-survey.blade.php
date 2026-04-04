@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Créer un sondage')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'surveys'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo;
    <a href="{{ route('panel.admin.surveys') }}">Sondages</a> &rsaquo;
    <span class="current">Créer</span>
@endsection

@push('styles')
<style>
    .form-section { background:#fff; border:1px solid #E2E8F0; border-radius:14px; padding:24px; margin-bottom:20px; }
    .form-section-title { font-size:15px; font-weight:800; color:#0F172A; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid #F1F5F9; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .form-group { margin-bottom:16px; }
    .form-label { display:block; font-size:12px; font-weight:600; color:#64748B; margin-bottom:6px; }
    .form-input { width:100%; border:1px solid #E2E8F0; border-radius:10px; padding:9px 14px; font-size:13px; color:#0F172A; outline:none; box-sizing:border-box; transition:border-color .15s; }
    .form-input:focus { border-color:#0EA5E9; box-shadow:0 0 0 3px rgba(14,165,233,0.08); }
    .form-hint { font-size:11px; color:#94A3B8; margin-top:4px; }
    .question-card { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:16px; margin-bottom:12px; }
    .question-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
    .question-label { font-size:12px; font-weight:700; color:#475569; }
    .remove-btn { background:none; border:none; color:#EF4444; cursor:pointer; font-size:12px; font-weight:600; padding:4px 8px; border-radius:6px; }
    .remove-btn:hover { background:#FEE2E2; }
    .add-btn { background:#F0F9FF; border:1px dashed #0EA5E9; color:#0EA5E9; padding:10px 20px; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; width:100%; margin-top:8px; }
    .add-btn:hover { background:#E0F2FE; }
    .options-list { margin-top:8px; }
    .option-row { display:flex; gap:8px; align-items:center; margin-bottom:6px; }
    .option-input { flex:1; border:1px solid #E2E8F0; border-radius:8px; padding:7px 12px; font-size:12px; color:#0F172A; outline:none; }
    .option-input:focus { border-color:#0EA5E9; }
    .add-option-btn { background:none; border:1px solid #CBD5E1; color:#64748B; padding:5px 10px; border-radius:7px; font-size:11px; cursor:pointer; }
    .remove-option-btn { background:none; border:none; color:#EF4444; cursor:pointer; font-size:16px; line-height:1; padding:2px 4px; }
    .submit-btn { background:linear-gradient(135deg,#2AABF0,#0E7AB8); color:#fff; padding:13px 32px; border-radius:12px; font-size:14px; font-weight:700; border:none; cursor:pointer; }
    .submit-btn:hover { opacity:0.9; }
    .error-list { background:#FEE2E2; color:#B91C1C; border:1px solid #FECACA; border-radius:10px; padding:12px 16px; margin-bottom:20px; font-size:13px; }
    .error-list li { margin-bottom:4px; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <h1 class="page-title">Créer un sondage</h1>
    </div>

    @if($errors->any())
    <div class="error-list">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('panel.admin.surveys.store') }}" method="POST" id="surveyForm">
        @csrf

        {{-- Informations générales --}}
        <div class="form-section">
            <div class="form-section-title">Informations générales</div>

            <div class="form-group">
                <label class="form-label">Titre <span style="color:#EF4444">*</span></label>
                <input type="text" name="title" class="form-input" placeholder="Ex : Vos habitudes d'achat en ligne" value="{{ old('title') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3" placeholder="Décrivez brièvement l'objectif du sondage…">{{ old('description') }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Récompense FCFA <span style="color:#EF4444">*</span></label>
                    <input type="number" name="reward_amount" class="form-input" placeholder="Ex : 500" min="1" value="{{ old('reward_amount', 100) }}" required>
                    <div class="form-hint">Montant crédité sur le wallet de l'abonné.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Récompense XP</label>
                    <input type="number" name="reward_xp" class="form-input" placeholder="Ex : 20" min="0" value="{{ old('reward_xp', 20) }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Quota maximum de réponses</label>
                    <input type="number" name="max_responses" class="form-input" placeholder="Laisser vide = illimité" min="1" value="{{ old('max_responses') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Date d'expiration</label>
                    <input type="datetime-local" name="expires_at" class="form-input" value="{{ old('expires_at') }}">
                </div>
            </div>
        </div>

        {{-- Questions --}}
        <div class="form-section">
            <div class="form-section-title">Questions</div>

            <div id="questionsList"></div>

            <button type="button" class="add-btn" onclick="addQuestion()">
                + Ajouter une question
            </button>

            <input type="hidden" name="questions" id="questionsInput">
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;">
            <a href="{{ route('panel.admin.surveys') }}" style="padding:12px 24px;border:1px solid #E2E8F0;border-radius:12px;color:#64748B;font-size:14px;font-weight:600;text-decoration:none;">
                Annuler
            </a>
            <button type="submit" class="submit-btn">Créer le sondage</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
let questions = [];

function addQuestion() {
    const id = Date.now();
    questions.push({ id, type: 'radio', text: '', options: ['', ''], required: true });
    renderQuestions();
}

function removeQuestion(id) {
    questions = questions.filter(q => q.id !== id);
    renderQuestions();
}

function addOption(id) {
    const q = questions.find(q => q.id === id);
    if (q) q.options.push('');
    renderQuestions();
}

function removeOption(qId, idx) {
    const q = questions.find(q => q.id === qId);
    if (q && q.options.length > 1) q.options.splice(idx, 1);
    renderQuestions();
}

function renderQuestions() {
    const list = document.getElementById('questionsList');
    list.innerHTML = '';

    questions.forEach((q, idx) => {
        const card = document.createElement('div');
        card.className = 'question-card';

        const needsOptions = q.type === 'radio' || q.type === 'checkbox';

        let optionsHtml = '';
        if (needsOptions) {
            optionsHtml = `<div class="options-list" id="opts_${q.id}">
                ${q.options.map((opt, oi) => `
                    <div class="option-row">
                        <input class="option-input" type="text" placeholder="Option ${oi + 1}" value="${escHtml(opt)}"
                            oninput="updateOption(${q.id}, ${oi}, this.value)">
                        <button type="button" class="remove-option-btn" onclick="removeOption(${q.id}, ${oi})">×</button>
                    </div>
                `).join('')}
                <button type="button" class="add-option-btn" onclick="addOption(${q.id})">+ Ajouter une option</button>
            </div>`;
        }

        card.innerHTML = `
            <div class="question-header">
                <span class="question-label">Question ${idx + 1}</span>
                <button type="button" class="remove-btn" onclick="removeQuestion(${q.id})">Supprimer</button>
            </div>
            <div class="form-group">
                <label class="form-label">Texte de la question</label>
                <input type="text" class="form-input" placeholder="Ex : Utilisez-vous des applications de shopping ?" value="${escHtml(q.text)}"
                    oninput="updateQuestion(${q.id}, 'text', this.value)">
            </div>
            <div style="display:flex;gap:16px;margin-bottom:12px;">
                <div style="flex:1;">
                    <label class="form-label">Type</label>
                    <select class="form-input" onchange="updateQuestion(${q.id}, 'type', this.value)">
                        <option value="radio" ${q.type === 'radio' ? 'selected' : ''}>Choix unique (radio)</option>
                        <option value="checkbox" ${q.type === 'checkbox' ? 'selected' : ''}>Choix multiple (checkbox)</option>
                        <option value="text" ${q.type === 'text' ? 'selected' : ''}>Réponse libre (texte)</option>
                    </select>
                </div>
                <div style="flex:0 0 150px;align-self:flex-end;">
                    <label class="form-label">Obligatoire</label>
                    <select class="form-input" onchange="updateQuestion(${q.id}, 'required', this.value === 'true')">
                        <option value="true" ${q.required ? 'selected' : ''}>Oui</option>
                        <option value="false" ${!q.required ? 'selected' : ''}>Non</option>
                    </select>
                </div>
            </div>
            ${optionsHtml}
        `;

        list.appendChild(card);
    });

    syncQuestionsInput();
}

function updateQuestion(id, field, value) {
    const q = questions.find(q => q.id === id);
    if (q) {
        q[field] = value;
        if (field === 'type') renderQuestions();
        else syncQuestionsInput();
    }
}

function updateOption(qId, idx, value) {
    const q = questions.find(q => q.id === qId);
    if (q) q.options[idx] = value;
    syncQuestionsInput();
}

function syncQuestionsInput() {
    const payload = questions.map(q => {
        const item = { type: q.type, text: q.text, required: q.required };
        if (q.type !== 'text') item.options = q.options.filter(o => o.trim() !== '');
        return item;
    });
    document.getElementById('questionsInput').value = JSON.stringify(payload);
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Init with 1 question
addQuestion();

// Validate before submit
document.getElementById('surveyForm').addEventListener('submit', function(e) {
    if (questions.length === 0) {
        e.preventDefault();
        alert('Veuillez ajouter au moins une question.');
        return;
    }
    syncQuestionsInput();
});
</script>
@endpush
