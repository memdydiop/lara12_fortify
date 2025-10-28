<?php

namespace App\Actions\Users;

use App\Models\User;

class UpdateUserRolesAndPermissionsAction
{
    /**
     * Synchronise uniquement les rôles et les permissions directes d'un utilisateur.
     * Les informations personnelles (nom, email) ne sont PAS modifiées.
     */
    public function execute(User $user, array $data): User
    {
        // 1. REMOVAL: Suppression de la logique de mise à jour du nom et de l'email.
        // Seuls les rôles et les permissions sont traités ici.
        
        // 2. Synchronisation des rôles (Spatie)
        $user->syncRoles($data['selectedRoles']);

        // 3. Synchronisation des permissions directes (Spatie)
        $user->syncPermissions($data['selectedPermissions']);

        return $user;
    }
}