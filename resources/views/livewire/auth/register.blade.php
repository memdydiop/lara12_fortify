<?php

use App\Models\Invitation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Livewire\Volt\Component;

new class extends Component {
    public ?Invitation $invitation = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token = null): void
    {
        if ($token) {
            $this->invitation = Invitation::where('token', $token)->first();

            if (!$this->invitation || $this->invitation->isRegistered() || $this->invitation->isExpired()) {
                abort(404);
            }

            $this->email = $this->invitation->email;
        }
    }

    public function register(CreatesNewUsers $creator): void
    {
        // Rediriger si on essaie de s'inscrire sans invitation et que le système est fermé
        if (is_null($this->invitation)) {
            $this->redirectRoute('login');
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($this->email !== $this->invitation->email) {
            $this->addError('email', 'The provided email does not match the invitation.');
            return;
        }

        $user = $creator->create($this->all());

        event(new Registered($user));

        Auth::login($user);

        $this->invitation->update(['registered_at' => now()]);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<x-layouts.auth.split>
    <x-slot:image>
        <x-placeholder-pattern />
    </x-slot:image>

    <x-auth-header title="Create an account" description="Complete your profile to get started" />

    <form wire:submit="register" class="mt-8 grid gap-y-6">
        <flux:input wire:model="name" type="text" label="Full name" placeholder="Enter your full name" required
            autofocus />
        <flux:input wire:model="email" type="email" label="Email address" placeholder="Enter your email address"
            required readonly />
        <flux:input wire:model="password" type="password" label="Password" placeholder="Enter a password" required />
        <flux:input wire:model="password_confirmation" type="password" label="Confirm password"
            placeholder="Confirm your password" required />

        <button type="submit"
            class="flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
            Create Account
        </button>
    </form>

    <p class="mt-8 text-sm text-center text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-primary-600 hover:text-primary-500">Sign
            in</a>
    </p>
</x-layouts.auth.split>