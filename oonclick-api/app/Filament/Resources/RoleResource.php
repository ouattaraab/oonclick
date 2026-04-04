<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Rôles & Permissions';

    protected static ?string $modelLabel = 'Rôle';

    protected static ?string $pluralModelLabel = 'Rôles & Permissions';

    protected static ?string $navigationGroup = 'Outils';

    protected static ?int $navigationSort = 8;

    // =========================================================================
    // Form
    // =========================================================================

    public static function form(Form $form): Form
    {
        // Libellés lisibles par catégorie
        $permissionLabels = [
            // Utilisateurs
            'view_users'           => '[Utilisateurs] Voir les utilisateurs',
            'edit_users'           => '[Utilisateurs] Modifier les utilisateurs',
            'suspend_users'        => '[Utilisateurs] Suspendre les utilisateurs',
            // Campagnes
            'view_campaigns'       => '[Campagnes] Voir les campagnes',
            'edit_campaigns'       => '[Campagnes] Modifier les campagnes',
            'approve_campaigns'    => '[Campagnes] Approuver/Rejeter les campagnes',
            // Retraits
            'view_withdrawals'     => '[Retraits] Voir les retraits',
            'process_withdrawals'  => '[Retraits] Traiter les retraits',
            // Analytique
            'view_analytics'       => '[Analytique] Voir les statistiques',
            // Fraude
            'view_fraud_events'    => '[Fraude] Voir les alertes de fraude',
            'manage_fraud'         => '[Fraude] Gérer la fraude',
            // Audit
            'view_audit_logs'      => '[Audit] Voir la piste d\'audit',
            // Plateforme
            'manage_platform_config' => '[Plateforme] Gérer la configuration',
            // App
            'view_app_stats'       => '[App] Voir les statistiques d\'installation',
            'manage_app_versions'  => '[App] Gérer les versions de l\'app',
            // Administration
            'manage_roles'         => '[Administration] Gérer les rôles et permissions',
        ];

        return $form->schema([
            TextInput::make('name')
                ->label('Nom du rôle')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Section::make('Permissions')
                ->schema([
                    CheckboxList::make('permissions')
                        ->label('')
                        ->relationship('permissions', 'name')
                        ->options($permissionLabels)
                        ->columns(2)
                        ->columnSpanFull()
                        ->bulkToggleable()
                        ->searchable(),
                ])
                ->columnSpanFull(),
        ]);
    }

    // =========================================================================
    // Table
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Rôle')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'moderateur'  => 'warning',
                        'analyste'    => 'info',
                        'support'     => 'success',
                        'comptable'   => 'gray',
                        default       => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->counts('users')
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->color('gray'),
            ])
            ->actions([
                EditAction::make()
                    ->label('Modifier'),

                DeleteAction::make()
                    ->label('Supprimer')
                    ->hidden(fn (Role $record): bool => $record->name === 'super_admin'),
            ])
            ->defaultSort('name');
    }

    // =========================================================================
    // Pages
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
