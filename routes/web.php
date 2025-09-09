<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GdacsController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return view('livewire.pages.dashboard');
})->name('home');

Route::get('/dashboard', function () {
    return view('livewire.pages.dashboard');
})->name('dashboard');



Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::get('settings/password', [SettingsController::class, 'password'])->name('settings.password');
    Route::get('settings/appearance', [SettingsController::class, 'appearance'])->name('settings.appearance');
});

require __DIR__.'/auth.php';
