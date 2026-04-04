<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FraudEventResource;
use App\Models\FraudEvent;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FraudAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Alertes fraude';

    protected int | string | array $columnSpan = 1;

    protected static ?string $pollingInterval = '30s';

    // =========================================================================

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FraudEvent::query()
                    ->with('user')
                    ->where('is_resolved', false)
                    ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('type')
                    ->label('Événement')
                    ->formatStateUsing(function (string $state, FraudEvent $record): string {
                        $icon = match ($state) {
                            'bot_activity'      => '🤖',
                            'rapid_views'       => '⚡',
                            'fake_view'         => '👁',
                            'multiple_accounts' => '👥',
                            'suspicious_ip'     => '🌐',
                            default             => '⚠️',
                        };
                        $typeLabel = match ($state) {
                            'fake_view'         => 'Fausse vue',
                            'multiple_accounts' => 'Multi-comptes',
                            'suspicious_ip'     => 'IP suspecte',
                            'bot_activity'      => 'Bot détecté',
                            'rapid_views'       => 'Vues rapides',
                            default             => $state,
                        };
                        $user = $record->user?->name ?? 'Inconnu';
                        $ago  = $record->created_at?->diffForHumans() ?? '';
                        return "
                            <div style='display:flex;align-items:flex-start;gap:8px;'>
                                <div style='font-size:18px;flex-shrink:0;'>{$icon}</div>
                                <div>
                                    <div style='font-size:12px;font-weight:800;color:#1B2A6E;'>{$typeLabel}</div>
                                    <div style='font-size:10px;color:#5A7098;font-weight:600;margin-top:1px;'>{$user} · {$ago}</div>
                                </div>
                            </div>
                        ";
                    })
                    ->html(),

                TextColumn::make('severity')
                    ->label('Sévérité')
                    ->formatStateUsing(function (string $state): string {
                        $label = match ($state) {
                            'critical' => 'Critique',
                            'high'     => 'Élevé',
                            'medium'   => 'Moyen',
                            'low'      => 'Faible',
                            default    => $state,
                        };
                        $style = match ($state) {
                            'critical' => 'background:#FEE2E2;color:#B91C1C;',
                            'high'     => 'background:#FEF3C7;color:#92400E;',
                            'medium'   => 'background:#FEF9C3;color:#A16207;',
                            'low'      => 'background:#DCFCE7;color:#15803D;',
                            default    => 'background:#F1F5F9;color:#475569;',
                        };
                        return "<span style='display:inline-block;padding:2px 8px;border-radius:10px;font-size:9px;font-weight:800;{$style}'>{$label}</span>";
                    })
                    ->html(),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->size('sm')
                    ->action(function (FraudEvent $record): void {
                        $record->update([
                            'is_resolved' => true,
                            'resolved_at' => now(),
                            'resolved_by' => auth()->id(),
                        ]);

                        $user = $record->user;
                        if ($user) {
                            $totalImpact = FraudEvent::where('user_id', $user->id)
                                ->where('is_resolved', false)
                                ->sum('trust_score_impact');
                            $newScore = max(0, min(100, 100 - abs((int) $totalImpact)));
                            $user->update(['trust_score' => $newScore]);
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Résoudre l\'alerte')
                    ->modalDescription('Marquer cet événement comme résolu ?')
                    ->modalSubmitActionLabel('Confirmer'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('voir_toutes')
                    ->label('Voir toutes')
                    ->url(FraudEventResource::getUrl('index'))
                    ->color('danger')
                    ->size('sm'),
            ])
            ->paginated(false);
    }
}
