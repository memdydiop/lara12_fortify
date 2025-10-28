<?php
// [file name]: profile.blade.php
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $user;
    public $name;
    public $email;
    public $date_of_birth;
    public $phone;
    public $address;
    public $city;
    public $country;
    public $avatar;
    
    // Champs pour le changement de mot de passe
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->date_of_birth = $this->user->date_of_birth?->format('Y-m-d');
        $this->phone = $this->user->phone;
        $this->address = $this->user->address;
        $this->city = $this->user->city;
        $this->country = $this->user->country;
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'image', 'max:2048'], // 2MB max
        ]);

        // Gestion de l'avatar
        if ($this->avatar) {
            // Supprimer l'ancien avatar s'il existe
            if ($this->user->avatar) {
                Storage::disk('public')->delete($this->user->avatar);
            }
            
            // Stocker le nouvel avatar
            $avatarPath = $this->avatar->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $this->user->update($validated);

        // Réinitialiser l'upload
        $this->avatar = null;

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Profil mis à jour avec succès.'
        ]);
    }

    public function removeAvatar(): void
    {
        if ($this->user->avatar) {
            Storage::disk('public')->delete($this->user->avatar);
            $this->user->update(['avatar' => null]);
        }

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Photo de profil supprimée.'
        ]);
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $this->user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Mot de passe mis à jour avec succès.'
        ]);
    }

    public function getRolesProperty()
    {
        return $this->user->getRoleNames();
    }

    public function getPermissionsProperty()
    {
        return $this->user->getAllPermissions()->pluck('name');
    }
}; ?>

<x-layouts.content :heading="__('Mon Profil')" :subheading="__('Gérez vos informations personnelles et votre compte')">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Colonne de gauche --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Informations personnelles --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="lg" class="mb-6">{{ __('Informations personnelles') }}</flux:heading>
                
                <form wire:submit="updateProfile" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Nom --}}
                        <div>
                            <flux:input 
                                :label="__('Nom complet')"
                                wire:model="name" 
                                id="name"
                                placeholder="Votre nom complet"
                                required
                            />
                        </div>

                        {{-- Email (non modifiable) --}}
                        <div>
                            <flux:input 
                                :label="__('Adresse email')"
                                value="{{ $user->email }}"
                                id="email"
                                type="email"
                                disabled
                                class="bg-zinc-50 dark:bg-zinc-700 cursor-not-allowed"
                            />
                        </div>

                        {{-- Date de naissance --}}
                        <div>
                            <flux:input 
                                :label="__('Date de naissance')"
                                wire:model="date_of_birth" 
                                id="date_of_birth"
                                type="date"
                            />
                        </div>

                        {{-- Téléphone --}}
                        <div>
                            <flux:input 
                                :label="__('Téléphone')"
                                wire:model="phone" 
                                id="phone"
                                placeholder="+225 01 23 45 67 89"
                            />
                        </div>

                        {{-- Ville --}}
                        <div>
                            <flux:input 
                                :label="__('Ville')"
                                wire:model="city" 
                                id="city"
                                placeholder="Abidjan"
                            />
                        </div>

                        {{-- Pays --}}
                        <div>
                            <flux:input 
                                :label="__('Pays')"
                                wire:model="country" 
                                id="country"
                                placeholder="Côte d'Ivoire"
                            />
                        </div>

                        {{-- Adresse --}}
                        <div class="md:col-span-2">
                            <flux:input 
                                :label="__('Adresse')"
                                wire:model="address" 
                                id="address"
                                placeholder="123 Rue Example, Cocody"
                            />
                        </div>

                        {{-- Photo de profil --}}
                        <div class="md:col-span-2">
                            <flux:label for="avatar">{{ __('Photo de profil') }}</flux:label>
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0">
                                    {{-- Aperçu qui remplace l'avatar --}}
                                    @if($avatar)
                                        {{-- Aperçu de la nouvelle image --}}
                                        <div class="relative">
                                            <img src="{{ $avatar->temporaryUrl() }}" class="size-16 mask mask-squircle object-cover">
                                        </div>
                                    @elseif($user->avatar)
                                        {{-- Avatar actuel --}}
                                        <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" 
                                             class="size-16 mask mask-squircle object-cover ">
                                    @else
                                        {{-- Avatar par défaut --}}
                                        <div class="size-16 bg-gradient-to-br from-blue-500 to-purple-600 mask mask-squircle flex items-center justify-center text-white font-bold text-lg">
                                            {{ $user->initials() }}
                                        </div>
                                    @endif
                                </div>
                                @if($user->avatar && !$avatar)
                                    <div>
                                        <flux:button variant="danger" size="sm" wire:click="removeAvatar" type="button">
                                            <flux:icon.trash class="size-4" />
                                        </flux:button>
                                    </div>
                                    @else
                                <div class="flex-1">
                                    <input 
                                        type="file" 
                                        wire:model="avatar" 
                                        id="avatar"
                                        accept="image/*"
                                        class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300"
                                    />
                                    @error('avatar')
                                        <flux:text variant="error" class="mt-1">{{ $message }}</flux:text>
                                    @enderror
                                    <flux:text variant="subtle" class="text-xs mt-1">
                                        {{ __('Formats supportés : JPG, PNG, GIF. Taille max : 2MB') }}
                                    </flux:text>
                                </div>
                                @endif
                            </div>
                            
                           
                        </div>
                    </div>

                    {{-- Bouton de sauvegarde --}}
                    <div class="flex justify-end pt-4">
                        <flux:button type="submit" variant="primary" icon="check">
                            {{ __('Enregistrer les modifications') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            {{-- Changement de mot de passe --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="lg" class="mb-6">{{ __('Changer le mot de passe') }}</flux:heading>
                
                <form wire:submit="updatePassword" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4">
                        {{-- Mot de passe actuel --}}
                        <div>
                            <flux:label for="current_password">{{ __('Mot de passe actuel') }}</flux:label>
                            <flux:input 
                                wire:model="current_password" 
                                id="current_password"
                                type="password"
                                required
                            />
                            @error('current_password')
                                <flux:text variant="error" class="mt-1">{{ $message }}</flux:text>
                            @enderror
                        </div>

                        {{-- Nouveau mot de passe --}}
                        <div>
                            <flux:label for="new_password">{{ __('Nouveau mot de passe') }}</flux:label>
                            <flux:input 
                                wire:model="new_password" 
                                id="new_password"
                                type="password"
                                required
                            />
                            @error('new_password')
                                <flux:text variant="error" class="mt-1">{{ $message }}</flux:text>
                            @enderror
                        </div>

                        {{-- Confirmation --}}
                        <div>
                            <flux:label for="new_password_confirmation">{{ __('Confirmer le nouveau mot de passe') }}</flux:label>
                            <flux:input 
                                wire:model="new_password_confirmation" 
                                id="new_password_confirmation"
                                type="password"
                                required
                            />
                        </div>
                    </div>

                    {{-- Bouton de sauvegarde --}}
                    <div class="flex justify-end pt-4">
                        <flux:button type="submit" variant="primary" icon="lock-closed">
                            {{ __('Changer le mot de passe') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Colonne de droite --}}
        <div class="space-y-6">
            {{-- Avatar et informations rapides --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 text-center">
                {{-- Avatar avec aperçu --}}
                <div class="relative inline-block">
                    @if($avatar)
                        {{-- Aperçu de la nouvelle image --}}
                        <img src="{{ $avatar->temporaryUrl() }}" alt="Aperçu" class="size-24 mask mask-squircle mx-auto mb-4 object-cover">
                        
                    @elseif($user->avatar)
                        {{-- Avatar actuel --}}
                        <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" 
                             class="size-24 mask mask-squircle mx-auto mb-4 object-cover">
                    @else
                        {{-- Avatar par défaut --}}
                        <div class="size-24 bg-gradient-to-br from-blue-500 to-purple-600 mask mask-squircle mx-auto mb-4 flex items-center justify-center text-white text-2xl font-bold">
                            {{ $user->initials() }}
                        </div>
                    @endif
                </div>
                
                <flux:heading size="lg">{{ $user->name }}</flux:heading>
                <flux:text variant="subtle" class="mb-4">{{ $user->email }}</flux:text>
                
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Membre depuis :</span>
                        <span>{{ $user->created_at->diffForHumans() }}</span>
                    </div>
                    @if($user->email_verified_at)
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Email vérifié :</span>
                            <flux:badge color="green" size="sm">Oui</flux:badge>
                        </div>
                    @else
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Email vérifié :</span>
                            <flux:badge color="red" size="sm">Non</flux:badge>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Rôles et permissions --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="lg" class="mb-4">{{ __('Rôles et Permissions') }}</flux:heading>
                
                {{-- Rôles --}}
                <div class="mb-4">
                    <flux:heading size="sm" class="mb-2">{{ __('Rôles') }}</flux:heading>
                    <div class="space-y-1">
                        @forelse($this->roles as $role)
                            <flux:badge variant="pill" color="blue" size="sm" class="capitalize">
                                {{ $role }}
                            </flux:badge>
                        @empty
                            <flux:text variant="subtle" class="text-sm">{{ __('Aucun rôle assigné') }}</flux:text>
                        @endforelse
                    </div>
                </div>

                {{-- Permissions --}}
                <div>
                    <flux:heading size="sm" class="mb-2">{{ __('Permissions') }}</flux:heading>
                    <div class="space-y-1 max-h-32 overflow-y-auto">
                        @forelse($this->permissions as $permission)
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 px-2 py-1 bg-zinc-50 dark:bg-zinc-700 rounded">
                                {{ $permission }}
                            </div>
                        @empty
                            <flux:text variant="subtle" class="text-sm">{{ __('Aucune permission spécifique') }}</flux:text>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Actions rapides --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="lg" class="mb-4">{{ __('Actions') }}</flux:heading>
                <div class="space-y-2">
                    <flux:button variant="ghost" icon="arrow-left-start-on-rectangle" class="w-full justify-start">
                        {{ __('Journal d\'activité') }}
                    </flux:button>
                    <flux:button variant="ghost" icon="cog-6-tooth" class="w-full justify-start">
                        {{ __('Paramètres') }}
                    </flux:button>
                    <flux:button variant="ghost" icon="question-mark-circle" class="w-full justify-start">
                        {{ __('Aide et support') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.content>