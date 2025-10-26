<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;



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

});
