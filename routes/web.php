<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EbookController;

// Main route - cek aktivasi
Route::get('/', function () {
    if (Storage::exists('activation.json')) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('activation');
});

// Activation routes
Route::get('/activation', [ActivationController::class, 'index'])->name('activation');
Route::post('/activation', [ActivationController::class, 'activate'])->name('activate');

// Dashboard routes (protected)
Route::middleware(['activation'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/launch-ebook', [EbookController::class, 'launch'])->name('ebook.launch');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::get('/license-info', [DashboardController::class, 'licenseInfo'])->name('license.info');
});

// API untuk ebook viewer
Route::get('/launcher-check', [EbookController::class, 'launcherCheck'])->name('ebook.check');