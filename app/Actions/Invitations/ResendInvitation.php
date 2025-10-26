<?php

namespace App\Actions\Invitations;

use App\Models\Invitation;
use App\Notifications\UserInvited;
use Illuminate\Support\Facades\Notification;

class ResendInvitation
{
    public function handle(Invitation $invitation): void
    {
        Notification::route('mail', $invitation->email)->notify(new UserInvited($invitation));
    }
}