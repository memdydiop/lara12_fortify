<?php
namespace App\Actions\Invitations;

use App\Models\Invitation;
use App\Notifications\UserInvited;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class CreateInvitationAction
{
    public function execute(string $email, string $role): Invitation
    {
        $invitation = Invitation::create([
            'invited_by' => Auth::id(),
            'email'      => $email,
            'role'       => $role, // String, pas array
        ]);

        // La notification est déjà configurée pour utiliser la file d'attente (Queue)
        Notification::route('mail', $email)->notify(new UserInvited($invitation));

        return $invitation;
    }
}