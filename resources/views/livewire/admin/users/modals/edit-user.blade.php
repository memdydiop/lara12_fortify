<?php

use App\Models\User;
use App\Actions\Users\UpdateUserRolesAndPermissionsAction;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new class extends Component {
    public bool $showModal = false;
    public ?User $user = null;
    public array $selectedRoles = [];
    public array $selectedPermissions = [];
    public array $allRoles;
    public array $allPermissions;
    public array $originalRoles = [];
    public array $originalPermissions = [];

    public function mount(): void
    {
        $this->allRoles = Role::orderBy('name')->pluck('name', 'name')->toArray();
        $this->allPermissions = Permission::orderBy('name')->pluck('name', 'name')->toArray();
    }

    #[Livewire\Attributes\On('open-edit-user-modal')]
    public function openEditUserModal(int $userId): void
    {
        $this->user = User::with('roles', 'permissions')->findOrFail($userId);

        $this->selectedRoles = $this->user->getRoleNames()->toArray();
        $this->selectedPermissions = $this->user->getDirectPermissions()->pluck('name')->toArray();
        $this->originalRoles = $this->selectedRoles;
        $this->originalPermissions = $this->selectedPermissions;

        $this->showModal = true;
    }

    public function rules(): array
    {
        return [
            'selectedRoles' => ['nullable', 'array'],
            'selectedRoles.*' => ['exists:roles,name'],
            'selectedPermissions' => ['nullable', 'array'],
            'selectedPermissions.*' => ['exists:permissions,name'],
        ];
    }

    public function save(UpdateUserRolesAndPermissionsAction $updateUserAction): void
    {
        $this->authorize('updateRolesAndPermissions', $this->user);

        $validated = $this->validate();

        // Sécurité : Empêcher de se retirer le rôle admin
        if (auth()->user()->is($this->user) && in_array('admin', $this->allRoles) && !in_array('admin', $validated['selectedRoles'])) {
            $this->dispatch('notification', type: 'error', message: 'Vous ne pouvez pas vous retirer le rôle admin.');
            return;
        }

        $updateUserAction->execute($this->user, $validated);

        $this->dispatch('user-updated');
        $this->showModal = false;
        $this->dispatch('notification', type: 'success', message: "Les droits de l'utilisateur {$this->user->name} ont été mis à jour.");
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetErrorBag();
        $this->reset('selectedRoles', 'selectedPermissions', 'originalRoles', 'originalPermissions');
    }

    #[Livewire\Attributes\Computed]
    public function hasChanges()
    {
        return array_diff($this->selectedRoles, $this->originalRoles)
            || array_diff($this->originalRoles, $this->selectedRoles)
            || array_diff($this->selectedPermissions, $this->originalPermissions)
            || array_diff($this->originalPermissions, $this->selectedPermissions);
    }

    #[Livewire\Attributes\Computed]
    public function addedRoles()
    {
        return array_diff($this->selectedRoles, $this->originalRoles);
    }

    #[Livewire\Attributes\Computed]
    public function removedRoles()
    {
        return array_diff($this->originalRoles, $this->selectedRoles);
    }

    #[Livewire\Attributes\Computed]
    public function addedPermissions()
    {
        return array_diff($this->selectedPermissions, $this->originalPermissions);
    }

    #[Livewire\Attributes\Computed]
    public function removedPermissions()
    {
        return array_diff($this->originalPermissions, $this->selectedPermissions);
    }
}; ?>

<flux:modal name="edit-user" wire:model="showModal" class="max-w-4xl">
    @if ($user)
        <form wire:submit="save" class="space-y-6">
            {{-- En-tête --}}
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <flux:heading size="lg">{{ __('Éditer les droits de') }} : {{ $user->name }}</flux:heading>
                    @if ($user->id === auth()->id())
                        <flux:badge color="blue" size="sm">
                            <flux:icon.user class="size-3" />
                            {{ __('Vous') }}
                        </flux:badge>
                    @endif
                </div>
                <flux:subheading>
                    {{ __('Gérez les rôles et les permissions directes de cet utilisateur.') }}
                </flux:subheading>
            </div>

            {{-- Informations utilisateur --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                <div>
                    <flux:text class="font-semibold">{{ __('Nom') }}</flux:text>
                    <flux:text variant="subtle">{{ $user->name }}</flux:text>
                </div>
                <div>
                    <flux:text class="font-semibold">{{ __('Email') }}</flux:text>
                    <flux:text variant="subtle">{{ $user->email }}</flux:text>
                </div>
                <div>
                    <flux:text class="font-semibold">{{ __('Inscrit le') }}</flux:text>
                    <flux:text variant="subtle">{{ $user->created_at->format('d/m/Y H:i') }}</flux:text>
                </div>
                <div>
                    <flux:text class="font-semibold">{{ __('Dernière connexion') }}</flux:text>
                    <flux:text variant="subtle">
                        {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : __('Jamais') }}
                    </flux:text>
                </div>
            </div>

            {{-- Alerte de modifications --}}
            @if ($this->hasChanges)
                <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                    <div class="flex items-start gap-3">
                        <flux:icon.exclamation-triangle class="size-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <flux:heading size="sm" class="text-amber-900 dark:text-amber-100 mb-1">
                                {{ __('Modifications en attente') }}
                            </flux:heading>
                            <div class="space-y-1 text-sm text-amber-800 dark:text-amber-200">
                                @if (count($this->addedRoles) > 0)
                                    <div><strong>+{{ count($this->addedRoles) }}</strong> {{ __('rôle(s) ajouté(s)') }}</div>
                                @endif
                                @if (count($this->removedRoles) > 0)
                                    <div><strong>-{{ count($this->removedRoles) }}</strong> {{ __('rôle(s) retiré(s)') }}</div>
                                @endif
                                @if (count($this->addedPermissions) > 0)
                                    <div><strong>+{{ count($this->addedPermissions) }}</strong> {{ __('permission(s) ajoutée(s)') }}</div>
                                @endif
                                @if (count($this->removedPermissions) > 0)
                                    <div><strong>-{{ count($this->removedPermissions) }}</strong> {{ __('permission(s) retirée(s)') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Rôles --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <flux:label for="roles">{{ __('Rôles Assignés') }}</flux:label>
                        <div class="flex gap-2">
                            <flux:button type="button" wire:click="$set('selectedRoles', [])" variant="ghost" size="xs">
                                {{ __('Tout désélectionner') }}
                            </flux:button>
                        </div>
                    </div>
                    <flux:text variant="subtle" class="text-xs mb-3">
                        {{ __('Le rôle détermine l\'accès principal de l\'utilisateur.') }}
                    </flux:text>
                    <div class="space-y-2 max-h-48 overflow-y-auto p-3 border rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                        @foreach ($allRoles as $roleName => $label)
                            @php
                                $isAdded = in_array($roleName, $this->addedRoles);
                                $isRemoved = in_array($roleName, $this->removedRoles);
                            @endphp
                            <div class="flex items-center gap-2 p-1 rounded {{ $isAdded ? 'bg-green-50 dark:bg-green-900/20' : '' }} {{ $isRemoved ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                <flux:checkbox 
                                    wire:model.live="selectedRoles" 
                                    value="{{ $roleName }}" 
                                    label="{{ $roleName }}" 
                                    :disabled="auth()->user()->is($user) && $roleName === 'admin'" 
                                />
                                @if ($isAdded)
                                    <flux:icon.plus class="size-3 text-green-600 dark:text-green-400" />
                                @elseif ($isRemoved)
                                    <flux:icon.minus class="size-3 text-red-600 dark:text-red-400" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Permissions directes --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <flux:label for="direct_permissions">{{ __('Permissions Directes') }}</flux:label>
                        <div class="flex gap-2">
                            <flux:button type="button" wire:click="$set('selectedPermissions', [])" variant="ghost" size="xs">
                                {{ __('Tout désélectionner') }}
                            </flux:button>
                        </div>
                    </div>
                    <flux:text variant="subtle" class="text-xs mb-3">
                        {{ __('Permissions spécifiques à cet utilisateur, en plus de celles de son rôle.') }}
                    </flux:text>
                    <div class="space-y-2 max-h-48 overflow-y-auto p-3 border rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                        @foreach ($allPermissions as $permissionName => $label)
                            @php
                                $isAdded = in_array($permissionName, $this->addedPermissions);
                                $isRemoved = in_array($permissionName, $this->removedPermissions);
                            @endphp
                            <div class="flex items-center gap-2 p-1 rounded {{ $isAdded ? 'bg-green-50 dark:bg-green-900/20' : '' }} {{ $isRemoved ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                <flux:checkbox 
                                    wire:model.live="selectedPermissions" 
                                    value="{{ $permissionName }}"
                                    label="{{ $permissionName }}" 
                                />
                                @if ($isAdded)
                                    <flux:icon.plus class="size-3 text-green-600 dark:text-green-400" />
                                @elseif ($isRemoved)
                                    <flux:icon.minus class="size-3 text-red-600 dark:text-red-400" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Résumé --}}
            <div class="p-4 border rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                <flux:heading size="sm" class="mb-2">{{ __('Résumé des droits') }}</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <flux:text class="font-semibold">{{ __('Rôles') }} ({{ count($selectedRoles) }})</flux:text>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @forelse ($selectedRoles as $role)
                                <flux:badge variant="pill" color="blue" size="sm">{{ $role }}</flux:badge>
                            @empty
                                <flux:text variant="subtle" class="text-xs">{{ __('Aucun rôle') }}</flux:text>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <flux:text class="font-semibold">{{ __('Permissions directes') }} ({{ count($selectedPermissions) }})</flux:text>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @forelse ($selectedPermissions as $permission)
                                <flux:badge variant="pill" color="green" size="sm">{{ $permission }}</flux:badge>
                            @empty
                                <flux:text variant="subtle" class="text-xs">{{ __('Aucune permission directe') }}</flux:text>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-between items-center pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:text variant="subtle" class="text-sm">
                    {{ count($selectedRoles) }} {{ __('rôle(s)') }}, 
                    {{ count($selectedPermissions) }} {{ __('permission(s) directe(s)') }}
                </flux:text>

                <div class="flex gap-3">
                    <flux:button variant="ghost" type="button" wire:click="closeModal">{{ __('Annuler') }}</flux:button>
                    <flux:button variant="primary" type="submit" icon="check" :disabled="!$this->hasChanges">
                        {{ __('Sauvegarder les droits') }}
                    </flux:button>
                </div>
            </div>
        </form>
    @endif
</flux:modal>