<?php

use Spatie\Permission\Models\Role;
use Livewire\Volt\Component;

new class extends Component {
    public bool $showModal = false;
    public ?Role $role = null;
    public array $groupedPermissions = [];

    // Écoute l'événement pour ouvrir la modale
    #[Livewire\Attributes\On('open-view-permissions-modal')]
    public function openModal(int $roleId): void
    {
        $this->role = Role::with('permissions', 'users')->findOrFail($roleId);

        // Grouper les permissions par catégorie (basé sur le préfixe)
        $this->groupedPermissions = $this->role->permissions
            ->groupBy(function ($permission) {
                // Extraire le préfixe (ex: "create posts" -> "posts")
                $parts = explode(' ', $permission->name);
                return count($parts) > 1 ? $parts[1] : 'general';
            })
            ->map(function ($permissions) {
                return $permissions->sortBy('name')->values();
            })
            ->sortKeys()
            ->all();

        $this->showModal = true;
    }

    // Fermer la modale
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->role = null;
        $this->groupedPermissions = [];
    }

    // Ouvrir la modale d'édition
    public function editRole(): void
    {
        $this->closeModal();
        $this->dispatch('open-edit-role-modal', roleId: $this->role->id);
    }
}; ?>

<flux:modal name="view-permissions" wire:model="showModal" class="max-w-3xl">
    @if ($role)
        <div class="space-y-6">
            {{-- En-tête --}}
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <flux:heading size="lg" class="capitalize">{{ $role->name }}</flux:heading>
                        @if (in_array($role->name, ['admin', 'super-admin']))
                            <flux:badge color="zinc" size="sm">
                                <flux:icon.lock-closed class="size-3" />
                                {{ __('Système') }}
                            </flux:badge>
                        @endif
                    </div>
                    <flux:subheading>
                        {{ __('Vue détaillée des permissions et informations du rôle') }}
                    </flux:subheading>
                </div>

                @can('edit roles')
                    <flux:button wire:click="editRole" variant="ghost" size="sm" icon="pencil">
                        {{ __('Éditer') }}
                    </flux:button>
                @endcan
            </div>

            {{-- Statistiques --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Nombre de permissions --}}
                <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/40">
                            <flux:icon.shield-check class="size-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <flux:text variant="subtle" class="text-xs">{{ __('Permissions') }}</flux:text>
                            <flux:heading size="lg">{{ $role->permissions->count() }}</flux:heading>
                        </div>
                    </div>
                </div>

                {{-- Nombre d'utilisateurs --}}
                <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/40">
                            <flux:icon.users class="size-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <flux:text variant="subtle" class="text-xs">{{ __('Utilisateurs') }}</flux:text>
                            <flux:heading size="lg">{{ $role->users->count() }}</flux:heading>
                        </div>
                    </div>
                </div>

                {{-- Nombre de catégories --}}
                <div
                    class="p-4 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/40">
                            <flux:icon.folder class="size-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <flux:text variant="subtle" class="text-xs">{{ __('Catégories') }}</flux:text>
                            <flux:heading size="lg">{{ count($groupedPermissions) }}</flux:heading>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Liste des permissions groupées --}}
            @if (empty($groupedPermissions))
                <div class="p-8 text-center bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <flux:icon.shield-exclamation class="size-10 mx-auto mb-3 text-zinc-400" />
                    <flux:text variant="subtle">
                        {{ __('Ce rôle n\'a aucune permission assignée.') }}
                    </flux:text>
                </div>
            @else
                <div class="space-y-4 max-h-[400px] overflow-y-auto">
                    @foreach ($groupedPermissions as $category => $permissions)
                        <div class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                            {{-- Titre de la catégorie --}}
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                <flux:heading size="sm" class="capitalize">
                                    {{ __($category) }}
                                </flux:heading>
                                <flux:badge color="zinc" size="sm">
                                    {{ count($permissions) }} {{ __('permission(s)') }}
                                </flux:badge>
                            </div>

                            {{-- Liste des permissions --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach ($permissions as $permission)
                                    <div class="flex items-center gap-2 p-2 rounded bg-zinc-50 dark:bg-zinc-800">
                                        <flux:icon.check-circle class="size-4 text-green-500 flex-shrink-0" />
                                        <flux:text class="text-sm">{{ $permission->name }}</flux:text>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Utilisateurs assignés (aperçu) --}}
            @if ($role->users->count() > 0)
                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between mb-3">
                        <flux:heading size="sm">{{ __('Utilisateurs assignés') }}</flux:heading>
                        <flux:badge color="blue" size="sm">
                            {{ $role->users->count() }} {{ __('utilisateur(s)') }}
                        </flux:badge>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($role->users->take(10) as $user)
                            <flux:badge variant="pill" color="zinc">
                                {{ $user->name }}
                            </flux:badge>
                        @endforeach

                        @if ($role->users->count() > 10)
                            <flux:badge variant="pill" color="blue">
                                +{{ $role->users->count() - 10 }} {{ __('autre(s)') }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Fermer') }}</flux:button>
                </flux:modal.close>

                @can('edit roles')
                    <flux:button wire:click="editRole" variant="primary" icon="pencil">
                        {{ __('Modifier les permissions') }}
                    </flux:button>
                @endcan
            </div>
        </div>
    @endif
</flux:modal>