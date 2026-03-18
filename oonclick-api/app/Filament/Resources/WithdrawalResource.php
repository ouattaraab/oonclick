<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Retraits';

    protected static ?string $modelLabel = 'Retrait';

    protected static ?string $pluralModelLabel = 'Retraits';

    protected static ?int $navigationSort = 3;

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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.phone')
                    ->label('Téléphone utilisateur')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->sortable(),

                TextColumn::make('mobile_operator')
                    ->label('Opérateur')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'orange'   => 'warning',
                        'mtn'      => 'success',
                        'moov'     => 'info',
                        default    => 'gray',
                    }),

                TextColumn::make('mobile_phone')
                    ->label('N° mobile money')
                    ->searchable(),

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
                    ->sortable(),
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
                    ]),
            ])
            ->actions([
                Action::make('markCompleted')
                    ->label('Marquer complété')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
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

                Action::make('markFailed')
                    ->label('Marquer échoué')
                    ->icon('heroicon-o-x-circle')
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
