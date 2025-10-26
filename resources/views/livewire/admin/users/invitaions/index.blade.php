<?php

use App\Actions\Invitations\CreateInvitation;
use App\Actions\Invitations\ResendInvitation;
use App\Models\Invitation;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $email = '';

    public function with(): array
    {
        return [
            'invitations' => Invitation::with('sentBy')->latest()->paginate(10),
        ];
    }

    public function send(CreateInvitation $action): void
    {
        $validated = $this->validate(['email' => 'required|email']);
        $action->handle($validated['email']);

        $this->reset('email');
        session()->flash('status', 'Invitation sent successfully.');
    }

    public function resend(Invitation $invitation, ResendInvitation $action): void
    {
        $this->authorize('resend', $invitation);
        $action->handle($invitation);
        session()->flash('status', 'Invitation re-sent successfully.');
    }

    public function delete(Invitation $invitation): void
    {
        $this->authorize('delete', $invitation);
        $invitation->delete();
        session()->flash('status', 'Invitation deleted.');
    }
}; ?>

<x-layouts.content heading="Invitations" subheading="Send and manage user invitations">
    <div class="space-y-8">
        <div class="border-b border-gray-200 pb-5 sm:flex sm:items-center sm:justify-between">
            <h3 class="text-base font-semibold leading-6 text-gray-900">Send a new invitation</h3>
        </div>

        @if (session('status'))
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.06 0l4.001-5.5z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('status') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <form wire:submit="send" class="flex items-start gap-4">
            <div class="w-full">
                <label for="email" class="sr-only">Email</label>
                <input type="email" wire:model="email" id="email"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                    placeholder="you@example.com">
                @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit"
                class="rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                <span wire:loading.remove wire:target="send">Send Invitation</span>
                <span wire:loading wire:target="send">Sending...</span>
            </button>
        </form>

        <div class="border-b border-gray-200 pb-5 sm:flex sm:items-center sm:justify-between">
            <h3 class="text-base font-semibold leading-6 text-gray-900">Sent Invitations</h3>
        </div>

        <table class="min-w-full divide-y divide-gray-300">
            <thead>
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Email
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Expires At</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($invitations as $invitation)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                            {{ $invitation->email }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            @if ($invitation->isRegistered())
                                <span
                                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Accepted</span>
                            @elseif ($invitation->isExpired())
                                <span
                                    class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Expired</span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Pending</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $invitation->expires_at->format('M d, Y') }}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            @can('resend', $invitation)
                                <button wire:click="resend({{ $invitation->id }})"
                                    class="text-primary-600 hover:text-primary-900">Resend</button>
                            @endcan
                            @can('delete', $invitation)
                                <button wire:click="delete({{ $invitation->id }})"
                                    class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">No invitations
                            sent yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $invitations->links() }}
        </div>
    </div>
</x-layouts.content>