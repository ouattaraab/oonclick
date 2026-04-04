<?php

namespace App\Filament\Advertiser\Widgets;

use App\Models\Campaign;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AdvertiserCampaignsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Mes campagnes actives';

    protected int | string | array $columnSpan = 'full';

    // =========================================================================

    public function table(Table $table): Table
    {
        $advertiserId = auth()->id();

        return $table
            ->query(
                Campaign::query()
                    ->where('advertiser_id', $advertiserId)
                    ->whereIn('status', ['active', 'approved', 'pending_review'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Campagne')
                    ->formatStateUsing(function (string $state, Campaign $record): string {
                        $format = match ($record->format) {
                            'video'   => 'Vidéo',
                            'image'   => 'Image',
                            'quiz'    => 'Quiz',
                            'flash'   => 'Flash',
                            'scratch' => 'Grattage',
                            default   => ucfirst($record->format),
                        };
                        return "
                            <div>
                                <div style='font-size:12px;font-weight:800;color:#1B2A6E;'>{$state}</div>
                                <div style='font-size:10px;color:#5A7098;font-weight:600;margin-top:2px;'>{$format}</div>
                            </div>
                        ";
                    })
                    ->html()
                    ->searchable(),

                TextColumn::make('budget')
                    ->label('Budget')
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->weight('bold')
                    ->color('primary'),

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
                            <div style='min-width:120px;'>
                                <div style='display:flex;justify-content:space-between;margin-bottom:4px;'>
                                    <span style='font-size:10px;color:#5A7098;font-weight:600;'>{$views} / {$max} vues</span>
                                    <span style='font-size:10px;font-weight:800;color:#2AABF0;'>{$pct}%</span>
                                </div>
                                <div style='height:5px;background:#EBF7FE;border-radius:3px;overflow:hidden;'>
                                    <div style='width:{$pct}%;height:100%;background:linear-gradient(90deg,#2AABF0,#1B2A6E);border-radius:3px;'></div>
                                </div>
                            </div>
                        ";
                    })
                    ->html(),

                TextColumn::make('cost_per_view')
                    ->label('CPV')
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' F/vue')
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'         => 'success',
                        'pending_review' => 'warning',
                        'approved'       => 'info',
                        'paused'         => 'gray',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'         => 'Active',
                        'pending_review' => 'En validation',
                        'approved'       => 'Approuvée',
                        'paused'         => 'En pause',
                        default          => ucfirst($state),
                    }),

                TextColumn::make('ends_at')
                    ->label('Fin prévue')
                    ->date('d/m/Y')
                    ->color('gray'),
            ])
            ->paginated(false);
    }
}
