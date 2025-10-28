<?php

use Spatie\Permission\Models\Role;
use Livewire\Attributes\{Computed, On};
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    // Propriétés pour la gestion du tableau
    public int $perPage = 15;
    public array $perPageOptions = [10, 15, 25, 50, 100];
    public array $selectedRoles = [];
    public bool $selectAll = false;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public string $search = '';
    public string $permissionFilter = '';
    public bool $showOnlyWithUsers = false;

    // Écoute l'événement émis par la modale après une modification
    #[On('role-updated')]
    public function refreshRoles(): void
    {
        $this->resetPage();
        $this->selectedRoles = [];
        $this->selectAll = false;
        unset($this->roles);
    }

    // Reset de la recherche
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPermissionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedShowOnlyWithUsers(): void
    {
        $this->resetPage();
    }

    // Réinitialiser tous les filtres
    public function resetFilters(): void
    {
        $this->search = '';
        $this->permissionFilter = '';
        $this->showOnlyWithUsers = false;
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    #[Computed]
    public function roles()
    {
        $query = Role::query()
            ->with('permissions')
            ->withCount('users');

        // Recherche par nom
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        // Filtre par permission
        if ($this->permissionFilter) {
            $query->whereHas('permissions', function ($q) {
                $q->where('name', $this->permissionFilter);
            });
        }

        // Filtre : uniquement les rôles avec utilisateurs
        if ($this->showOnlyWithUsers) {
            $query->has('users');
        }

        return $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function allPermissions()
    {
        return \Spatie\Permission\Models\Permission::orderBy('name')->get();
    }

    #[Computed]
    public function hasActiveFilters()
    {
        return !empty($this->search)
            || !empty($this->permissionFilter)
            || $this->showOnlyWithUsers
            || $this->sortField !== 'name'
            || $this->sortDirection !== 'asc';
    }

    // Tri des colonnes
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    // Changement du nombre de lignes par page
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    // Sélection/Désélection de tous les rôles (page courante)
    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedRoles = $this->roles->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedRoles = [];
        }
    }

    // Synchroniser selectAll quand on change selectedRoles
    public function updatedSelectedRoles(): void
    {
        $this->selectAll = count($this->selectedRoles) === $this->roles->count() && $this->roles->count() > 0;
    }

    // Sélectionner tous les rôles (toutes pages)
    public function selectAllRoles(): void
    {
        $allIds = Role::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->permissionFilter, fn($q) => $q->whereHas('permissions', fn($p) => $p->where('name', $this->permissionFilter)))
            ->when($this->showOnlyWithUsers, fn($q) => $q->has('users'))
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $this->selectedRoles = $allIds;
        $this->selectAll = true;
    }

    // Désélectionner tout
    public function deselectAll(): void
    {
        $this->selectedRoles = [];
        $this->selectAll = false;
    }

    // Exporter les rôles sélectionnés (CSV)
    public function exportSelected(): void
    {
        if (empty($this->selectedRoles)) {
            $this->dispatch(
                'notification',
                type: 'warning',
                message: __('Aucun rôle sélectionné.')
            );
            return;
        }

        $roles = Role::with('permissions', 'users')
            ->whereIn('id', $this->selectedRoles)
            ->get();

        $csv = "Rôle,Permissions,Nombre d'utilisateurs\n";
        foreach ($roles as $role) {
            $permissions = $role->permissions->pluck('name')->implode(', ');
            $csv .= "\"{$role->name}\",\"{$permissions}\",{$role->users->count()}\n";
        }

        $this->dispatch('download-csv', content: base64_encode($csv), filename: 'roles-' . date('Y-m-d') . '.csv');
    }

    // Dupliquer un rôle
    public function duplicateRole(int $id): void
    {
        $this->authorize('create roles');

        $role = Role::with('permissions')->findOrFail($id);

        $newRoleName = $role->name . ' (copie)';
        $counter = 1;

        while (Role::where('name', $newRoleName)->exists()) {
            $newRoleName = $role->name . ' (copie ' . $counter . ')';
            $counter++;
        }

        $newRole = Role::create(['name' => $newRoleName]);
        $newRole->syncPermissions($role->permissions);

        $this->dispatch(
            'notification',
            type: 'success',
            message: __("Le rôle ':role' a été dupliqué avec succès.", ['role' => $newRoleName])
        );

        $this->refreshRoles();
    }

    // Suppression d'un seul rôle
    public function deleteRole(int $id): void
    {
        $this->authorize('delete roles');

        $role = Role::findOrFail($id);

        if (in_array($role->name, ['admin', 'super-admin'])) {
            $this->dispatch(
                'notification',
                type: 'error',
                message: __('Les rôles système ne peuvent pas être supprimés.')
            );
            return;
        }

        if ($role->users()->exists()) {
            $this->dispatch(
                'notification',
                type: 'error',
                message: __('Ce rôle ne peut pas être supprimé car il est assigné à des utilisateurs.')
            );
            return;
        }

        $roleName = $role->name;
        $role->delete();

        $this->dispatch(
            'notification',
            type: 'success',
            message: __("Le rôle ':role' a été supprimé avec succès.", ['role' => $roleName])
        );

        $this->refreshRoles();
    }

    // Suppression multiple
    public function deleteSelected(): void
    {
        $this->authorize('delete roles');

        if (empty($this->selectedRoles)) {
            $this->dispatch(
                'notification',
                type: 'warning',
                message: __('Aucun rôle sélectionné.')
            );
            return;
        }

        $roles = Role::whereIn('id', $this->selectedRoles)
            ->whereNotIn('name', ['admin', 'super-admin'])
            ->get();

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($roles as $role) {
            if ($role->users()->exists()) {
                $skippedCount++;
                continue;
            }
            $role->delete();
            $deletedCount++;
        }

        $message = '';
        if ($deletedCount > 0) {
            $message .= __(':count rôle(s) supprimé(s) avec succès.', ['count' => $deletedCount]);
        }
        if ($skippedCount > 0) {
            $message .= ' ' . __(':count rôle(s) ignoré(s) (assignés à des utilisateurs).', ['count' => $skippedCount]);
        }

        $this->dispatch(
            'notification',
            type: $deletedCount > 0 ? 'success' : 'warning',
            message: $message
        );

        $this->refreshRoles();
    }

    // Action groupée : Assigner des permissions
    public function assignPermissionsToSelected(): void
    {
        $this->authorize('edit roles');

        if (empty($this->selectedRoles)) {
            $this->dispatch(
                'notification',
                type: 'warning',
                message: __('Aucun rôle sélectionné.')
            );
            return;
        }

        $this->dispatch('open-bulk-assign-permissions', roleIds: $this->selectedRoles);
    }
}; ?>

<x-layouts.content :heading="__('Gestion des Rôles')" :subheading="__('Définissez et configurez les rôles utilisateur et leurs permissions')">

    <x-slot name="actions">
        {{-- Actions groupées --}}
        <div class="flex items-center gap-2 flex-wrap">
            @if (count($selectedRoles) > 0)
                <flux:badge color="blue" size="lg">
                    {{ count($selectedRoles) }} {{ __('sélectionné(s)') }}
                </flux:badge>

                {{-- Sélectionner tout / Désélectionner --}}
                @if (count($selectedRoles) < $this->roles->total())
                    <flux:button variant="ghost" size="sm" wire:click="selectAllRoles">
                        {{ __('Tout sélectionner') }} ({{ $this->roles->total() }})
                    </flux:button>
                @else
                    <flux:button variant="ghost" size="sm" wire:click="deselectAll">
                        {{ __('Désélectionner tout') }}
                    </flux:button>
                @endif

                <flux:dropdown>
                    <flux:button variant="primary" size="sm" icon="chevron-down">
                        {{ __('Actions') }}
                    </flux:button>
                    <flux:menu>
                        @can('edit roles')
                            <flux:menu.item icon="shield-check" wire:click="assignPermissionsToSelected">
                                {{ __('Assigner des permissions') }}
                            </flux:menu.item>
                        @endcan
                        <flux:menu.item icon="arrow-down-tray" wire:click="exportSelected">
                            {{ __('Exporter (CSV)') }}
                        </flux:menu.item>
                        @can('delete roles')
                            <flux:menu.separator />
                            <flux:menu.item variant="danger" icon="trash" wire:click="deleteSelected"
                                wire:confirm="{{ __('Êtes-vous sûr de vouloir supprimer les rôles sélectionnés ?') }}">
                                {{ __('Supprimer la sélection') }}
                            </flux:menu.item>
                        @endcan
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>

        {{-- Bouton de création --}}
        @can('create roles')
            <flux:modal.trigger name="create-role">
                <flux:button variant="primary" icon="plus">
                    {{ __('Créer un rôle') }}
                </flux:button>
            </flux:modal.trigger>
        @endcan

    </x-slot>


    <div class="space-y-4">
        {{-- Barre de recherche et filtres --}}
        <div class="flex items-center flex-col sm:flex-row gap-2">
            {{-- Sélecteur nombre de lignes --}}
            <div class="flex items-center gap-2">
                <flux:select wire:model.live="perPage">
                    @foreach ($perPageOptions as $option)
                        <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Recherche --}}
            <div class="">
                <flux:input class="!w-48" wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass"
                    placeholder="{{ __('Rechercher un rôle...') }}" />
            </div>

            {{-- Filtre par permission --}}
            <div class="w-48">
                <flux:select wire:model.live="permissionFilter">
                    <option value="">{{ __('Toutes les permissions') }}</option>
                    @foreach ($this->allPermissions as $permission)
                        <option value="{{ $permission->name }}">{{ $permission->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Toggle : Rôles avec utilisateurs


            <flux:checkbox class="leading-none" wire:model.live="showOnlyWithUsers"
                label="{{ __('Avec utilisateurs') }}" />--}}


            {{-- Bouton reset filtres --}}
            @if ($this->hasActiveFilters)
                <flux:button wire:click="resetFilters" variant="ghost" icon="x-mark" size="sm">
                    {{ __('Réinitialiser') }}
                </flux:button>
            @endif
        </div>

        {{-- État vide --}}
        @if ($this->roles->isEmpty())
            <div class="p-12 text-center bg-zinc-50  rounded">
                <flux:icon.{{ $this->hasActiveFilters ? 'magnifying-glass' : 'key' }}
                    class="size-12 mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">
                    {{ $this->hasActiveFilters ? __('Aucun résultat') : __('Aucun rôle défini') }}
                </flux:heading>
                <flux:text variant="subtle">
                    {{ $this->hasActiveFilters
        ? __('Essayez de modifier vos critères de recherche.')
        : __('Commencez par créer le premier rôle (ex: Rédacteur, Administrateur).') 
                                }}
                </flux:text>
                @if ($this->hasActiveFilters)
                    <flux:button wire:click="resetFilters" variant="ghost" icon="arrow-path" class="mt-4">
                        {{ __('Réinitialiser les filtres') }}
                    </flux:button>
                @endif
            </div>
        @else
            {{-- Tableau des rôles --}}
            <div class="overflow-hidden border rounded border-zinc-200">
                <table class="w-full">
                    <thead class="bg-zinc-50 border-b border-zinc-200 font-semibold">
                        <tr>
                            {{-- Checkbox sélection --}}
                            <th class="px-2.5 py-2 text-left w-12">
                                <flux:checkbox wire:model.live="selectAll" />
                            </th>

                            {{-- Colonne Rôle (triable) --}}
                            <th class="px-2.5 py-2 text-left text-xs text-zinc-500 tracking-wider">
                                <button wire:click="sortBy('name')"
                                    class="flex items-center gap-1 hover:text-zinc-700 font-semibold uppercase transition-colors">
                                    {{ __('Rôle') }}
                                    @if ($sortField === 'name')
                                        <flux:icon.{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}
                                            class="size-4" />
                                    @else
                                        <flux:icon.chevron-up-down class="size-4 opacity-30" />
                                    @endif
                                </button>
                            </th>

                            {{-- Colonne Permissions
                            <th class="px-2.5 py-2 text-left text-xs font-medium text-zinc-500  uppercase tracking-wider">
                                {{ __('Permissions Associées') }}
                            </th> --}}

                            {{-- Colonne Utilisateurs (triable) --}}
                            <th class="px-2.5 py-2 text-center text-xs font-medium text-zinc-500 tracking-wider">
                                <button wire:click="sortBy('users_count')"
                                    class="flex items-center gap-1 hover:text-zinc-700  mx-auto font-semibold  uppercase transition-colors">
                                    {{ __('Utilisateurs') }}
                                    @if ($sortField === 'users_count')
                                        <flux:icon.{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}
                                            class="size-4" />
                                    @else
                                        <flux:icon.chevron-up-down class="size-4 opacity-30" />
                                    @endif
                                </button>
                            </th>

                            {{-- Actions --}}
                            <th class="px-2.5 py-2 text-right text-xs font-medium text-zinc-500 font-semibold  uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-zinc-200">
                        @foreach ($this->roles as $role)
                            <tr wire:key="role-{{ $role->id }}"
                                class="hover:bg-zinc-50  transition-colors"
                                :class="{ 'bg-blue-50 ': @js(in_array($role->id, $selectedRoles)) }">

                                {{-- Checkbox sélection --}}
                                <td class="px-2.5 py-1">
                                    <flux:checkbox wire:model.live="selectedRoles" value="{{ $role->id }}" />
                                </td>

                                {{-- Nom du rôle --}}
                                <td class="px-2.5 py-1 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="font-medium capitalize">
                                            {{ $role->name }}
                                        </div>
                                        @if (in_array($role->name, ['Ghost', 'Admin']))
                                            <flux:badge color="red" size="sm">
                                                <flux:icon.lock-closed class="size-3" />
                                            </flux:badge>
                                        @endif
                                    </div>
                                </td>

                                {{-- Permissions
                                <td class="px-2.5 py-1">
                                    <div class="flex flex-wrap gap-1 max-w-lg">
                                        @forelse ($role->permissions->sortBy('name')->take(3) as $permission)
                                        <flux:badge variant="pill" color="blue" size="sm">
                                            {{ $permission->name }}
                                        </flux:badge>
                                        @empty
                                        <flux:text variant="subtle" class="text-sm">
                                            {{ __('Aucune permission') }}
                                        </flux:text>
                                        @endforelse

                                        @if ($role->permissions->count() > 3)
                                        <flux:badge variant="pill" color="zinc" size="sm">
                                            +{{ $role->permissions->count() - 3 }}
                                        </flux:badge>
                                        @endif
                                    </div>
                                </td> --}}

                                {{-- Nombre d'utilisateurs --}}
                                <td class="px-2.5 py-1 whitespace-nowrap text-center">
                                    <flux:badge color="{{ $role->users_count > 0 ? 'blue' : 'zinc' }}" size="sm">
                                        {{ $role->users_count }}
                                    </flux:badge>
                                </td>

                                {{-- Actions --}}
                                <td class="px-2.5 py-1 whitespace-nowrap text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button  size="sm" icon="ellipsis-vertical" square />
                                        <flux:menu>
                                            <flux:menu.item icon="eye"
                                                wire:click="$dispatch('open-view-permissions-modal', { roleId: {{ $role->id }} })">
                                                {{ __('Voir les permissions') }}
                                            </flux:menu.item>
                                            @can('edit roles')
                                                <flux:menu.item icon="pencil"
                                                    wire:click="$dispatch('open-edit-role-modal', { roleId: {{ $role->id }} })">
                                                    {{ __('Éditer') }}
                                                </flux:menu.item>
                                            @endcan

                                            @can('create roles')
                                                <flux:menu.item icon="document-duplicate"
                                                    wire:click="duplicateRole({{ $role->id }})">
                                                    {{ __('Dupliquer') }}
                                                </flux:menu.item>
                                            @endcan

                                            @can('delete roles')
                                                @if (!in_array($role->name, ['admin', 'super-admin']))
                                                    <flux:menu.separator />
                                                    <flux:menu.item variant="danger" icon="trash"
                                                        wire:click="deleteRole({{ $role->id }})"
                                                        wire:confirm="{{ __('Êtes-vous sûr de vouloir supprimer ce rôle ?') }}">
                                                        {{ __('Supprimer') }}
                                                    </flux:menu.item>
                                                @endif
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination et nombre de lignes --}}
            <div class="flex items-center justify-between flex-wrap gap-4">


                {{-- Pagination --}}
                <div>
                    {{ $this->roles->links() }}
                </div>

                {{-- Info total --}}
                <flux:text variant="subtle" class="text-sm">
                    {{ __('Total : :total rôle(s)', ['total' => $this->roles->total()]) }}
                </flux:text>
            </div>
        @endif
    </div>

    {{-- Modales --}}
    <livewire:admin.roles.modals.create-role />
    <livewire:admin.roles.modals.view-permissions />
    <livewire:admin.roles.modals.edit-role />

    {{-- Script pour télécharger le CSV --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('download-csv', (event) => {
                const content = atob(event.content);
                const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = event.filename;
                link.click();
            });
        });
    </script>
</x-layouts.content>