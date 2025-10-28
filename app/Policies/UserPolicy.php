<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    // ... (autres méthodes viewAny, delete, etc.)

    /**
     * Détermine si l'utilisateur actuel peut modifier les rôles/permissions d'un autre utilisateur.
     */
    public function updateRolesAndPermissions(User $currentUser, User $targetUser): bool
    {
        // 1. Interdit la modification de ses propres droits par un utilisateur.
        if ($currentUser->is($targetUser)) {
            return false;
        }

        // 2. Exige la permission spécifique (détenue par Admin/Ghost)
        return $currentUser->hasPermissionTo('edit user roles');
    }
}