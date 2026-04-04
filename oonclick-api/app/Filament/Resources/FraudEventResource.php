<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FraudEventResource\Pages;
use App\Models\FraudEvent;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FraudEventResource extends Resource
{
    protected static ?string $model = FraudEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Anti-fraude';

    protected static ?string $modelLabel = 'Événement de fraude';

    protected static ?string $pluralModelLabel = 'Événements de fraude';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Outils';

    // =========================================================================
    // Navigation badge — alertes non résolues
    // =========================================================================

    public static function getNavigationBadge(): ?string
    {
        $open = FraudEvent::where('is_resolved', false)->count();
        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $critical = FraudEvent::where('is_resolved', false)->where('severity', 'critical')->count();
        return $critical > 0 ? 'danger' : 'warning';
    }

    // =========================================================================
    // Form
    // =========================================================================

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    // =========================================================================
    // Table
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Utilisateur
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (?string $state, FraudEvent $record): string {
                        if (! $state) {
                            return '<span style="color:#5A7098;">Inconnu</span>';
                        }
                        $initial = mb_strtoupper(mb_substr($state, 0, 1));
                        $phone   = e($record->user?->phone ?? '');
                        $score   = (int) ($record->user?->trust_score ?? 0);
                        $scoreColor = $score >= 70 ? '#16A34A' : ($score >= 40 ? '#D97706' : '#DC2626');
                        return "
                            <div style='display:flex;align-items:center;gap:8px;'>
                                <div style='width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#2AABF0,#1B2A6E);display:flex;align-items:center;justify-content:center;color:white;font-weight:900;font-size:12px;flex-shrink:0;'>{$initial}</div>
                                <div>
                                    <div style='font-size:12px;font-weight:800;color:#1B2A6E;'>{$state}</div>
                                    <div style='font-size:10px;color:#5A7098;font-weight:600;'>{$phone}</div>
                                </div>
                            </div>
                        ";
                    })
                    ->html(),

                // Type d'événement
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fake_view'         => 'Fausse vue',
                        'multiple_accounts' => 'Multi-comptes',
                        'suspicious_ip'     => 'IP suspecte',
                        'bot_activity'      => 'Bot',
                        'rapid_views'       => 'Vues rapides',
                        default             => $state,
                    })
                    ->searchable(),

                // Sévérité pill colorée
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
                        return "<span style='display:inline-block;padding:2px 10px;border-radius:10px;font-size:10px;font-weight:800;{$style}'>{$label}</span>";
                    })
                    ->html()
                    ->sortable(),

                // Impact Trust Score
                TextColumn::make('trust_score_impact')
                    ->label('Impact Score')
                    ->formatStateUsing(fn ($state): string => $state
                        ? "<span style='font-size:12px;font-weight:800;color:#DC2626;'>-" . abs((int) $state) . " pts</span>"
                        : '<span style="color:#5A7098;">—</span>'
                    )
                    ->html(),

                // Description courte
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn (FraudEvent $record): string => $record->description ?? ''),

                // Statut
                TextColumn::make('is_resolved')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Résolu' : 'En cours'),

                TextColumn::make('created_at')
                    ->label('Détecté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'fake_view'         => 'Fausse vue',
                        'multiple_accounts' => 'Multi-comptes',
                        'suspicious_ip'     => 'IP suspecte',
                        'bot_activity'      => 'Bot',
                        'rapid_views'       => 'Vues rapides',
                    ]),

                SelectFilter::make('severity')
                    ->label('Sévérité')
                    ->options([
                        'low'      => 'Faible',
                        'medium'   => 'Moyen',
                        'high'     => 'Élevé',
                        'critical' => 'Critique',
                    ]),

                Filter::make('is_resolved')
                    ->label('Non résolus uniquement')
                    ->query(fn (Builder $query): Builder => $query->where('is_resolved', false))
                    ->default(),
            ])
            ->actions([
                // Résoudre
                Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (FraudEvent $record): bool => ! $record->is_resolved)
                    ->action(function (FraudEvent $record): void {
                        $record->update([
                            'is_resolved' => true,
                            'resolved_at' => now(),
                            'resolved_by' => auth()->id(),
                        ]);

                        // Recalcul du trust score de l'utilisateur
                        $user = $record->user;
                        if ($user) {
                            $totalImpact = FraudEvent::where('user_id', $user->id)
                                ->where('is_resolved', false)
                                ->sum('trust_score_impact');
                            $newScore = max(0, min(100, 100 - abs((int) $totalImpact)));
                            $user->update(['trust_score' => $newScore]);
                        }

                        Notification::make()
                            ->title('Alerte résolue')
                            ->body('Le score de confiance de l\'utilisateur a été recalculé.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Résoudre l\'événement de fraude')
                    ->modalDescription('Marquer cet événement comme résolu et recalculer le score de confiance de l\'utilisateur ?')
                    ->modalSubmitActionLabel('Confirmer la résolution'),

                // Suspendre l'utilisateur directement depuis l'alerte
                Action::make('suspend_user')
                    ->label('Suspendre')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (FraudEvent $record): bool => $record->user && ! $record->user->is_suspended)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Motif de suspension')
                            ->required()
                            ->default(fn (FraudEvent $record): string => "Suspension suite à fraude détectée : {$record->type}")
                            ->rows(3),
                    ])
                    ->action(function (FraudEvent $record, array $data): void {
                        $record->user?->update([
                            'is_suspended'      => true,
                            'suspension_reason' => $data['reason'],
                        ]);
                        Notification::make()
                            ->title('Utilisateur suspendu')
                            ->danger()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Suspendre l\'utilisateur')
                    ->modalDescription('Suspendre l\'utilisateur associé à cette alerte de fraude ?')
                    ->modalSubmitActionLabel('Suspendre'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // =========================================================================
    // Pages
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFraudEvents::route('/'),
        ];
    }
}
