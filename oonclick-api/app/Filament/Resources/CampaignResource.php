<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Filament\Resources\CampaignResource\Widgets\CampaignStatsOverview;
use App\Models\Campaign;
use App\Modules\Campaign\Services\CampaignService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Campagnes';

    protected static ?string $modelLabel = 'Campagne';

    protected static ?string $pluralModelLabel = 'Campagnes';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Principal';

    // =========================================================================
    // Navigation badge — campagnes en attente de validation
    // =========================================================================

    public static function getNavigationBadge(): ?string
    {
        $pending = Campaign::where('status', 'pending_review')->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // =========================================================================
    // Form
    // =========================================================================

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('Titre')
                ->required()
                ->maxLength(255),

            Select::make('format')
                ->label('Format')
                ->options([
                    'video'   => 'Vidéo',
                    'image'   => 'Image',
                    'quiz'    => 'Quiz',
                    'flash'   => 'Flash',
                    'scratch' => 'Grattage',
                ])
                ->required(),

            Select::make('status')
                ->label('Statut')
                ->options([
                    'draft'          => 'Brouillon',
                    'pending_review' => 'En attente',
                    'approved'       => 'Approuvé',
                    'active'         => 'Actif',
                    'paused'         => 'En pause',
                    'completed'      => 'Terminé',
                    'rejected'       => 'Rejeté',
                ])
                ->required(),

            TextInput::make('budget')
                ->label('Budget (FCFA)')
                ->numeric()
                ->required(),

            TextInput::make('cost_per_view')
                ->label('Coût par vue (FCFA)')
                ->numeric()
                ->required(),

            TextInput::make('max_views')
                ->label('Vues maximum')
                ->numeric(),
        ]);
    }

    // =========================================================================
    // Table
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Annonceur avec avatar initiales
                TextColumn::make('advertiser.name')
                    ->label('Annonceur')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '<span style="color:#5A7098;">—</span>';
                        }
                        $initial = mb_strtoupper(mb_substr($state, 0, 1));
                        return "
                            <div style='display:flex;align-items:center;gap:8px;'>
                                <div style='width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#2AABF0,#1B2A6E);display:flex;align-items:center;justify-content:center;color:white;font-weight:900;font-size:12px;flex-shrink:0;'>{$initial}</div>
                                <span style='font-size:12px;font-weight:800;color:#1B2A6E;'>{$state}</span>
                            </div>
                        ";
                    })
                    ->html(),

                TextColumn::make('title')
                    ->label('Campagne')
                    ->searchable()
                    ->limit(32)
                    ->weight('semibold'),

                // Format pill coloré
                TextColumn::make('format')
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

                // Progression vues/budget
                TextColumn::make('views_progress')
                    ->label('Progression')
                    ->state(fn (Campaign $record): string => '')
                    ->formatStateUsing(function (string $state, Campaign $record): string {
                        $pct = $record->max_views > 0
                            ? min(100, round(($record->views_count / $record->max_views) * 100))
                            : 0;
                        $views = number_format($record->views_count, 0, ',', ' ');
                        $max   = number_format($record->max_views ?? 0, 0, ',', ' ');
                        return "
                            <div style='min-width:100px;'>
                                <div style='display:flex;justify-content:space-between;margin-bottom:3px;'>
                                    <span style='font-size:10px;color:#5A7098;font-weight:600;'>{$views}/{$max}</span>
                                    <span style='font-size:10px;font-weight:800;color:#2AABF0;'>{$pct}%</span>
                                </div>
                                <div style='height:5px;background:#EBF7FE;border-radius:3px;overflow:hidden;'>
                                    <div style='width:{$pct}%;height:100%;background:#2AABF0;border-radius:3px;'></div>
                                </div>
                            </div>
                        ";
                    })
                    ->html(),

                // Budget
                TextColumn::make('budget')
                    ->label('Budget')
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' F')
                    ->sortable()
                    ->weight('bold'),

                // Statut badge coloré
                TextColumn::make('status')
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
                        'pending_review' => 'En attente',
                        'approved'       => 'Approuvé',
                        'paused'         => 'En pause',
                        'completed'      => 'Terminé',
                        'rejected'       => 'Rejeté',
                        'draft'          => 'Brouillon',
                        default          => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft'          => 'Brouillon',
                        'pending_review' => 'En attente',
                        'approved'       => 'Approuvé',
                        'active'         => 'Actif',
                        'paused'         => 'En pause',
                        'completed'      => 'Terminé',
                        'rejected'       => 'Rejeté',
                    ]),

                SelectFilter::make('format')
                    ->label('Format')
                    ->options([
                        'video'   => 'Vidéo',
                        'image'   => 'Image',
                        'quiz'    => 'Quiz',
                        'flash'   => 'Flash',
                        'scratch' => 'Grattage',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Campaign $record): bool => $record->status === 'pending_review')
                    ->action(function (Campaign $record): void {
                        app(CampaignService::class)->approve($record, auth()->id());
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approuver la campagne')
                    ->modalDescription('Confirmer l\'approbation de cette campagne ?')
                    ->modalSubmitActionLabel('Approuver'),

                Action::make('pause')
                    ->label('Pause')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn (Campaign $record): bool => $record->status === 'active')
                    ->action(function (Campaign $record): void {
                        $record->update(['status' => 'paused']);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Mettre en pause')
                    ->modalDescription('Suspendre temporairement la diffusion de cette campagne ?')
                    ->modalSubmitActionLabel('Mettre en pause'),

                Action::make('resume')
                    ->label('Reprendre')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (Campaign $record): bool => $record->status === 'paused')
                    ->action(function (Campaign $record): void {
                        $record->update(['status' => 'active']);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reprendre la campagne')
                    ->modalDescription('Relancer la diffusion de cette campagne ?')
                    ->modalSubmitActionLabel('Reprendre'),

                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Campaign $record): bool => $record->status === 'pending_review')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Campaign $record, array $data): void {
                        app(CampaignService::class)->reject($record, auth()->id(), $data['reason']);
                    })
                    ->modalHeading('Rejeter la campagne')
                    ->modalDescription('Veuillez indiquer le motif du rejet.')
                    ->modalSubmitActionLabel('Confirmer le rejet'),

                ViewAction::make()
                    ->label('Détails'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // =========================================================================
    // Widgets
    // =========================================================================

    public static function getWidgets(): array
    {
        return [
            CampaignStatsOverview::class,
        ];
    }

    // =========================================================================
    // Pages
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCampaigns::route('/'),
            'view'   => Pages\ViewCampaign::route('/{record}'),
            'edit'   => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
