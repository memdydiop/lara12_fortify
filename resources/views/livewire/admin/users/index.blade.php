<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public array $selectedUsers = [];
    public bool $selectAll = false;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public int $perPage = 15;
    public array $perPageOptions = [10, 15, 25, 50, 100];

    #[On('user-created')]
    #[On('user-updated')]
    public function refreshList(): void
    {
        $this->selectedUsers = [];
        $this->selectAll = false;
        unset($this->users);
    }

    #[Computed]
    public function users()
    {
        return User::with('roles')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function deleteUser(int $id): void
    {
        $user = User::findOrFail($id);

        $this->authorize('delete', $user);

        if ($user->id === auth()->id()) {
            $this->dispatch('notification', type: 'error', message: 'Vous ne pouvez pas supprimer votre propre compte.');
            return;
        }

        $user->delete();

        $this->dispatch('notification', type: 'success', message: 'Utilisateur supprimé avec succès.');
        $this->refreshList();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
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

    // Sélection/Désélection de tous les utilisateurs (page courante)
    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedUsers = $this->users->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    // Synchroniser selectAll quand on change selectedUsers
    public function updatedSelectedUsers(): void
    {
        $this->selectAll = count($this->selectedUsers) === $this->users->count() && $this->users->count() > 0;
    }

    // Sélectionner tous les utilisateurs (toutes pages)
    public function selectAllUsers(): void
    {
        $allIds = User::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%'))
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $this->selectedUsers = $allIds;
        $this->selectAll = true;
    }

    // Désélectionner tout
    public function deselectAll(): void
    {
        $this->selectedUsers = [];
        $this->selectAll = false;
    }

    // Suppression multiple
    public function deleteSelected(): void
    {
        if (empty($this->selectedUsers)) {
            $this->dispatch(
                'notification',
                type: 'warning',
                message: __('Aucun utilisateur sélectionné.')
            );
            return;
        }

        $users = User::whereIn('id', $this->selectedUsers)
            ->where('id', '!=', auth()->id())
            ->get();

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            if ($user->id === auth()->id()) {
                $skippedCount++;
                continue;
            }

            if (!$this->authorize('delete', $user)) {
                $skippedCount++;
                continue;
            }

            $user->delete();
            $deletedCount++;
        }

        $message = '';
        if ($deletedCount > 0) {
            $message .= __(':count utilisateur(s) supprimé(s) avec succès.', ['count' => $deletedCount]);
        }
        if ($skippedCount > 0) {
            $message .= ' ' . __(':count utilisateur(s) ignoré(s) (droits insuffisants ou votre propre compte).', ['count' => $skippedCount]);
        }

        $this->dispatch(
            'notification',
            type: $deletedCount > 0 ? 'success' : 'warning',
            message: $message
        );

        $this->refreshList();
    }

    #[Computed]
    public function hasActiveFilters()
    {
        return !empty($this->search)
            || $this->sortField !== 'name'
            || $this->sortDirection !== 'asc';
    }

    // Réinitialiser tous les filtres
    public function resetFilters(): void
    {
        $this->search = '';
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }
}; ?>

<x-layouts.content :heading="__('Utilisateurs')" :subheading="__('Gérez les utilisateurs de votre application')">

    <x-slot name="actions">
        {{-- Actions groupées --}}
        @if (count($selectedUsers) > 0)
            <div class="flex items-center gap-2 flex-wrap">
                <flux:badge color="blue" size="lg">
                    {{ count($selectedUsers) }} {{ __('sélectionné(s)') }}
                </flux:badge>

                {{-- Sélectionner tout / Désélectionner --}}
                @if (count($selectedUsers) < $this->users->total())
                    <flux:button variant="ghost" size="sm" wire:click="selectAllUsers">
                        {{ __('Tout sélectionner') }} ({{ $this->users->total() }})
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
                    @can('delete users')
                        <flux:menu>
                            <flux:menu.item variant="danger" icon="trash" wire:click="deleteSelected"
                                wire:confirm="{{ __('Êtes-vous sûr de vouloir supprimer les utilisateurs sélectionnés ?') }}">
                                {{ __('Supprimer la sélection') }}
                            </flux:menu.item>
                        </flux:menu>
                    @endcan
                </flux:dropdown>
            </div>
        @endif

        {{-- Bouton d'invitation --}}
        @can('create invitations')
            <flux:modal.trigger name="invite-user">
                <flux:button variant="primary" icon="plus">
                    {{ __('Inviter un utilisateur') }}
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
                    placeholder="{{ __('Rechercher un utilisateur...') }}" />
            </div>

            {{-- Bouton reset filtres --}}
            @if ($this->hasActiveFilters)
                <flux:button wire:click="resetFilters" variant="ghost" icon="x-mark" size="sm">
                    {{ __('Réinitialiser') }}
                </flux:button>
            @endif
        </div>

        {{-- État vide --}}
        @if ($this->users->isEmpty())
            <div class="p-12 text-center bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <flux:icon.users class="size-12 mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">
                    {{ $this->hasActiveFilters ? __('Aucun résultat') : __('Aucun utilisateur') }}
                </flux:heading>
                <flux:text variant="subtle">
                    {{ $this->hasActiveFilters
            ? __('Essayez de modifier vos critères de recherche.')
            : __('Les utilisateurs apparaîtront ici.') 
                                }}
                </flux:text>
                @if ($this->hasActiveFilters)
                    <flux:button wire:click="resetFilters" variant="ghost" icon="arrow-path" class="mt-4">
                        {{ __('Réinitialiser les filtres') }}
                    </flux:button>
                @endif
            </div>
        @else
            {{-- Tableau des utilisateurs --}}
            <div class="overflow-hidden border rounded border-zinc-200">
                <table class="w-full">
                    <thead class="bg-zinc-50 border-b border-zinc-200">
                        <tr>
                            {{-- Checkbox sélection --}}
                            <th class="pl-2.5 py-2 text-left w-2">
                                <flux:checkbox wire:model.live="selectAll" />
                            </th>

                            {{-- Colonne Utilisateur (triable) --}}
                            <th
                                class="pl-2.5 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <button wire:click="sortBy('name')"
                                    class="flex items-center gap-1 hover:text-zinc-700 font-semibold uppercase transition-colors">
                                    {{ __('Utilisateur') }}
                                    @if ($sortField === 'name')
                                        <flux:icon.{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}
                                            class="size-4" />
                                    @else
                                        <flux:icon.chevron-up-down class="size-4 opacity-30" />
                                    @endif
                                </button>
                            </th>

                            {{-- Colonne Email (triable) --}}
                            <th
                                class="pl-2.5 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <button wire:click="sortBy('email')"
                                    class="flex items-center gap-1 hover:text-zinc-700 font-semibold uppercase transition-colors">
                                    {{ __('Email') }}
                                    @if ($sortField === 'email')
                                        <flux:icon.{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}
                                            class="size-4" />
                                    @else
                                        <flux:icon.chevron-up-down class="size-4 opacity-30" />
                                    @endif
                                </button>
                            </th>

                            {{-- Colonne Rôles --}}
                            <th
                                class="pl-2.5 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                {{ __('Rôles') }}
                            </th>

                            {{-- Actions --}}
                            <th
                                class="px-2.5 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-zinc-200">
                        @foreach ($this->users as $user)
                            <tr wire:key="user-{{ $user->id }}"
                                class="hover:bg-zinc-50 transition-colors"
                                :class="{ 'bg-blue-50': @js(in_array($user->id, $selectedUsers)) }">

                                {{-- Checkbox sélection --}}
                                <td class="pl-2.5 py-1.5">
                                    <flux:checkbox wire:model.live="selectedUsers" value="{{ $user->id }}" />
                                </td>

                                {{-- Nom et avatar --}}
                                <td class="pl-2.5 py-1.5 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            @if($user->avatar)
                                                <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}"
                                                    class="size-8 mask mask-squircle object-cover ">
                                            @else
                                                <div
                                                    class="size-8 bg-zinc-200 mask mask-squircle flex items-center justify-center text-white text-xs font-bold">
                                                    {{ $user->initials() }}
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ $user->name }}</div>
                                            <div class="text-xs text-zinc-500">
                                                {{ $user->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Email --}}
                                <td class="pl-2.5 py-1.5 whitespace-nowrap">
                                    <flux:text class="text-sm">{{ $user->email }}</flux:text>
                                </td>

                                {{-- Rôles --}}
                                <td class="pl-2.5 py-1.5">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($user->getRoleNames() as $role)
                                            <flux:badge variant="outline" color="gray" size="sm">{{ $role }}</flux:badge>
                                        @empty
                                            <flux:badge variant="pill" color="zinc" size="sm">
                                                {{ __('Aucun rôle') }}
                                            </flux:badge>
                                        @endforelse
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-2.5 py-1.5 whitespace-nowrap text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" square />
                                        <flux:menu>
                                            {{-- Édition Rôles/Permissions --}}
                                            @can('updateRolesAndPermissions', $user)
                                                <flux:menu.item icon="pencil"
                                                    x-on:click="$dispatch('open-edit-user-modal', { userId: {{ $user->id }} })">
                                                    {{ __('Éditer Rôles & Permissions') }}
                                                </flux:menu.item>
                                            @endcan

                                            {{-- Séparateur pour distinguer les actions --}}
                                            @if ($user->id !== auth()->id())
                                                @can('updateRolesAndPermissions', $user)
                                                    <flux:menu.separator />
                                                @endcan
                                            @endif

                                            {{-- Bouton de Suppression --}}
                                            @if ($user->id !== auth()->id())
                                                @can('delete', $user)
                                                    <flux:menu.item variant="danger" icon="trash"
                                                        wire:click="deleteUser({{ $user->id }})"
                                                        wire:confirm="Êtes-vous sûr de vouloir supprimer cet utilisateur ?">
                                                        {{ __('Supprimer') }}
                                                    </flux:menu.item>
                                                @endcan
                                            @endif
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
                    {{ $this->users->links() }}
                </div>

                {{-- Info total --}}
                <flux:text variant="subtle" class="text-sm">
                    {{ __('Total : :total utilisateur(s)', ['total' => $this->users->total()]) }}
                </flux:text>
            </div>
        @endif
    </div>

    <livewire:admin.users.modals.invite-user />
    <livewire:admin.users.modals.edit-user />
</x-layouts.content>