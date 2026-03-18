<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\WithdrawalResource;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'Utilisateur';

    protected static ?string $pluralModelLabel = 'Utilisateurs';

    protected static ?int $navigationSort = 1;

    // =========================================================================
    // Form
    // =========================================================================

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(255),

            TextInput::make('phone')
                ->label('Téléphone')
                ->required()
                ->maxLength(20),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255),

            Select::make('role')
                ->label('Rôle')
                ->options([
                    'subscriber'  => 'Abonné',
                    'advertiser'  => 'Annonceur',
                    'admin'       => 'Administrateur',
                ])
                ->required(),

            Select::make('kyc_level')
                ->label('Niveau KYC')
                ->options([
                    0 => 'Niveau 0',
                    1 => 'Niveau 1',
                    2 => 'Niveau 2',
                    3 => 'Niveau 3',
                ])
                ->required(),

            TextInput::make('trust_score')
                ->label('Score de confiance')
                ->numeric()
                ->minValue(0)
                ->maxValue(100),
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
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),

                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'subscriber' => 'info',
                        'advertiser' => 'success',
                        'admin'      => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'subscriber' => 'Abonné',
                        'advertiser' => 'Annonceur',
                        'admin'      => 'Administrateur',
                        default      => $state,
                    }),

                TextColumn::make('kyc_level')
                    ->label('KYC')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => "Niveau {$state}"),

                TextColumn::make('trust_score')
                    ->label('Score confiance')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 70 => 'success',
                        $state >= 40 => 'warning',
                        default      => 'danger',
                    })
                    ->formatStateUsing(fn ($state): string => "{$state}/100"),

                ToggleColumn::make('is_active')
                    ->label('Actif'),

                TextColumn::make('is_suspended')
                    ->label('Suspendu')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'danger' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Suspendu' : 'Non'),

                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rôle')
                    ->options([
                        'subscriber' => 'Abonné',
                        'advertiser' => 'Annonceur',
                        'admin'      => 'Administrateur',
                    ]),

                SelectFilter::make('kyc_level')
                    ->label('Niveau KYC')
                    ->options([
                        0 => 'Niveau 0',
                        1 => 'Niveau 1',
                        2 => 'Niveau 2',
                        3 => 'Niveau 3',
                    ]),

                Filter::make('is_suspended')
                    ->label('Suspendus uniquement')
                    ->query(fn (Builder $query): Builder => $query->where('is_suspended', true)),
            ])
            ->actions([
                Action::make('suspend')
                    ->label('Suspendre')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record): bool => ! $record->is_suspended)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Motif de suspension')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'is_suspended'       => true,
                            'suspension_reason'  => $data['reason'],
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Suspendre l\'utilisateur')
                    ->modalDescription('Veuillez indiquer le motif de suspension.')
                    ->modalSubmitActionLabel('Confirmer la suspension'),

                Action::make('unsuspend')
                    ->label('Réactiver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->is_suspended)
                    ->action(function (User $record): void {
                        $record->update([
                            'is_suspended'      => false,
                            'suspension_reason' => null,
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Réactiver l\'utilisateur')
                    ->modalDescription('Êtes-vous sûr de vouloir réactiver cet utilisateur ?')
                    ->modalSubmitActionLabel('Confirmer la réactivation'),

                Action::make('viewWallet')
                    ->label('Voir les retraits')
                    ->icon('heroicon-o-wallet')
                    ->color('info')
                    ->url(fn (User $record): string => WithdrawalResource::getUrl('index', ['tableSearch' => $record->phone]))
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->label('Modifier'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // =========================================================================
    // Pages
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
