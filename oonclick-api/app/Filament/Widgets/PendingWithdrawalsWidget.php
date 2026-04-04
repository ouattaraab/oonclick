<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\WithdrawalResource;
use App\Models\Withdrawal;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;

class PendingWithdrawalsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Retraits en attente';

    protected int | string | array $columnSpan = 1;

    protected static ?string $pollingInterval = '30s';

    // =========================================================================

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Withdrawal::query()
                    ->with('user')
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->formatStateUsing(function (string $state, Withdrawal $record): string {
                        $initial = mb_strtoupper(mb_substr($state, 0, 1));
                        $phone   = $record->user?->phone ?? '';
                        return "
                            <div style='display:flex;align-items:center;gap:8px;'>
                                <div style='width:34px;height:34px;border-radius:10px;background:#EBF7FE;display:flex;align-items:center;justify-content:center;font-weight:900;color:#2AABF0;font-size:13px;flex-shrink:0;'>{$initial}</div>
                                <div>
                                    <div style='font-size:12px;font-weight:800;color:#1B2A6E;'>{$state}</div>
                                    <div style='font-size:10px;color:#5A7098;font-weight:600;'>{$phone}</div>
                                </div>
                            </div>
                        ";
                    })
                    ->html(),

                TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(function ($state, Withdrawal $record): string {
                        $operatorEmoji = match (strtolower($record->mobile_operator ?? '')) {
                            'orange' => '🟠',
                            'mtn'    => '🟡',
                            'moov'   => '🔵',
                            'wave'   => '🟦',
                            default  => '💳',
                        };
                        $operator = ucfirst($record->mobile_operator ?? '');
                        $amount   = number_format($state, 0, ',', ' ');
                        return "
                            <div>
                                <div style='font-size:13px;font-weight:900;color:#1B2A6E;'>{$amount} F</div>
                                <div style='font-size:10px;color:#5A7098;font-weight:600;margin-top:1px;'>{$operatorEmoji} {$operator}</div>
                            </div>
                        ";
                    })
                    ->html(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->size('sm')
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
                    ->modalDescription('Confirmer l\'approbation de ce retrait ?')
                    ->modalSubmitActionLabel('Approuver'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('voir_tous')
                    ->label('Voir tous')
                    ->url(WithdrawalResource::getUrl('index'))
                    ->color('primary')
                    ->size('sm'),
            ])
            ->paginated(false);
    }
}
