<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('register', ['token' => $this->invitation->token]);
        
        $roles = $this->invitation->roles 
            ? implode(', ', $this->invitation->roles) 
            : 'Utilisateur';

        return (new MailMessage)
            ->subject('Vous êtes invité à rejoindre ' . config('app.name'))
            ->greeting('Bonjour,')
            ->line('Vous avez été invité à créer un compte sur ' . config('app.name') . '.')
            ->line('Rôle(s) assigné(s) : **' . $roles . '**')
            ->action('Créer mon compte', $url)
            ->line('Ce lien d\'invitation expirera dans 7 jours.')
            //->line('Si vous n\'avez pas demandé cette invitation, vous pouvez ignorer ce message.')
            ->salutation('Cordialement, L\'équipe ' . config('app.name'));
    }
}