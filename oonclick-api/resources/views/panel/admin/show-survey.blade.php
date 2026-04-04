@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Réponses — ' . $survey->title)

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'surveys'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo;
    <a href="{{ route('panel.admin.surveys') }}">Sondages</a> &rsaquo;
    <span class="current">{{ Str::limit($survey->title, 30) }}</span>
@endsection

@push('styles')
<style>
    .survey-meta { background:#fff; border:1px solid #E2E8F0; border-radius:14px; padding:20px 24px; margin-bottom:20px; }
    .meta-row { display:flex; gap:24px; flex-wrap:wrap; }
    .meta-item { }
    .meta-label { font-size:11px; color:#64748B; font-weight:600; text-transform:uppercase; margin-bottom:4px; }
    .meta-value { font-size:16px; font-weight:700; color:#0F172A; }
    .answer-cell { max-width:300px; font-size:12px; color:#475569; white-space:pre-wrap; word-break:break-word; }
    .badge-active { background:#DCFCE7; color:#15803D; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-inactive { background:#FEE2E2; color:#B91C1C; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
</style>
@endpush

@section('content')
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h1 class="page-title">{{ $survey->title }}</h1>
            @if($survey->description)
            <p style="color:#64748B;font-size:13px;margin-top:4px;">{{ $survey->description }}</p>
            @endif
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            @if($survey->is_active)
                <span class="badge-active">Actif</span>
            @else
                <span class="badge-inactive">Inactif</span>
            @endif
            <form action="{{ route('panel.admin.surveys.toggle', $survey) }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" style="padding:8px 16px;border-radius:8px;border:1px solid #E2E8F0;background:#fff;font-size:12px;font-weight:600;cursor:pointer;color:#64748B;">
                    {{ $survey->is_active ? 'Désactiver' : 'Activer' }}
                </button>
            </form>
        </div>
    </div>

    <div class="survey-meta">
        <div class="meta-row">
            <div class="meta-item">
                <div class="meta-label">Récompense</div>
                <div class="meta-value" style="color:#D97706;">{{ number_format($survey->reward_amount) }} FCFA</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">XP</div>
                <div class="meta-value" style="color:#7C3AED;">+{{ $survey->reward_xp }} XP</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Réponses</div>
                <div class="meta-value">{{ number_format($totalResponses) }}
                    @if($survey->max_responses)
                    <span style="font-size:12px;color:#94A3B8;">/ {{ number_format($survey->max_responses) }}</span>
                    @endif
                </div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Questions</div>
                <div class="meta-value">{{ count($survey->questions) }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Expiration</div>
                <div class="meta-value">{{ $survey->expires_at ? $survey->expires_at->format('d/m/Y') : 'Aucune' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 style="font-size:14px;font-weight:700;color:#0F172A;margin-bottom:16px;">
                Réponses ({{ $responses->total() }})
            </h3>

            @if($responses->isEmpty())
            <div style="text-align:center;padding:40px;color:#94A3B8;">
                <p style="font-weight:600;">Aucune réponse pour l'instant.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Utilisateur</th>
                            <th>Réponses</th>
                            <th>Crédité</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($responses as $response)
                        <tr>
                            <td style="color:#94A3B8;font-size:12px;">{{ $response->id }}</td>
                            <td>
                                <div style="font-weight:600;font-size:13px;">{{ $response->user->name ?? 'Utilisateur #' . $response->user_id }}</div>
                                <div style="font-size:11px;color:#94A3B8;">{{ $response->user->phone ?? '' }}</div>
                            </td>
                            <td>
                                <div class="answer-cell">
                                    @foreach((array) $response->answers as $qIdx => $answer)
                                        @php $question = $survey->questions[$qIdx] ?? null; @endphp
                                        @if($question)
                                        <div style="margin-bottom:4px;">
                                            <span style="font-weight:600;color:#475569;">Q{{ $qIdx + 1 }}:</span>
                                            {{ is_array($answer) ? implode(', ', $answer) : $answer }}
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                @if($response->credited)
                                    <span style="color:#15803D;font-weight:600;font-size:12px;">&#10003; Oui</span>
                                @else
                                    <span style="color:#94A3B8;font-size:12px;">Non</span>
                                @endif
                            </td>
                            <td style="font-size:12px;color:#64748B;">
                                {{ $response->completed_at ? $response->completed_at->format('d/m/Y H:i') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;">
                {{ $responses->links() }}
            </div>
            @endif
        </div>
    </div>
@endsection
