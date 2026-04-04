<?php

namespace App\Filament\Advertiser\Widgets;

use App\Models\AdView;
use Filament\Widgets\ChartWidget;

class AdvertiserViewsChartWidget extends ChartWidget
{
    protected static ?int $sort = 1;

    protected static ?string $heading = 'Vues par jour — 7 derniers jours';

    protected static ?string $pollingInterval = '120s';

    protected int | string | array $columnSpan = 2;

    // =========================================================================

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $advertiserId = auth()->id();
        $dayNames     = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        $labels       = [];
        $data         = [];
        $values       = [];

        for ($i = 6; $i >= 0; $i--) {
            $date  = now()->subDays($i)->toDateString();
            $count = AdView::whereHas('campaign', fn ($q) => $q->where('advertiser_id', $advertiserId))
                ->where('is_completed', true)
                ->whereDate('started_at', $date)
                ->count();

            $dayIndex = (int) now()->subDays($i)->format('w');
            $labels[] = $dayNames[$dayIndex];
            $data[]   = $count;
            $values[] = $count;
        }

        $max    = max($values) ?: 1;
        $colors = array_map(
            fn ($v) => $v === $max ? '#2AABF0' : '#C5E8FA',
            $data
        );

        return [
            'datasets' => [
                [
                    'label'           => 'Vues complétées',
                    'data'            => $data,
                    'backgroundColor' => $colors,
                    'borderRadius'    => 5,
                    'borderSkipped'   => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid'  => ['color' => '#C8E4F6'],
                    'ticks' => [
                        'font'  => ['family' => 'Nunito', 'weight' => '700', 'size' => 11],
                        'color' => '#5A7098',
                    ],
                ],
                'x' => [
                    'grid'  => ['display' => false],
                    'ticks' => [
                        'font'  => ['family' => 'Nunito', 'weight' => '700', 'size' => 11],
                        'color' => '#5A7098',
                    ],
                ],
            ],
        ];
    }
}
