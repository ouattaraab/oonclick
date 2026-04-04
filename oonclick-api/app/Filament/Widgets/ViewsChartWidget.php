<?php

namespace App\Filament\Widgets;

use App\Models\AdView;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class ViewsChartWidget extends ChartWidget
{
    protected static ?int $sort = 1;

    protected static ?string $heading = 'Vues publicitaires — 7 derniers jours';

    protected static ?string $pollingInterval = '120s';

    protected int | string | array $columnSpan = 2;

    // =========================================================================

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $days   = collect();
        $labels = [];
        $data   = [];
        $colors = [];

        $dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

        for ($i = 6; $i >= 0; $i--) {
            $date  = now()->subDays($i)->toDateString();
            $count = AdView::where('is_completed', true)
                ->whereDate('started_at', $date)
                ->count();

            $dayIndex = (int) now()->subDays($i)->format('w');
            $labels[] = $dayNames[$dayIndex];
            $data[]   = $count;

            // Mise en valeur du jour ayant le plus de vues
            $days->push($count);
        }

        $maxValue = $days->max() ?: 1;

        // Couleur : le jour le plus haut est en --sky, les autres en --sky-mid
        foreach ($data as $value) {
            $colors[] = $value === $maxValue ? '#2AABF0' : '#C5E8FA';
        }

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
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => '#C8E4F6',
                    ],
                    'ticks' => [
                        'font' => [
                            'family' => 'Nunito',
                            'weight' => '700',
                            'size'   => 11,
                        ],
                        'color' => '#5A7098',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'family' => 'Nunito',
                            'weight' => '700',
                            'size'   => 11,
                        ],
                        'color' => '#5A7098',
                    ],
                ],
            ],
        ];
    }
}
