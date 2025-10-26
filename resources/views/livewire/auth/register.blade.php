<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $token = '';
    public string $email = '';
    public string $name = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?Invitation $invitation = null;
    public bool $invitationValid = false;

    public function mount(): void
    {
        $this->token = request('token', '');

        if ($this->token) {
            $this->invitation = Invitation::where('token', $this->token)
                ->whereNull('registered_at')
                ->where('expires_at', '>', now())
                ->first();

            if ($this->invitation) {
                $this->email = $this->invitation->email;
                $this->invitationValid = true;
            }
        }
    }

    public function register(): void
    {
        if (!$this->invitationValid) {
            $this->addError('token', 'Cette invitation n\'est pas valide ou a expiré.');
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        // Vérifier que l'email correspond à l'invitation
        if ($this->email !== $this->invitation->email) {
            $this->addError('email', 'L\'email ne correspond pas à l\'invitation.');
            return;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // Assigner les rôles si spécifiés
        if ($this->invitation->roles) {
            $user->syncRoles($this->invitation->roles);
        }

        // Marquer l'invitation comme utilisée
        $this->invitation->markAsRegistered();

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<x-layouts.auth>
    <div class="flex flex-col gap-6">
        @if ($invitationValid)
            <x-auth-header :title="__('Créer votre compte')" :description="__('Vous avez été invité à rejoindre ' . config('app.name'))" />

            <x-auth-session-status class="text-center" :status="session('status')" />

            <form wire:submit="register" class="flex flex-col gap-6">
                <flux:input wire:model="name" :label="__('Nom complet')" type="text" required autofocus autocomplete="name"
                    :placeholder="__('Votre nom')" />

                <flux:input wire:model="email" :label="__('Adresse email')" type="email" required readonly disabled
                    class="bg-zinc-100 dark:bg-zinc-800 cursor-not-allowed" />

                @if ($invitation && $invitation->roles)
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <flux:text class="text-sm font-medium text-blue-900 dark:text-blue-100">
                            {{ __('Rôle(s) assigné(s) :') }}
                            <span class="font-semibold">{{ implode(', ', $invitation->roles) }}</span>
                        </flux:text>
                    </div>
                @endif

                <flux:input wire:model="password" :label="__('Mot de passe')" type="password" required
                    autocomplete="new-password" :placeholder="__('Mot de passe')" viewable />

                <flux:input wire:model="password_confirmation" :label="__('Confirmer le mot de passe')" type="password"
                    required autocomplete="new-password" :placeholder="__('Confirmer le mot de passe')" viewable />

                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Créer mon compte') }}
                </flux:button>
            </form>
        @else
            <div class="text-center space-y-4">
                <div class="p-6 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <flux:icon.exclamation-triangle class="size-12 mx-auto mb-4 text-red-500" />
                    <flux:heading size="lg" class="text-red-900 dark:text-red-100 mb-2">
                        {{ __('Invitation invalide') }}
                    </flux:heading>
                    <flux:text class="text-red-700 dark:text-red-300">
                        {{ __('Cette invitation n\'est pas valide ou a expiré. Veuillez contacter votre administrateur.') }}
                    </flux:text>
                </div>

                <flux:link :href="route('login')" wire:navigate class="inline-block">
                    {{ __('Retour à la connexion') }}
                </flux:link>
            </div>
        @endif
    </div>
</x-layouts.auth>