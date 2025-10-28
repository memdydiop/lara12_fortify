<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Retourne la liste de toutes les permissions de l'application.
     * C'est la "Source de Vérité". Pour ajouter une permission,
     * il suffit de l'ajouter à ce tableau.
     */
    public function getPermissions(): array
    {
        return [
            // Gestion des Utilisateurs
            'view users',
            'create users',
            'edit users',
            'delete users',
            'export users',
            'edit user roles',

            'view invitations',
            'send invitations',
            'resend invitations',
            'delete invitations',

            'view administration',

            // Gestion des Rôles & Permissions
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',

            'view permissions',
            'assign permissions',

            'view profiles',
            'edit profiles',
            'delete profiles',

            'view settings',
            'edit settings',

            'view reports',
            'export reports',

            // --- AJOUTEZ VOS NOUVELLES PERMISSIONS CI-DESSOUS ---

            // Gestion des Clients
            'view clients',
            'create clients',
            'edit clients',
            'delete clients',

            'view patisseries', 'create patisseries', 'edit patisseries', 'delete patisseries',
            'view clients', 'create clients', 'edit clients', 'delete clients',

            'view clients',
            'create clients',
            'edit clients',
            'delete clients',

            'view ingredients_categories',
            'create ingredients_categories',
            'edit ingredients_categories',
            'delete ingredients_categories',
            'view ingredients',
            'create ingredients',
            'edit ingredients',
            'delete ingredients',
            'view fournisseurs',
            'create fournisseurs',
            'edit fournisseurs',
            'delete fournisseurs',
            // ...
        ];
    }

    /**
     * Exécute les seeds de la base de données.
     */
    public function run(): void
    {
        // Réinitialise le cache des rôles et permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 1. Création des Permissions ---
        $permissions = $this->getPermissions();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // --- 2. Création des Rôles ---
        $ghostRole = Role::firstOrCreate(['name' => 'Ghost']);
        
    }
}
