<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Retraits';

    protected static ?string $modelLabel = 'Retrait';

    protected static ?string $pluralModelLabel = 'Retraits';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Principal';

    // =========================================================================
    // Navigation badge — retraits en attente
    // =========================================================================

    public static function getNavigationBadge(): ?string
    {
        $pending = Withdrawal::where('status', 'pending')->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    // =========================================================================
    // Form (lecture seule — pas de création manuelle)
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
                // Utilisateur — nom + téléphone
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (?string $state, Withdrawal $record): string {
                        if (! $state) {
                            return '<span style="color:#5A7098;">—</span>';
                        }
                        $initial = mb_strtoupper(mb_substr($state, 0, 1));
                        $phone   = e($record->user?->phone ?? $record->mobile_phone ?? '');
                        return "
                            <div style='display:flex;align-items:center;gap:8px;'>
                                <div style='width:34px;height:34px;border-radius:10px;background:#EBF7FE;display:flex;align-items:center;justify-content:center;font-weight:900;color:#2AABF0;font-size:13px;flex-shrink:0;'>{$initial}</div>
                                <div>
                                    <div style='font-size:12px;font-weight:800;color:#1B2A6E;'>{$state}</div>
                                    <div style='font-size:10px;color:#5A7098;font-weight:600;margin-top:1px;'>{$phone}</div>
                                </div>
                            </div>
                        ";
                    })
                    ->html(),

                // Montant en gras
                TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                // Opérateur avec emoji
                TextColumn::make('mobile_operator')
                    ->label('Opérateur')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '<span style="color:#5A7098;">—</span>';
                        }
                        $emoji = match (strtolower($state)) {
                            'orange' => '🟠',
                            'mtn'    => '🟡',
                            'moov'   => '🔵',
                            'wave'   => '🟦',
                            default  => '💳',
                        };
                        $label  = ucfirst($state);
                        $colorMap = match (strtolower($state)) {
                            'orange' => '#D97706',
                            'mtn'    => '#16A34A',
                            'moov'   => '#1A95D8',
                            'wave'   => '#1B2A6E',
                            default  => '#5A7098',
                        };
                        return "<span style='font-size:12px;font-weight:800;color:{$colorMap};'>{$emoji} {$label}</span>";
                    })
                    ->html(),

                // N° mobile money
                TextColumn::make('mobile_phone')
                    ->label('N° Mobile Money')
                    ->searchable()
                    ->color('gray'),

                // Solde avant retrait
                TextColumn::make('balance_before')
                    ->label('Solde avant')
                    ->formatStateUsing(fn ($state): string => $state
                        ? number_format($state, 0, ',', ' ') . ' F'
                        : '<span style="color:#5A7098;">—</span>'
                    )
                    ->html()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Référence de transaction
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Référence copiée')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Statut badge
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'completed'  => 'success',
                        'failed'     => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'    => 'En attente',
                        'processing' => 'En cours',
                        'completed'  => 'Complété',
                        'failed'     => 'Échoué',
                        default      => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Demandé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'    => 'En attente',
                        'processing' => 'En cours',
                        'completed'  => 'Complété',
                        'failed'     => 'Échoué',
                    ]),

                SelectFilter::make('mobile_operator')
                    ->label('Opérateur')
                    ->options([
                        'orange' => 'Orange',
                        'mtn'    => 'MTN',
                        'moov'   => 'Moov',
                        'wave'   => 'Wave',
                    ]),
            ])
            ->actions([
                // Approuver — uniquement si En attente
                Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Withdrawal $record): bool => $record->status === 'pending')
                    ->action(function (Withdrawal $record): void {
                        $record->update([
                            'status'       => 'processing',
                            'processed_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Retrait approuvé')
                            ->body("Retrait de {$record->amount} FCFA passé en traitement.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approuver le retrait')
                    ->modalDescription('Passer ce retrait en statut "En cours de traitement" ?')
                    ->modalSubmitActionLabel('Approuver'),

                // Rejeter — uniquement si En attente
                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Withdrawal $record): bool => $record->status === 'pending')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Withdrawal $record, array $data): void {
                        $record->update([
                            'status'         => 'failed',
                            'failure_reason' => $data['reason'],
                            'processed_at'   => now(),
                        ]);
                        Notification::make()
                            ->title('Retrait rejeté')
                            ->danger()
                            ->send();
                    })
                    ->modalHeading('Rejeter le retrait')
                    ->modalDescription('Veuillez indiquer le motif du rejet.')
                    ->modalSubmitActionLabel('Confirmer le rejet'),

                // Marquer complété — uniquement si En cours
                Action::make('markCompleted')
                    ->label('Complété')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->visible(fn (Withdrawal $record): bool => $record->status === 'processing')
                    ->action(function (Withdrawal $record): void {
                        $record->update([
                            'status'       => 'completed',
                            'processed_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Confirmer le retrait')
                    ->modalDescription('Marquer ce retrait comme complété ?')
                    ->modalSubmitActionLabel('Confirmer'),

                // Marquer échoué — uniquement si En cours
                Action::make('markFailed')
                    ->label('Échoué')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('danger')
                    ->visible(fn (Withdrawal $record): bool => $record->status === 'processing')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Motif de l\'échec')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Withdrawal $record, array $data): void {
                        $record->update([
                            'status'         => 'failed',
                            'failure_reason' => $data['reason'],
                            'processed_at'   => now(),
                        ]);
                    })
                    ->modalHeading('Échec du retrait')
                    ->modalDescription('Indiquer le motif de l\'échec.')
                    ->modalSubmitActionLabel('Confirmer l\'échec'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_bulk')
                        ->label('Approuver la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $records
                                ->filter(fn (Withdrawal $r) => $r->status === 'pending')
                                ->each(fn (Withdrawal $r) => $r->update([
                                    'status'       => 'processing',
                                    'processed_at' => now(),
                                ]));
                            Notification::make()
                                ->title('Retraits approuvés')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Approuver en masse')
                        ->modalDescription('Approuver tous les retraits en attente sélectionnés ?')
                        ->modalSubmitActionLabel('Approuver tout'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // =========================================================================
    // Pages
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
        ];
    }
}
