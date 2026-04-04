<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Piste d\'audit';

    protected static ?string $modelLabel = 'Log d\'audit';

    protected static ?string $pluralModelLabel = 'Piste d\'audit';

    protected static ?string $navigationGroup = 'Outils';

    protected static ?int $navigationSort = 10;

    // =========================================================================
    // Pas de formulaire — lecture seule
    // =========================================================================

    public static function canCreate(): bool
    {
        return false;
    }

    // =========================================================================
    // Table
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, 'user.')       => 'info',
                        str_starts_with($state, 'campaign.')   => 'success',
                        str_starts_with($state, 'withdrawal.') => 'warning',
                        str_starts_with($state, 'fraud.')      => 'danger',
                        str_starts_with($state, 'admin.')      => 'gray',
                        default                                => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('module')
                    ->label('Module')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('platform')
                    ->label('Plateforme')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mobile' => 'primary',
                        'web'    => 'warning',
                        default  => 'gray',
                    }),

                TextColumn::make('user.phone')
                    ->label('Utilisateur')
                    ->placeholder('—')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '—';
                        }
                        // Masque le milieu du numéro
                        if (strlen($state) <= 6) {
                            return $state;
                        }
                        $prefix = substr($state, 0, strlen($state) - 6);
                        $suffix = substr($state, -2);
                        return $prefix . '****' . $suffix;
                    }),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—')
                    ->searchable()
                    ->color('gray'),

                TextColumn::make('new_values')
                    ->label('Nouvelles valeurs')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state): string => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : '—')
                    ->tooltip(fn ($state): ?string => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null)
                    ->limit(50)
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options(
                        AuditLog::query()
                            ->distinct()
                            ->orderBy('action')
                            ->pluck('action', 'action')
                            ->toArray()
                    ),

                SelectFilter::make('module')
                    ->label('Module')
                    ->options([
                        'auth'     => 'Authentification',
                        'campaign' => 'Campagne',
                        'payment'  => 'Paiement',
                        'fraud'    => 'Fraude',
                        'admin'    => 'Administration',
                        'mobile'   => 'Mobile',
                    ]),

                SelectFilter::make('platform')
                    ->label('Plateforme')
                    ->options([
                        'api'    => 'API',
                        'mobile' => 'Mobile',
                        'web'    => 'Web',
                    ]),

                Filter::make('created_at')
                    ->label('Période')
                    ->form([
                        DatePicker::make('from')
                            ->label('Du')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('until')
                            ->label('Au')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }

    // =========================================================================
    // Pages
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
