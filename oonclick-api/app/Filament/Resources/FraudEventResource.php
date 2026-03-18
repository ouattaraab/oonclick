<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FraudEventResource\Pages;
use App\Models\FraudEvent;
use Filament\Forms\Form;
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

    protected static ?string $navigationLabel = 'Fraudes';

    protected static ?string $modelLabel = 'Événement de fraude';

    protected static ?string $pluralModelLabel = 'Événements de fraude';

    protected static ?int $navigationSort = 4;

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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.phone')
                    ->label('Téléphone')
                    ->searchable(),

                TextColumn::make('user.trust_score')
                    ->label('Score confiance')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 70 => 'success',
                        $state >= 40 => 'warning',
                        default      => 'danger',
                    })
                    ->formatStateUsing(fn ($state): string => "{$state}/100"),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('severity')
                    ->label('Sévérité')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low'      => 'gray',
                        'medium'   => 'warning',
                        'high'     => 'orange',
                        'critical' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low'      => 'Faible',
                        'medium'   => 'Moyen',
                        'high'     => 'Élevé',
                        'critical' => 'Critique',
                        default    => $state,
                    }),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->tooltip(fn (FraudEvent $record): string => $record->description ?? ''),

                TextColumn::make('is_resolved')
                    ->label('Résolu')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Résolu' : 'En cours'),

                TextColumn::make('created_at')
                    ->label('Détecté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'fake_view'         => 'Fausse vue',
                        'multiple_accounts' => 'Comptes multiples',
                        'suspicious_ip'     => 'IP suspecte',
                        'bot_activity'      => 'Activité bot',
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
                    ->query(fn (Builder $query): Builder => $query->where('is_resolved', false)),
            ])
            ->actions([
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
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Résoudre l\'événement de fraude')
                    ->modalDescription('Marquer cet événement comme résolu et recalculer le score de confiance de l\'utilisateur ?')
                    ->modalSubmitActionLabel('Confirmer la résolution'),
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
