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

        return (new MailMessage)
            ->subject('You are invited to join!')
            ->line('You have been invited to create an account.')
            ->action('Create Account', $url)
            ->line('This invitation link will expire in 7 days.');
    }
}