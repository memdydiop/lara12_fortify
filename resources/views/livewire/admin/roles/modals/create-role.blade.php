<?php

use App\Actions\Roles\CreateRoleAction;
use Spatie\Permission\Models\Permission;
use Livewire\Volt\Component;

new class extends Component {
    public bool $showModal = false;
    public string $name = '';
    public array $selectedPermissions = [];
    public string $searchPermission = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'selectedPermissions' => ['nullable', 'array'],
            'selectedPermissions.*' => ['exists:permissions,name'],
        ];
    }

    public function save(): void
    {
        $this->authorize('create roles');

        $validated = $this->validate();

        // Créer le rôle
        $role = \Spatie\Permission\Models\Role::create(['name' => $validated['name']]);

        // Assigner les permissions
        if (!empty($validated['selectedPermissions'])) {
            $role->syncPermissions($validated['selectedPermissions']);
        }

        // Notification et rafraîchissement
        $this->dispatch('role-updated');
        $this->dispatch(
            'notification',
            type: 'success',
            message: __("Le rôle ':role' a été créé avec succès.", ['role' => $this->name])
        );

        $this->reset(['name', 'selectedPermissions', 'searchPermission']);
        $this->showModal = false;
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
}; ?>

<flux:modal name="create-role" wire:model="showModal" class="max-w-4xl">
    <form wire:submit="save" class="space-y-6">
        {{-- En-tête --}}
        <div>
            <flux:heading size="lg">{{ __('Créer un nouveau rôle') }}</flux:heading>
            <flux:subheading>{{ __('Définissez un nom et attribuez les permissions nécessaires à ce rôle.') }}
            </flux:subheading>
        </div>

        {{-- Nom du rôle --}}
        <flux:input wire:model="name" label="{{ __('Nom du rôle') }}" type="text"
            placeholder="{{ __('Ex: Éditeur, Modérateur, Gestionnaire...') }}" required />

        {{-- Section Permissions --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:label>{{ __('Permissions') }}
                    ({{ count($selectedPermissions) }}/{{ $this->allPermissions->count() }})</flux:label>

                <div class="flex gap-2">
                    <flux:button type="button" wire:click="selectAllPermissions" variant="ghost" size="sm">
                        {{ __('Tout sélectionner') }}
                    </flux:button>
                    <flux:button type="button" wire:click="deselectAllPermissions" variant="ghost" size="sm">
                        {{ __('Tout désélectionner') }}
                    </flux:button>
                </div>
            </div>

            <flux:text variant="subtle" class="text-xs">
                {{ __('Choisissez les actions que ce rôle pourra effectuer.') }}
            </flux:text>

            {{-- Recherche de permissions --}}
            <flux:input wire:model.live.debounce.300ms="searchPermission" type="search" icon="magnifying-glass"
                placeholder="{{ __('Rechercher une permission...') }}" />

            {{-- Liste des permissions groupées --}}
            <div
                class="space-y-3 max-h-96 overflow-y-auto p-4 border rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                @forelse ($this->groupedPermissions as $category => $permissions)
                    <div class="p-3 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                        {{-- En-tête de catégorie --}}
                        <div
                            class="flex items-center justify-between mb-3 pb-2 border-b border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center gap-2">
                                <flux:heading size="sm" class="capitalize">{{ __($category) }}</flux:heading>
                                <flux:badge color="zinc" size="sm">
                                    {{ count($permissions) }}
                                </flux:badge>
                            </div>
                            <flux:button type="button" wire:click="selectCategory('{{ $category }}')" variant="ghost"
                                size="xs">
                                {{ __('Sélectionner tout') }}
                            </flux:button>
                        </div>

                        {{-- Permissions de la catégorie --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach ($permissions as $permission)
                                <flux:checkbox wire:model.live="selectedPermissions" value="{{ $permission->name }}"
                                    label="{{ $permission->name }}" />
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
                <flux:button variant="primary" type="submit" icon="check">
                    {{ __('Créer le rôle') }}
                </flux:button>
            </div>
        </div>
    </form>
</flux:modal>