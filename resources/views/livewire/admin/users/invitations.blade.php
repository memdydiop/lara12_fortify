<?php

use App\Models\Invitation;
use Illuminate\Support\Facades\Notification;
use App\Notifications\UserInvited;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    #[Computed]
    public function invitations()
    {
        $query = Invitation::with('inviter')
            ->orderBy('created_at', 'desc');

        if ($this->search) {
            $query->where('email', 'like', '%' . $this->search . '%');
        }

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

        return $query->paginate(15);
    }

    public function resendInvitation(int $id): void
    {
        $invitation = Invitation::findOrFail($id);

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
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }
}; ?>

<x-layouts.content :heading="__('Invitations')" :subheading="__('Gérez les invitations envoyées aux futurs utilisateurs')">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                    placeholder="Rechercher par email..." class="w-64" clearable />

                <flux:select wire:model.live="statusFilter" size="sm">
                    <option value="all">{{ __('Tous les statuts') }}</option>
                    <option value="pending">{{ __('En attente') }}</option>
                    <option value="registered">{{ __('Acceptées') }}</option>
                    <option value="expired">{{ __('Expirées') }}</option>
                </flux:select>
            </div>

            @can('create invitations')
                <flux:modal.trigger name="invite-user">
                    <flux:button variant="primary" icon="plus">
                        {{ __('Envoyer une invitation') }}
                    </flux:button>
                </flux:modal.trigger>
            @endcan
        </div>

        @if ($this->invitations->isEmpty())
            <div class="p-12 text-center bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <flux:icon.envelope class="size-12 mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">{{ __('Aucune invitation') }}</flux:heading>
                <flux:text variant="subtle">
                    {{ $search ? __('Aucun résultat pour votre recherche') : __('Commencez par envoyer votre première invitation') }}
                </flux:text>
            </div>
        @else
            <div class="overflow-hidden border rounded-lg border-zinc-200 dark:border-zinc-700">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                {{ __('Email') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                {{ __('Rôles') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                {{ __('Statut') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                {{ __('Envoyée le') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                {{ __('Expire le') }}
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->invitations as $invitation)
                            <tr wire:key="invitation-{{ $invitation->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:text class="font-medium">{{ $invitation->email }}</flux:text>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($invitation->roles)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($invitation->roles as $role)
                                                <flux:badge variant="pill" color="blue" size="sm">
                                                    {{ $role }}
                                                </flux:badge>
                                            @endforeach
                                        </div>
                                    @else
                                        <flux:text variant="subtle">—</flux:text>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:text variant="subtle" class="text-xs">
                                        {{ $invitation->created_at->format('d/m/Y H:i') }}
                                    </flux:text>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:text variant="subtle" class="text-xs">
                                        {{ $invitation->expires_at->format('d/m/Y H:i') }}
                                    </flux:text>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" square />

                                        <flux:menu>
                                            @can('resend', $invitation)
                                                @if (!$invitation->isRegistered() && !$invitation->isExpired())
                                                    <flux:menu.item icon="arrow-path"
                                                        wire:click="resendInvitation({{ $invitation->id }})">
                                                        {{ __('Renvoyer') }}
                                                    </flux:menu.item>
                                                @endif
                                            @endcan

                                            @can('delete', $invitation)
                                                @if (!$invitation->isRegistered())
                                                    <flux:menu.separator />
                                                    <flux:menu.item variant="danger" icon="trash"
                                                        wire:click="deleteInvitation({{ $invitation->id }})"
                                                        wire:confirm="Êtes-vous sûr de vouloir supprimer cette invitation ?">
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

            <div class="mt-4">
                {{ $this->invitations->links() }}
            </div>
        @endif
    </div>

    <livewire:admin.users.modals.invite-user />
</x-layouts.content>