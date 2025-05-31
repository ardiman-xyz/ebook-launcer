<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EbookController;
use App\Http\Controllers\EmbedEbookController;
use App\Http\Controllers\InternalEbookController;
use App\Http\Controllers\SimpleEmbedController;
use Illuminate\Support\Facades\Log;

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
    
    // TRIPLE LAUNCH OPTIONS
    Route::post('/launch-internal-ebook', [InternalEbookController::class, 'launch'])->name('internal.ebook.launch');
    Route::post('/launch-embed-ebook', [EmbedEbookController::class, 'launch'])->name('embed.ebook.launch');
    Route::post('/launch-ebook', [EbookController::class, 'launch'])->name('ebook.launch');
    
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::get('/license-info', [DashboardController::class, 'licenseInfo'])->name('license.info');
    Route::post('/launch-embed', [SimpleEmbedController::class, 'launch'])->name('embed.launch');
    
    // Tambah di luar middleware (untuk akses langsung):
    Route::get('/embed-viewer', [SimpleEmbedController::class, 'view'])->name('embed.viewer');
    Route::get('/embed-content', [SimpleEmbedController::class, 'content'])->name('embed.content');
    Route::get('/embed-asset/{path}', [SimpleEmbedController::class, 'asset'])->name('embed.asset')->where('path', '.*');

    Route::get('/embed-index', [SimpleEmbedController::class, 'index'])->name('embed.index');

});
