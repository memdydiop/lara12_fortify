<?php

use App\Actions\Invitations\CreateInvitationAction;
use App\Models\Invitation;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

new class extends Component {
    public string $email = '';
    public string $role = '';  // Un seul rôle (string)
    public bool $showModal = false;

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                Rule::unique('invitations', 'email')->whereNull('registered_at')
            ],
            'role' => ['required', 'exists:roles,name'], // Required car on doit assigner un rôle
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cet email est déjà utilisé ou une invitation est déjà en attente.',
            'role.required' => 'Vous devez sélectionner un rôle.',
        ];
    }

    public function sendInvitation(CreateInvitationAction $createInvitation): void
    {
        $this->authorize('create', Invitation::class);
        $validated = $this->validate();

        // Appel de l'action
        $createInvitation->execute($validated['email'], $validated['role']);

        $this->dispatch(
            'notification',
            type: 'success',
            message: __('Invitation envoyée avec succès à :email', ['email' => $validated['email']])
        );

        $this->reset('email', 'role');
        $this->showModal = false;
        $this->dispatch('invitation-sent');
    }

    #[Livewire\Attributes\Computed]
    public function availableRoles()
    {
        return Role::orderBy('name')->get();
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

        {{-- Email --}}
        <flux:input wire:model="email" label="{{ __('Adresse email') }}" type="email"
            placeholder="utilisateur@exemple.com" required icon="envelope" />

        {{-- Sélection du rôle (RADIO buttons pour un seul choix) --}}
        <div class="space-y-2">
            <flux:label>{{ __('Rôle') }} <span class="text-red-500">*</span></flux:label>
            <flux:text variant="subtle" class="text-xs mb-2">
                {{ __('Sélectionnez le rôle à assigner à cet utilisateur') }}
            </flux:text>

            <div
                class="space-y-2 max-h-48 overflow-y-auto p-3 border rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                @foreach ($this->availableRoles as $availableRole)
                    <label
                        class="flex items-center gap-3 p-2 rounded hover:bg-white dark:hover:bg-zinc-800 cursor-pointer transition-colors">
                        <input type="radio" wire:model="role" value="{{ $availableRole->name }}"
                            class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-zinc-300 dark:border-zinc-600" />
                        <div class="flex-1">
                            <flux:text class="font-medium capitalize">{{ $availableRole->name }}</flux:text>
                            @if ($availableRole->permissions->count() > 0)
                                <flux:text variant="subtle" class="text-xs">
                                    {{ $availableRole->permissions->count() }} {{ __('permission(s)') }}
                                </flux:text>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
            @error('role')
                <flux:text variant="danger" class="text-sm">{{ $message }}</flux:text>
            @enderror
        </div>

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