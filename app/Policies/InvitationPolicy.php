<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    public function resend(User $user, Invitation $invitation): bool
    {
        return ! $invitation->isRegistered() && ! $invitation->isExpired();
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        return ! $invitation->isRegistered();
    }
}