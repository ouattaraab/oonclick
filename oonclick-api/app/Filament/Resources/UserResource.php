<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\WithdrawalResource;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
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
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'Utilisateur';

    protected static ?string $pluralModelLabel = 'Utilisateurs';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Principal';

    // =========================================================================
    // Navigation badge — nombre d'utilisateurs suspendus
    // =========================================================================

    public static function getNavigationBadge(): ?string
    {
        $suspended = User::where('is_suspended', true)->count();
        return $suspended > 0 ? (string) $suspended : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

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

            CheckboxList::make('roles')
                ->label('Rôles Spatie')
                ->relationship('roles', 'name')
                ->options(Role::all()->pluck('name', 'name')->toArray())
                ->columns(3)
                ->columnSpanFull()
                ->visible(fn (): bool => auth()->user()?->can('manage_roles') ?? false),
        ]);
    }

    // =========================================================================
    // Table
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Avatar + nom + téléphone
                TextColumn::make('name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (string $state, User $record): string {
                        $initial = mb_strtoupper(mb_substr($state, 0, 1));
                        $phone   = e($record->phone ?? '');
                        return "
                            <div style='display:flex;align-items:center;gap:10px;'>
                                <div style='width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#2AABF0,#1B2A6E);display:flex;align-items:center;justify-content:center;color:white;font-weight:900;font-size:13px;flex-shrink:0;'>{$initial}</div>
                                <div>
                                    <div style='font-size:12px;font-weight:800;color:#1B2A6E;'>{$state}</div>
                                    <div style='font-size:10px;color:#5A7098;font-weight:600;'>{$phone}</div>
                                </div>
                            </div>
                        ";
                    })
                    ->html(),

                // Rôle badge
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
                        'admin'      => 'Admin',
                        default      => $state,
                    }),

                // Tier badge (Bronze / Silver / Gold)
                TextColumn::make('tier')
                    ->label('Tier')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'gold'   => 'warning',
                        'silver' => 'gray',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'gold'   => 'Gold',
                        'silver' => 'Silver',
                        default  => 'Bronze',
                    }),

                // KYC level badge
                TextColumn::make('kyc_level')
                    ->label('KYC')
                    ->badge()
                    ->color(fn ($state): string => match ((int) $state) {
                        3       => 'success',
                        2       => 'info',
                        1       => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => "Niv. {$state}"),

                // Trust score barre de progression
                TextColumn::make('trust_score')
                    ->label('Score confiance')
                    ->formatStateUsing(function ($state): string {
                        $score = (int) ($state ?? 0);
                        $color = $score >= 70 ? '#16A34A' : ($score >= 40 ? '#D97706' : '#DC2626');
                        $bgColor = $score >= 70 ? '#DCFCE7' : ($score >= 40 ? '#FEF3C7' : '#FEE2E2');
                        return "
                            <div style='min-width:80px;'>
                                <div style='display:flex;justify-content:space-between;margin-bottom:3px;'>
                                    <span style='font-size:10px;font-weight:800;color:{$color};'>{$score}/100</span>
                                </div>
                                <div style='height:5px;background:#EBF7FE;border-radius:3px;overflow:hidden;'>
                                    <div style='width:{$score}%;height:100%;background:{$color};border-radius:3px;'></div>
                                </div>
                            </div>
                        ";
                    })
                    ->html()
                    ->sortable(),

                // Statut (Actif / Suspendu)
                TextColumn::make('status_display')
                    ->label('Statut')
                    ->state(fn (User $record): string => $record->is_suspended ? 'suspended' : ($record->is_active ? 'active' : 'inactive'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'gray',
                        default    => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'   => 'Actif',
                        'inactive' => 'Inactif',
                        default    => 'Suspendu',
                    }),

                TextColumn::make('roles_display')
                    ->label('Rôles')
                    ->state(fn (User $record): string => $record->getRoleNames()->implode(', '))
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color('gray'),
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

                Filter::make('active')
                    ->label('Actifs uniquement')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)->where('is_suspended', false)),
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
                            'is_suspended'      => true,
                            'suspension_reason' => $data['reason'],
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
                    ->label('Retraits')
                    ->icon('heroicon-o-wallet')
                    ->color('info')
                    ->url(fn (User $record): string => WithdrawalResource::getUrl('index', ['tableSearch' => $record->phone]))
                    ->openUrlInNewTab(),

                Action::make('manageRoles')
                    ->label('Gérer les rôles')
                    ->icon('heroicon-o-user-group')
                    ->color('warning')
                    ->visible(fn (): bool => auth()->user()?->can('manage_roles') ?? false)
                    ->form([
                        CheckboxList::make('roles')
                            ->label('Rôles assignés')
                            ->options(Role::all()->pluck('name', 'name')->toArray())
                            ->columns(2),
                    ])
                    ->fillForm(fn (User $record): array => [
                        'roles' => $record->getRoleNames()->toArray(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->syncRoles($data['roles'] ?? []);
                    })
                    ->modalHeading('Gérer les rôles')
                    ->modalSubmitActionLabel('Enregistrer'),

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
