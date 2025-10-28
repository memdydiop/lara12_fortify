<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view invitations');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('send invitations');
    }

    public function resend(User $user, Invitation $invitation): bool
    {
        return $user->hasPermissionTo('resend invitations') 
            && !$invitation->isRegistered() 
            && !$invitation->isExpired();
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        return $user->hasPermissionTo('delete invitations') 
            && !$invitation->isRegistered();
    }
}