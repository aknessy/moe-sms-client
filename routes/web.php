<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', [\App\Http\Controllers\LoginController::class, 'index']);
Route::post('/login', [\App\Http\Controllers\LoginController::class, 'login']);

Route::prefix('dashboard')->group(function(){
    Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index']);
    Route::get('/profile-settings', [\App\Http\Controllers\ProfileSettingsController::class, 'index'])->name('profile-settings');
    Route::get('/change-password', [\App\Http\Controllers\ProfileSettingsController::class, 'changePassword'])->name('change-password');
});

