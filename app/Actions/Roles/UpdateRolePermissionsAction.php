<?php

namespace App\Actions\Roles;

use Spatie\Permission\Models\Role;

class UpdateRolePermissionsAction
{
    /**
     * Met à jour le nom du rôle et synchronise ses permissions.
     */
    public function execute(Role $role, string $name, array $permissions): Role
    {
        // 1. Mettre à jour le nom du rôle si nécessaire
        if ($role->name !== $name) {
            $role->update(['name' => $name]);
        }

        // 2. Synchroniser les permissions (méthode Spatie)
        // Cela remplace toutes les anciennes permissions par les nouvelles
        $role->syncPermissions($permissions);

        return $role;
    }
}