<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CampaignResource;
use App\Models\Campaign;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentCampaignsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Campagnes récentes';

    protected int | string | array $columnSpan = 2;

    // =========================================================================

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Campaign::query()
                    ->with('advertiser')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('advertiser.name')
                    ->label('Annonceur')
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('title')
                    ->label('Campagne')
                    ->limit(35)
                    ->searchable(),

                TextColumn::make('progress')
                    ->label('Progression')
                    ->state(function (Campaign $record): string {
                        $pct = $record->max_views > 0
                            ? min(100, round(($record->views_count / $record->max_views) * 100))
                            : 0;
                        return "{$pct}%";
                    })
                    ->html()
                    ->formatStateUsing(function (string $state, Campaign $record): string {
                        $pct = $record->max_views > 0
                            ? min(100, round(($record->views_count / $record->max_views) * 100))
                            : 0;
                        return "
                            <div class='oon-progress-wrap'>
                                <div class='oon-progress-track' style='flex:1;height:5px;background:#EBF7FE;border-radius:3px;overflow:hidden;'>
                                    <div class='oon-progress-fill' style='width:{$pct}%;height:100%;background:#2AABF0;border-radius:3px;'></div>
                                </div>
                                <span class='oon-progress-pct' style='font-size:10px;font-weight:800;color:#5A7098;min-width:32px;text-align:right;'>{$pct}%</span>
                            </div>
                        ";
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
                        'active'         => 'Active',
                        'pending_review' => 'En attente',
                        'approved'       => 'Approuvée',
                        'paused'         => 'Suspendue',
                        'completed'      => 'Terminée',
                        'rejected'       => 'Rejetée',
                        'draft'          => 'Brouillon',
                        default          => $state,
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('voir_toutes')
                    ->label('Toutes les campagnes')
                    ->url(CampaignResource::getUrl('index'))
                    ->color('primary')
                    ->size('sm'),
            ])
            ->paginated(false);
    }
}
