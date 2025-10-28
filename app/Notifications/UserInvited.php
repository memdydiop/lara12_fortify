<?php
// [file name]: UserInvited.php
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

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Le token utilisé est automatiquement le nouveau token régénéré
        $url = route('register', ['token' => $this->invitation->token]);

        return (new MailMessage)
            ->subject('Invitation à rejoindre ' . config('app.name'))
            ->line('Vous avez été invité à rejoindre ' . config('app.name'))
            ->line('Rôle : ' . $this->invitation->role)
            ->action('Accepter l\'invitation', $url)
            ->line('Cette invitation expirera le : ' . $this->invitation->expires_at->format('d/m/Y à H:i'))
            ->line('Si vous ne souhaitez pas créer de compte, aucune action n\'est requise.');
    }
}
?>