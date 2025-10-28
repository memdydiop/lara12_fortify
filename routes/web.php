<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/welcome', function () {
    return view('welcome');
})->name('home');

// ===================================================================
// AJOUTEZ CE BLOC POUR LES ROUTES D'INSCRIPTION ET DE CONNEXION
// ===================================================================
Route::middleware('guest')->group(function () {
    // La ligne qui corrige votre problème :
    Volt::route('register', 'auth.register')->name('register');
});
// ===================================================================

Route::middleware(['auth', 'verified'])->group(function () {

    Volt::route('','dashboard')->name('dashboard');

    Route::prefix('settings')->group(function () {      
        Route::redirect('', 'settings/profile');
        Volt::route('profile','settings.profile')->name('profile.edit');
        Volt::route('password','settings.password')->name('user-password.edit');
        Volt::route('appearance','settings.appearance')->name('appearance.edit');
        Volt::route('two-factor','settings.two-factor')->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
    });

    // Routes d'administration des utilisateurs
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('can:view invitations')->group(function () {
            Volt::route('users', 'admin.users.index')->name('users');
            Volt::route('users/invitations', 'admin.users.invitations')->name('users.invitations');
        });
        
        Volt::route('roles', 'admin.roles.index')
            ->middleware('can:view roles')
            ->name('roles');
    });

});
