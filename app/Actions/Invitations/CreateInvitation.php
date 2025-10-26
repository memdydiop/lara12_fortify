<?php

namespace App\Actions\Invitations;

use App\Models\Invitation;
use App\Notifications\UserInvited;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateInvitation
{
    public function handle(string $email): Invitation
    {
        if (User::where('email', $email)->exists() || Invitation::where('email', $email)->whereNull('registered_at')->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An active invitation for this email already exists or the user is already registered.',
            ]);
        }

        $invitation = Invitation::create([
            'user_id' => auth()->id(),
            'email' => $email,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $email)->notify(new UserInvited($invitation));

        return $invitation;
    }
}