<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', [\App\Http\Controllers\LoginController::class, 'index']);
Route::post('/login', [\App\Http\Controllers\LoginController::class, 'login']);

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);
