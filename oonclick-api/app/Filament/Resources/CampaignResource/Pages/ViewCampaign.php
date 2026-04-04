<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use App\Models\Campaign;
use App\Modules\Campaign\Services\CampaignService;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCampaign extends ViewRecord
{
    protected static string $resource = CampaignResource::class;

    // =========================================================================
    // Header actions — Approve / Reject / Pause / Resume
    // =========================================================================

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approuver')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'pending_review')
                ->requiresConfirmation()
                ->modalHeading('Approuver la campagne')
                ->modalDescription('La campagne sera activée et diffusée auprès des abonnés.')
                ->modalSubmitActionLabel('Approuver')
                ->action(function (): void {
                    app(CampaignService::class)->approve($this->record, auth()->id());
                    Notification::make()->title('Campagne approuvée')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('reject')
                ->label('Rejeter')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === 'pending_review')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Motif du rejet')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(CampaignService::class)->reject($this->record, auth()->id(), $data['reason']);
                    Notification::make()->title('Campagne rejetée')->danger()->send();
                    $this->refreshFormData(['status']);
                })
                ->modalHeading('Rejeter la campagne')
                ->modalSubmitActionLabel('Confirmer le rejet'),

            Action::make('pause')
                ->label('Suspendre')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->visible(fn (): bool => $this->record->status === 'active')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['status' => 'paused']);
                    $this->refreshFormData(['status']);
                }),

            Action::make('resume')
                ->label('Reprendre')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'paused')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['status' => 'active']);
                    $this->refreshFormData(['status']);
                }),
        ];
    }

    // =========================================================================
    // Infolist — rich detail matching design w-06
    // =========================================================================

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ─── KPIs row ───────────────────────────────────────────────────
            Section::make('Performances')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('views_count')
                            ->label('Vues complétées')
                            ->state(fn (Campaign $record): string =>
                                number_format($record->views_count ?? 0, 0, ',', ' ')
                            )
                            ->badge()
                            ->color('info'),

                        TextEntry::make('max_views')
                            ->label('Objectif de vues')
                            ->state(fn (Campaign $record): string =>
                                number_format($record->max_views ?? 0, 0, ',', ' ')
                            )
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('completion_rate')
                            ->label('Taux de complétion')
                            ->state(function (Campaign $record): string {
                                $rate = $record->max_views > 0
                                    ? min(100, round(($record->views_count / $record->max_views) * 100))
                                    : 0;
                                return $rate . '%';
                            })
                            ->badge()
                            ->color(function (Campaign $record): string {
                                $rate = $record->max_views > 0
                                    ? min(100, round(($record->views_count / $record->max_views) * 100))
                                    : 0;
                                return $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger');
                            }),

                        TextEntry::make('budget_consumed')
                            ->label('Budget consommé (FCFA)')
                            ->state(fn (Campaign $record): string =>
                                number_format(($record->views_count ?? 0) * ($record->cost_per_view ?? 0), 0, ',', ' ')
                                . ' / '
                                . number_format($record->budget ?? 0, 0, ',', ' ')
                            )
                            ->badge()
                            ->color('primary'),
                    ]),
                ]),

            // ─── Informations générales ─────────────────────────────────────
            Section::make('Informations générales')
                ->columns(2)
                ->schema([
                    TextEntry::make('title')
                        ->label('Titre')
                        ->weight('bold'),

                    TextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'active'         => 'success',
                            'pending_review' => 'warning',
                            'approved'       => 'info',
                            'paused'         => 'gray',
                            'completed'      => 'primary',
                            'rejected'       => 'danger',
                            default          => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'active'         => 'Actif',
                            'pending_review' => 'En attente de validation',
                            'approved'       => 'Approuvé',
                            'paused'         => 'En pause',
                            'completed'      => 'Terminé',
                            'rejected'       => 'Rejeté',
                            'draft'          => 'Brouillon',
                            default          => $state,
                        }),

                    TextEntry::make('format')
                        ->label('Format')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'video'   => 'info',
                            'image'   => 'success',
                            'quiz'    => 'warning',
                            'flash'   => 'danger',
                            'scratch' => 'primary',
                            default   => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'video'   => 'Vidéo',
                            'image'   => 'Image',
                            'quiz'    => 'Quiz',
                            'flash'   => 'Flash',
                            'scratch' => 'Grattage',
                            default   => $state,
                        }),

                    TextEntry::make('duration_seconds')
                        ->label('Durée')
                        ->state(fn (Campaign $record): string =>
                            ($record->duration_seconds ?? 0) . ' secondes'
                        ),

                    TextEntry::make('description')
                        ->label('Description')
                        ->columnSpanFull()
                        ->default('—'),
                ]),

            // ─── Média ──────────────────────────────────────────────────────
            Section::make('Contenu & Média')
                ->columns(2)
                ->schema([
                    TextEntry::make('media_url')
                        ->label('URL du média')
                        ->url(fn (Campaign $record): string => $record->media_url ?? '')
                        ->openUrlInNewTab()
                        ->default('—'),

                    TextEntry::make('thumbnail_url')
                        ->label('Vignette')
                        ->url(fn (Campaign $record): string => $record->thumbnail_url ?? '')
                        ->openUrlInNewTab()
                        ->default('—'),
                ]),

            // ─── Budget & tarification ──────────────────────────────────────
            Section::make('Budget & Tarification')
                ->columns(3)
                ->schema([
                    TextEntry::make('budget')
                        ->label('Budget total')
                        ->state(fn (Campaign $record): string =>
                            number_format($record->budget ?? 0, 0, ',', ' ') . ' FCFA'
                        )
                        ->weight('bold')
                        ->color('primary'),

                    TextEntry::make('cost_per_view')
                        ->label('Coût par vue (annonceur)')
                        ->state(fn (Campaign $record): string =>
                            number_format($record->cost_per_view ?? 0, 0, ',', ' ') . ' FCFA'
                        ),

                    TextEntry::make('amount')
                        ->label('Gains par vue (abonné)')
                        ->state(fn (Campaign $record): string =>
                            number_format($record->amount ?? 0, 0, ',', ' ') . ' FCFA'
                        )
                        ->color('success'),
                ]),

            // ─── Annonceur ──────────────────────────────────────────────────
            Section::make('Annonceur')
                ->columns(3)
                ->schema([
                    TextEntry::make('advertiser.name')
                        ->label('Nom')
                        ->default('—'),

                    TextEntry::make('advertiser.email')
                        ->label('Email')
                        ->default('—'),

                    TextEntry::make('advertiser.kyc_level')
                        ->label('Niveau KYC')
                        ->badge()
                        ->color(fn ($state): string => match ((int) $state) {
                            3 => 'success', 2 => 'info', 1 => 'warning', default => 'gray',
                        })
                        ->formatStateUsing(fn ($state): string => 'Niv. ' . $state),
                ]),

            // ─── Dates ──────────────────────────────────────────────────────
            Section::make('Planification')
                ->columns(3)
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Créé le')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('starts_at')
                        ->label('Début')
                        ->dateTime('d/m/Y H:i')
                        ->default('Non défini'),

                    TextEntry::make('ends_at')
                        ->label('Fin')
                        ->dateTime('d/m/Y H:i')
                        ->default('Non défini'),
                ]),
        ]);
    }
}
