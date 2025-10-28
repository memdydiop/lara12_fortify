<?php

use App\Actions\Roles\UpdateRolePermissionsAction;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Volt\Component;

new class extends Component {
    public bool $showModal = false;
    public ?Role $role = null;
    public string $name = '';
    public array $selectedPermissions = [];
    public string $searchPermission = '';
    public array $originalPermissions = [];

    // Écoute l'événement pour ouvrir la modale et charger les données
    #[Livewire\Attributes\On('open-edit-role-modal')]
    public function openModal(int $roleId): void
    {
        $this->role = Role::with('permissions')->findOrFail($roleId);

        // Initialisation des propriétés du formulaire
        $this->name = $this->role->name;
        $this->selectedPermissions = $this->role->permissions->pluck('name')->toArray();
        $this->originalPermissions = $this->selectedPermissions;
        $this->searchPermission = '';
        $this->showModal = true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $this->role?->id],
            'selectedPermissions' => ['nullable', 'array'],
            'selectedPermissions.*' => ['exists:permissions,name'],
        ];
    }

    public function save(): void
    {
        $this->authorize('edit roles');

        $validated = $this->validate();

        // Mettre à jour le nom du rôle
        $this->role->update(['name' => $validated['name']]);

        // Synchroniser les permissions
        $this->role->syncPermissions($validated['selectedPermissions'] ?? []);

        // Émission de l'événement pour rafraîchir la liste principale
        $this->dispatch('role-updated');

        $this->dispatch(
            'notification',
            type: 'success',
            message: __("Le rôle ':role' a été mis à jour avec succès.", ['role' => $this->name])
        );

        $this->showModal = false;
        $this->reset(['searchPermission', 'originalPermissions']);
    }

    // Sélectionner toutes les permissions
    public function selectAllPermissions(): void
    {
        $this->selectedPermissions = $this->filteredPermissions->pluck('name')->toArray();
    }

    // Désélectionner toutes les permissions
    public function deselectAllPermissions(): void
    {
        $this->selectedPermissions = [];
    }

    // Sélectionner par catégorie
    public function selectCategory(string $category): void
    {
        $categoryPermissions = $this->groupedPermissions[$category] ?? [];
        $categoryNames = collect($categoryPermissions)->pluck('name')->toArray();

        $this->selectedPermissions = array_unique(array_merge(
            $this->selectedPermissions,
            $categoryNames
        ));
    }

    // Restaurer les permissions d'origine
    public function resetPermissions(): void
    {
        $this->selectedPermissions = $this->originalPermissions;
    }

    #[Livewire\Attributes\Computed]
    public function allPermissions()
    {
        return Permission::orderBy('name')->get();
    }

    #[Livewire\Attributes\Computed]
    public function filteredPermissions()
    {
        $permissions = $this->allPermissions;

        if ($this->searchPermission) {
            $permissions = $permissions->filter(function ($permission) {
                return str_contains(
                    strtolower($permission->name),
                    strtolower($this->searchPermission)
                );
            });
        }

        return $permissions;
    }

    #[Livewire\Attributes\Computed]
    public function groupedPermissions()
    {
        return $this->filteredPermissions->groupBy(function ($permission) {
            $parts = explode(' ', $permission->name);
            return count($parts) > 1 ? $parts[1] : 'general';
        })->sortKeys()->all();
    }

    #[Livewire\Attributes\Computed]
    public function hasChanges()
    {
        return $this->name !== $this->role?->name
            || array_diff($this->selectedPermissions, $this->originalPermissions)
            || array_diff($this->originalPermissions, $this->selectedPermissions);
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

<flux:modal name="edit-role" wire:model="showModal" class="max-w-4xl">
    @if ($role)
        <form wire:submit="save" class="space-y-6">
            {{-- En-tête --}}
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <flux:heading size="lg">{{ __('Éditer le rôle') }} : {{ $role->name }}</flux:heading>
                    @if (in_array($role->name, ['admin', 'super-admin']))
                        <flux:badge color="zinc" size="sm">
                            <flux:icon.lock-closed class="size-3" />
                            {{ __('Système') }}
                        </flux:badge>
                    @endif
                </div>
                <flux:subheading>{{ __('Modifiez le nom et les permissions associées à ce rôle.') }}</flux:subheading>
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
                            <flux:text class="text-sm text-amber-800 dark:text-amber-200">
                                @if (count($this->addedPermissions) > 0)
                                    <strong>+{{ count($this->addedPermissions) }}</strong> {{ __('permission(s) ajoutée(s)') }}
                                @endif
                                @if (count($this->removedPermissions) > 0)
                                    @if (count($this->addedPermissions) > 0), @endif
                                    <strong>-{{ count($this->removedPermissions) }}</strong> {{ __('permission(s) retirée(s)') }}
                                @endif
                            </flux:text>
                        </div>
                        <flux:button 
                            type="button"
                            wire:click="resetPermissions" 
                            variant="ghost" 
                            size="xs">
                            {{ __('Annuler les modifications') }}
                        </flux:button>
                    </div>
                </div>
            @endif

            {{-- Nom du rôle --}}
            <flux:input 
                wire:model="name" 
                label="{{ __('Nom du rôle') }}" 
                type="text" 
                required
                :disabled="in_array($role->name, ['admin', 'super-admin'])" 
            />

            {{-- Section Permissions --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:label>
                        {{ __('Permissions') }} 
                        <span class="text-zinc-500">({{ count($selectedPermissions) }}/{{ $this->allPermissions->count() }})</span>
                    </flux:label>

                    <div class="flex gap-2">
                        <flux:button 
                            type="button"
                            wire:click="selectAllPermissions" 
                            variant="ghost" 
                            size="sm">
                            {{ __('Tout sélectionner') }}
                        </flux:button>
                        <flux:button 
                            type="button"
                            wire:click="deselectAllPermissions" 
                            variant="ghost" 
                            size="sm">
                            {{ __('Tout désélectionner') }}
                        </flux:button>
                    </div>
                </div>

                <flux:text variant="subtle" class="text-xs">
                    {{ __('Modifiez les actions que ce rôle pourra effectuer.') }}
                </flux:text>

                {{-- Recherche de permissions --}}
                <flux:input 
                    wire:model.live.debounce.300ms="searchPermission"
                    type="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('Rechercher une permission...') }}"
                />

                {{-- Liste des permissions groupées --}}
                <div class="space-y-3 max-h-96 overflow-y-auto p-4 border rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                    @forelse ($this->groupedPermissions as $category => $permissions)
                        <div class="p-3 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                            {{-- En-tête de catégorie --}}
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center gap-2">
                                    <flux:heading size="sm" class="capitalize">{{ __($category) }}</flux:heading>
                                    <flux:badge color="zinc" size="sm">
                                        {{ count($permissions) }}
                                    </flux:badge>
                                </div>
                                <flux:button 
                                    type="button"
                                    wire:click="selectCategory('{{ $category }}')" 
                                    variant="ghost" 
                                    size="xs">
                                    {{ __('Sélectionner tout') }}
                                </flux:button>
                            </div>

                            {{-- Permissions de la catégorie --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach ($permissions as $permission)
                                    @php
                                        $isAdded = in_array($permission->name, $this->addedPermissions);
                                        $isRemoved = in_array($permission->name, $this->removedPermissions);
                                    @endphp
                                    <div class="flex items-center gap-2 p-1 rounded {{ $isAdded ? 'bg-green-50 dark:bg-green-900/20' : '' }} {{ $isRemoved ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                        <flux:checkbox 
                                            wire:model.live="selectedPermissions" 
                                            value="{{ $permission->name }}"
                                            label="{{ $permission->name }}" 
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
                    @empty
                        <div class="p-6 text-center">
                            <flux:icon.magnifying-glass class="size-10 mx-auto mb-2 text-zinc-400" />
                            <flux:text variant="subtle">{{ __('Aucune permission trouvée.') }}</flux:text>
                        </div>
                    @endforelse
                </div>

                @error('selectedPermissions') 
                    <flux:text variant="danger" class="text-sm">{{ $message }}</flux:text>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex justify-between items-center pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:text variant="subtle" class="text-sm">
                    {{ count($selectedPermissions) }} {{ __('permission(s) sélectionnée(s)') }}
                </flux:text>

                <div class="flex gap-3">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Annuler') }}</flux:button>
                    </flux:modal.close>
                    <flux:button 
                        variant="primary" 
                        type="submit" 
                        icon="check"
                        :disabled="!$this->hasChanges">
                        {{ __('Sauvegarder') }}
                    </flux:button>
                </div>
            </div>
        </form>
    @endif
</flux:modal>