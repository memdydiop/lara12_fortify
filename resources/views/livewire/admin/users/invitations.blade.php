<?php

use App\Models\Invitation;
use Illuminate\Support\Facades\Notification;
use App\Notifications\UserInvited;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $roleFilter = 'all';
    public array $selectedInvitations = [];
    public bool $selectAll = false;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 15;
    public array $perPageOptions = [10, 15, 25, 50, 100];

    #[On('invitation-sent')]
    #[On('user-created')]
    public function refreshList(): void
    {
        $this->resetPage();
        $this->selectedInvitations = [];
        $this->selectAll = false;
        unset($this->invitations);
    }

    #[Computed]
    public function invitations()
    {
        $query = Invitation::with('inviter')
            ->when($this->search, function ($query) {
                $query->where('email', 'like', '%' . $this->search . '%');
            })
            ->when($this->roleFilter !== 'all', function ($query) {
                $query->where('role', $this->roleFilter);
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                switch ($this->statusFilter) {
                    case 'pending':
                        $query->pending();
                        break;
                    case 'registered':
                        $query->registered();
                        break;
                    case 'expired':
                        $query->expired();
                        break;
                }
            });

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function availableRoles()
    {
        return \Spatie\Permission\Models\Role::orderBy('name')->pluck('name', 'name');
    }

    #[Computed]
    public function hasActiveFilters()
    {
        return !empty($this->search)
            || $this->statusFilter !== 'all'
            || $this->roleFilter !== 'all'
            || $this->sortField !== 'created_at'
            || $this->sortDirection !== 'desc';
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    // Réinitialiser tous les filtres
    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->roleFilter = 'all';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    // Sélection/Désélection de toutes les invitations (page courante)
    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedInvitations = $this->invitations->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedInvitations = [];
        }
    }

    // Synchroniser selectAll quand on change selectedInvitations
    public function updatedSelectedInvitations(): void
    {
        $this->selectAll = count($this->selectedInvitations) === $this->invitations->count() && $this->invitations->count() > 0;
    }

    // Sélectionner toutes les invitations (toutes pages)
    public function selectAllInvitations(): void
    {
        $allIds = Invitation::query()
            ->when($this->search, fn($q) => $q->where('email', 'like', '%' . $this->search . '%'))
            ->when($this->roleFilter !== 'all', fn($q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter !== 'all', function ($q) {
                switch ($this->statusFilter) {
                    case 'pending':
                        $q->pending();
                        break;
                    case 'registered':
                        $q->registered();
                        break;
                    case 'expired':
                        $q->expired();
                        break;
                }
            })
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $this->selectedInvitations = $allIds;
        $this->selectAll = true;
    }

    // Désélectionner tout
    public function deselectAll(): void
    {
        $this->selectedInvitations = [];
        $this->selectAll = false;
    }

    // Renvoyer plusieurs invitations
    public function resendSelected(): void
    {
        if (empty($this->selectedInvitations)) {
            $this->dispatch(
                'notification',
                type: 'warning',
                message: __('Aucune invitation sélectionnée.')
            );
            return;
        }

        $invitations = Invitation::whereIn('id', $this->selectedInvitations)
            ->pending()
            ->get();

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($invitations as $invitation) {
            if (!$this->can('resend', $invitation)) {
                $skippedCount++;
                continue;
            }

            Notification::route('mail', $invitation->email)
                ->notify(new UserInvited($invitation));

            $sentCount++;
        }

        $message = '';
        if ($sentCount > 0) {
            $message .= __(':count invitation(s) renvoyée(s) avec succès.', ['count' => $sentCount]);
        }
        if ($skippedCount > 0) {
            $message .= ' ' . __(':count invitation(s) ignorée(s) (droits insuffisants ou statut invalide).', ['count' => $skippedCount]);
        }

        $this->dispatch(
            'notification',
            type: $sentCount > 0 ? 'success' : 'warning',
            message: $message
        );

        $this->deselectAll();
    }

    // Suppression multiple
    public function deleteSelected(): void
    {
        if (empty($this->selectedInvitations)) {
            $this->dispatch(
                'notification',
                type: 'warning',
                message: __('Aucune invitation sélectionnée.')
            );
            return;
        }

        $invitations = Invitation::whereIn('id', $this->selectedInvitations)
            ->get();

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($invitations as $invitation) {
            if (!$this->can('delete', $invitation) || $invitation->isRegistered()) {
                $skippedCount++;
                continue;
            }

            $invitation->delete();
            $deletedCount++;
        }

        $message = '';
        if ($deletedCount > 0) {
            $message .= __(':count invitation(s) supprimée(s) avec succès.', ['count' => $deletedCount]);
        }
        if ($skippedCount > 0) {
            $message .= ' ' . __(':count invitation(s) ignorée(s) (droits insuffisants ou déjà acceptées).', ['count' => $skippedCount]);
        }

        $this->dispatch(
            'notification',
            type: $deletedCount > 0 ? 'success' : 'warning',
            message: $message
        );

        $this->refreshList();
    }

    public function resendInvitation(int $id): void
    {
        $invitation = Invitation::findOrFail($id);

        $invitation->update([
            'expires_at' => now()->addDays(7),
        ]);

        $this->authorize('resend', $invitation);

        Notification::route('mail', $invitation->email)
            ->notify(new UserInvited($invitation));

        $this->dispatch(
            'notification',
            type: 'success',
            message: 'Invitation renvoyée avec succès'
        );
    }

    public function deleteInvitation(int $id): void
    {
        $invitation = Invitation::findOrFail($id);

        $this->authorize('delete', $invitation);

        $invitation->delete();

        $this->dispatch(
            'notification',
            type: 'success',
            message: 'Invitation supprimée'
        );

        $this->refreshList();
    }
}; ?>

<x-layouts.content :heading="__('Invitations')" :subheading="__('Gérez les invitations envoyées aux futurs utilisateurs')">

    <x-slot name="actions">
        {{-- Actions groupées --}}
        @if (count($selectedInvitations) > 0)
            <div class="flex items-center gap-2 flex-wrap">
                <flux:badge color="blue" size="lg">
                    {{ count($selectedInvitations) }} {{ __('sélectionnée(s)') }}
                </flux:badge>

                {{-- Sélectionner tout / Désélectionner --}}
                @if (count($selectedInvitations) < $this->invitations->total())
                    <flux:button variant="ghost" size="sm" wire:click="selectAllInvitations">
                        {{ __('Tout sélectionner') }} ({{ $this->invitations->total() }})
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
                        @can('resend invitations')
                            <flux:menu.item icon="arrow-path" wire:click="resendSelected">
                                {{ __('Renvoyer la sélection') }}
                            </flux:menu.item>
                        @endcan
                        @can('delete invitations')
                            <flux:menu.separator />
                            <flux:menu.item variant="danger" icon="trash" wire:click="deleteSelected"
                                wire:confirm="{{ __('Êtes-vous sûr de vouloir supprimer les invitations sélectionnées ?') }}">
                                {{ __('Supprimer la sélection') }}
                            </flux:menu.item>
                        @endcan
                    </flux:menu>
                </flux:dropdown>
            </div>
        @endif
        @can('create invitations')
            <flux:modal.trigger name="invite-user">
                <flux:button variant="primary" icon="plus">
                    {{ __('Invitation') }}
                </flux:button>
            </flux:modal.trigger>
        @endcan
    </x-slot>

    <div class="space-y-4">

        {{-- Barre de recherche et filtres --}}
        <div class="flex items-center flex-col sm:flex-row gap-2">
            {{-- Sélecteur nombre de lignes --}}
            <div class="flex items-center gap-2">
                <flux:select wire:model.live="perPage" class="!w-20">
                    @foreach ($perPageOptions as $option)
                        <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Rechercher par email..." class="!w-48" clearable />

            <flux:select class="!w-48" wire:model.live="statusFilter">
                <flux:select.option value="all">{{ __('Tous les statuts') }}</flux:select.option>
                <flux:select.option value="pending">{{ __('En attente') }}</flux:select.option>
                <flux:select.option value="registered">{{ __('Acceptées') }}</flux:select.option>
                <flux:select.option value="expired">{{ __('Expirées') }}</flux:select.option>
            </flux:select>

            <flux:select class="!w-48" wire:model.live="roleFilter">
                <flux:select.option value="all">{{ __('Tous les rôles') }}</flux:select.option>
                @foreach ($this->availableRoles as $roleName)
                    <flux:select.option value="{{ $roleName }}">{{ $roleName }}</flux:select.option>
                @endforeach
            </flux:select>

            {{-- Bouton reset filtres --}}
            @if ($this->hasActiveFilters)
                <flux:button wire:click="resetFilters" variant="ghost" icon="x-mark" size="sm">
                    {{ __('Réinitialiser') }}
                </flux:button>
            @endif
        </div>

        {{-- État vide --}}
        @if ($this->invitations->isEmpty())
            <div class="p-12 text-center bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <flux:icon.envelope class="size-12 mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">{{ __('Aucune invitation') }}</flux:heading>
                <flux:text variant="subtle">
                    {{ $this->hasActiveFilters ? __('Aucun résultat pour vos critères') : __('Commencez par envoyer votre première invitation') }}
                </flux:text>
                @if ($this->hasActiveFilters)
                    <flux:button wire:click="resetFilters" variant="ghost" icon="arrow-path" class="mt-4">
                        {{ __('Réinitialiser les filtres') }}
                    </flux:button>
                @endif
            </div>
        @else
            {{-- Tableau --}}
            <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            {{-- Checkbox sélection --}}
                            <th class="px-2.5 py-2 text-left w-12">
                                <flux:checkbox wire:model.live="selectAll" />
                            </th>

                            <th class="px-2 py-1.5 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
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
                            <th class="px-2 py-1.5 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                {{ __('Rôle') }}
                            </th>
                            <th class="px-2 py-1.5 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                {{ __('Statut') }}
                            </th>
                            <th class="px-2 py-1.5 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                <button wire:click="sortBy('expires_at')"
                                    class="flex items-center gap-1 hover:text-zinc-700 font-semibold uppercase transition-colors">
                                    {{ __('Expire le') }}
                                    @if ($sortField === 'expires_at')
                                        <flux:icon.{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}
                                            class="size-4" />
                                    @else
                                        <flux:icon.chevron-up-down class="size-4 opacity-30" />
                                    @endif
                                </button>
                            </th>
                            <th class="px-2 py-1.5 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->invitations as $invitation)
                            <tr wire:key="invitation-{{ $invitation->id }}"
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
                                :class="{ 'bg-blue-50 dark:bg-blue-900/20': @js(in_array($invitation->id, $selectedInvitations)) }">

                                {{-- Checkbox sélection --}}
                                <td class="px-2.5 py-1">
                                    <flux:checkbox wire:model.live="selectedInvitations" value="{{ $invitation->id }}" />
                                </td>

                                <td class="px-2 py-1.5 whitespace-nowrap">
                                    <flux:text class="font-medium">{{ $invitation->email }}</flux:text>
                                </td>
                                <td class="px-2 py-1.5 whitespace-nowrap">
                                    <flux:badge variant="pill" color="blue" size="sm" class="capitalize">
                                        {{ $invitation->role }}
                                    </flux:badge>
                                </td>
                                <td class="px-2 py-1.5 whitespace-nowrap">
                                    @if ($invitation->isRegistered())
                                        <flux:badge color="green" icon="check-circle">
                                            {{ __('Acceptée') }}
                                        </flux:badge>
                                    @elseif ($invitation->isExpired())
                                        <flux:badge color="red" icon="x-circle">
                                            {{ __('Expirée') }}
                                        </flux:badge>
                                    @else
                                        <flux:badge color="yellow" icon="clock">
                                            {{ __('En attente') }}
                                        </flux:badge>
                                    @endif
                                </td>
                                <td class="px-2 py-1.5 whitespace-nowrap">
                                    <flux:text variant="subtle" class="text-xs">
                                        {{ $invitation->expires_at->format('d/m/Y H:i') }}
                                    </flux:text>
                                </td>
                                <td class="px-2 py-1.5 whitespace-nowrap text-right">
                                    @if (!$invitation->isRegistered())
                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" square />

                                            <flux:menu class=" divide-y-2">
                                                @if ($invitation->isExpired())
                                                    @can('resend', $invitation)
                                                        <flux:menu.item icon="arrow-path"
                                                            wire:click="resendInvitation({{ $invitation->id }})">
                                                            {{ __('Renvoyer') }}
                                                        </flux:menu.item>
                                                    @endcan
                                                @endif

                                                @can('delete', $invitation)
                                                    <flux:menu.item variant="danger" icon="trash"
                                                        wire:click="deleteInvitation({{ $invitation->id }})"
                                                        wire:confirm="Êtes-vous sûr de vouloir supprimer cette invitation ?">
                                                        {{ __('Supprimer') }}
                                                    </flux:menu.item>
                                                @endcan
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination et informations --}}
            <div class="flex items-center justify-between flex-wrap gap-4">
                {{-- Pagination --}}
                <div>
                    {{ $this->invitations->links() }}
                </div>

                {{-- Info total --}}
                <flux:text variant="subtle" class="text-sm">
                    {{ __('Total : :total invitation(s)', ['total' => $this->invitations->total()]) }}
                </flux:text>
            </div>
        @endif
    </div>

    <livewire:admin.users.modals.invite-user />
</x-layouts.content>