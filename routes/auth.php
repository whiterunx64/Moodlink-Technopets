<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;


Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::post('login/json', [AuthenticatedSessionController::class, 'login'])
        ->name('login.json');
});

Route::middleware(['supabase.auth', 'supabase.token'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('auth/user', [AuthenticatedSessionController::class, 'user'])
        ->name('auth.user');

    Route::post('auth/refresh', [AuthenticatedSessionController::class, 'refresh'])
        ->name('auth.refresh');
});