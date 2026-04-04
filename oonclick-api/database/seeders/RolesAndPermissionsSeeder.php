<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Vide le cache des permissions avant de tout créer
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        // =====================================================================
        // Permissions
        // =====================================================================

        $permissions = [
            // Gestion utilisateurs
            'view_users',
            'edit_users',
            'suspend_users',

            // Campagnes
            'view_campaigns',
            'edit_campaigns',
            'approve_campaigns',

            // Retraits
            'view_withdrawals',
            'process_withdrawals',

            // Analytique
            'view_analytics',

            // Fraude
            'view_fraud_events',
            'manage_fraud',

            // Audit
            'view_audit_logs',

            // Plateforme
            'manage_platform_config',

            // App
            'view_app_stats',
            'manage_app_versions',

            // Rôles
            'manage_roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        // =====================================================================
        // Rôles et leurs permissions
        // =====================================================================

        // super_admin — toutes les permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

        // moderateur
        $moderateur = Role::firstOrCreate(['name' => 'moderateur', 'guard_name' => $guard]);
        $moderateur->syncPermissions([
            'view_users',
            'edit_users',
            'suspend_users',
            'view_campaigns',
            'approve_campaigns',
            'view_withdrawals',
            'view_fraud_events',
            'manage_fraud',
            'view_audit_logs',
        ]);

        // analyste
        $analyste = Role::firstOrCreate(['name' => 'analyste', 'guard_name' => $guard]);
        $analyste->syncPermissions([
            'view_users',
            'view_campaigns',
            'view_analytics',
            'view_app_stats',
            'view_audit_logs',
        ]);

        // support
        $support = Role::firstOrCreate(['name' => 'support', 'guard_name' => $guard]);
        $support->syncPermissions([
            'view_users',
            'view_campaigns',
            'view_withdrawals',
            'view_fraud_events',
            'view_audit_logs',
        ]);

        // comptable
        $comptable = Role::firstOrCreate(['name' => 'comptable', 'guard_name' => $guard]);
        $comptable->syncPermissions([
            'view_users',
            'view_withdrawals',
            'process_withdrawals',
            'view_analytics',
        ]);

        // Vide à nouveau le cache après la création
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
