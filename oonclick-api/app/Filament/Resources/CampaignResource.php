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
                    'banner'  => 'Bannière',
                    'audio'   => 'Audio',
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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('advertiser.name')
                    ->label('Annonceur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('format')
                    ->label('Format')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'video'  => 'info',
                        'banner' => 'warning',
                        'audio'  => 'success',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'video'  => 'Vidéo',
                        'banner' => 'Bannière',
                        'audio'  => 'Audio',
                        default  => $state,
                    }),

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

                TextColumn::make('budget')
                    ->label('Budget')
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->sortable(),

                TextColumn::make('views_progress')
                    ->label('Vues')
                    ->state(fn (Campaign $record): string => $record->views_count . ' / ' . $record->max_views)
                    ->description(fn (Campaign $record): string => $record->max_views > 0
                        ? round(($record->views_count / $record->max_views) * 100) . '%'
                        : '0%'
                    ),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable(),
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
                        'video'  => 'Vidéo',
                        'banner' => 'Bannière',
                        'audio'  => 'Audio',
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
