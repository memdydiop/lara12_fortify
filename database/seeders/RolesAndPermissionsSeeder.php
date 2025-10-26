<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            // Permissions invitations
            'view invitations',
            'create invitations',
            'resend invitations',
            'delete invitations',
            
            // Permissions utilisateurs
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Permissions rôles
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // Créer les rôles et assigner les permissions

        // Super Admin - toutes les permissions
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['guard_name' => 'web']
        );
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - gestion complète sauf super-admin
        $admin = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );
        $admin->givePermissionTo([
            'view invitations',
            'create invitations',
            'resend invitations',
            'delete invitations',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view roles',
        ]);

        // Manager - gestion limitée
        $manager = Role::firstOrCreate(
            ['name' => 'manager'],
            ['guard_name' => 'web']
        );
        $manager->givePermissionTo([
            'view invitations',
            'create invitations',
            'resend invitations',
            'view users',
        ]);

        // User - utilisateur standard (pas de permissions spéciales)
        Role::firstOrCreate(
            ['name' => 'user'],
            ['guard_name' => 'web']
        );

        $this->command->info('Rôles et permissions créés avec succès!');
    }
}