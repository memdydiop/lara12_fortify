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

        // Assigner le rôle si spécifiés
        if ($this->invitation->role) {
            $user->syncRoles($this->invitation->role);
        }

        // Marquer l'invitation comme utilisée
        $this->invitation->markAsRegistered();
        $this->dispatch('user-created');

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>


<div class="flex flex-col gap-6">
    @if ($invitationValid)
        <x-auth-header :title="__('Créer votre compte')" :description="__('Vous avez été invité à rejoindre ' . config('app.name'))" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        @if ($invitation && $invitation->role)
            <div class="p-4 bg-blue-50  rounded-lg border border-blue-200 ">
                <flux:text class="text-sm font-medium text-primary">
                    {{ __('Adresse email :') }}
                    <span class="font-semibold">{{ $invitation->email }}</span>
                </flux:text>
                <flux:text class="text-sm font-medium text-primary">
                    {{ __('Rôle assigné :') }}
                    <span class="font-semibold">{{ $invitation->role }}</span>
                </flux:text>
            </div>
        @endif

        <form wire:submit="register" class="flex flex-col gap-6">
            <flux:input wire:model="name" type="text" required autofocus autocomplete="name"
                :placeholder="__('Vos nom et prénom')" />

            <flux:input class="hidden" wire:model="email" type="email" required readonly disabled />


            <flux:input wire:model="password" type="password" required autocomplete="new-password"
                :placeholder="__('Mot de passe')" viewable />

            <flux:input wire:model="password_confirmation" type="password" required autocomplete="new-password"
                :placeholder="__('Confirmer le mot de passe')" viewable />

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Créer mon compte') }}
            </flux:button>
        </form>
    @else
        <div class="text-center space-y-4">
            <div class="p-6 bg-red-50 rounded-lg border border-red-200">
                <flux:icon.exclamation-triangle class="size-12 mx-auto mb-4 text-red-500" />
                <flux:heading size="lg" class="text-red-900 mb-2">
                    {{ __('Invitation invalide') }}
                </flux:heading>
                <flux:text class="text-red-700">
                    {{ __('Cette invitation n\'est pas valide ou a expiré. Veuillez contacter votre administrateur.') }}
                </flux:text>
            </div>

            <flux:link :href="route('login')" wire:navigate class="inline-block">
                {{ __('Retour à la connexion') }}
            </flux:link>
        </div>
    @endif
</div>