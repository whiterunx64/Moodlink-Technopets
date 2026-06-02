<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PostManagementController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('Welcome', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
    'laravelVersion' => Application::VERSION,
    'phpVersion' => PHP_VERSION,
]));

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::get('/post-management', [PostManagementController::class, 'index'])
    ->name('post-management.index');
Route::get('/post-management/section/{section}', [PostManagementController::class, 'filterBySection'])
    ->name('post-management.section');
Route::get('/post-management/mood/{mood}', [PostManagementController::class, 'filterByMood'])
    ->name('post-management.mood');
Route::patch('/post-management/{post}/toggle-status', [PostManagementController::class, 'toggleFlag'])
    ->name('post-management.toggle-status');
Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
