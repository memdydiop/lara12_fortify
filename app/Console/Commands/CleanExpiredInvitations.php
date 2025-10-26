<?php

use App\Models\Invitation;
use App\Notifications\UserInvited;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new class extends Component {
    public string $email = '';
    public array $selectedRoles = [];
    public bool $showModal = false;

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                'unique:invitations,email'
            ],
            'selectedRoles' => ['nullable', 'array'],
            'selectedRoles.*' => ['exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cet email est déjà utilisé ou a déjà une invitation en attente.',
        ];
    }

    public function sendInvitation(): void
    {
        $this->authorize('create', Invitation::class);

        $validated = $this->validate();

        $invitation = Invitation::create([
            'email' => $validated['email'],
            'roles' => $this->selectedRoles ?: null,
            'invited_by' => auth()->id(),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new UserInvited($invitation));

        $this->dispatch('notification',
            type: 'success',
            message: 'Invitation envoyée avec succès à ' . $invitation->email
        );

        $this->reset('email', 'selectedRoles');
        $this->showModal = false;

        $this->dispatch('$refresh');
    }

    public function with(): array
    {
        return [
            'roles' => Role::orderBy('name')->get(),
        ];
    }
}; ?>

<flux:modal name="invite-user" wire:model="showModal" class="max-w-lg">
    <form wire:submit="sendInvitation" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Inviter un utilisateur') }}</flux:heading>
            <flux:subheading>
                {{ __('Envoyez une invitation par email pour créer un nouveau compte') }}
            </flux:subheading>
        </div>

        <flux:input
            wire:model="email"
            label="Adresse email"
            type="email"
            placeholder="utilisateur@exemple.com"
            required
            icon="envelope"
        />

        @if ($roles->isNotEmpty())
            <div class="space-y-2">
                <flux:label>{{ __('Rôles (optionnel)') }}</flux:label>
                <flux:text variant="subtle" class="text-xs mb-2">
                    {{ __('Sélectionnez un ou plusieurs rôles à assigner à cet utilisateur') }}
                </flux:text>
                
                <div class="space-y-2 max-h-48 overflow-y-auto p-3 border rounded-lg border-zinc-200 dark:border-zinc-700">
                    @foreach ($roles as $role)
                        <flux:checkbox 
                            wire:model="selectedRoles"
                            value="{{ $role->name }}"
                            label="{{ $role->name }}"
                        />
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Annuler') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="primary" type="submit" icon="paper-airplane">
                {{ __('Envoyer l\'invitation') }}
            </flux:button>
        </div>
    </form>
</flux:modal>