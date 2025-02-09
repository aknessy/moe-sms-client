<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', [\App\Http\Controllers\LoginController::class, 'index']);
Route::post('/login', [\App\Http\Controllers\LoginController::class, 'login']);

Route::prefix('dashboard')->group(function(){
    Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index']);
    Route::get('/profile-settings', [\App\Http\Controllers\ProfileSettingsController::class, 'index'])->name('profile-settings');
    Route::get('/change-password', [\App\Http\Controllers\ProfileSettingsController::class, 'changePassword'])->name('change-password');

    Route::prefix('staff')->group(function(){
        Route::get('/', [\App\Http\Controllers\StaffController::class, 'index']);
        Route::get('/basic-info', [\App\Http\Controllers\StaffController::class, 'index'])->name('basic-info');
        Route::get('/edu-progress', [\App\Http\Controllers\StaffController::class, 'edu_progress'])->name('edu-progress');
        Route::get('/promotion', [\App\Http\Controllers\StaffController::class, 'promotions'])->name('promotion');
        Route::get('/leave-record', [\App\Http\Controllers\StaffController::class, 'leave'])->name('leave-record');
        Route::get('/referee', [\App\Http\Controllers\StaffController::class, 'referee'])->name('referee');
        Route::get('/next-of-kin', [\App\Http\Controllers\StaffController::class, 'next_of_kin'])->name('next-of-kin');
        Route::get('/career', [\App\Http\Controllers\StaffController::class, 'career'])->name('career');

        Route::post('/create',[\App\Http\Controllers\StaffController::class, 'create'])->name('create');
    });

    Route::get('/config', [\App\Http\Controllers\ConfigurationController::class, 'index']);
});

