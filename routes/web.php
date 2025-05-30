<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EbookController;
use App\Http\Controllers\EmbedEbookController;
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
    
    // DUAL LAUNCH OPTIONS
    Route::post('/launch-embed-ebook', [EmbedEbookController::class, 'launch'])->name('embed.ebook.launch');
    Route::post('/launch-ebook', [EbookController::class, 'launch'])->name('ebook.launch');
    
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::get('/license-info', [DashboardController::class, 'licenseInfo'])->name('license.info');
});

// Embedded E-book routes (tanpa middleware untuk direct access)
Route::get('/embed-ebook/view', [EmbedEbookController::class, 'view'])->name('embed.ebook.view');
Route::get('/embed-ebook/iframe', [EmbedEbookController::class, 'iframe'])->name('embed.ebook.iframe');
Route::post('/embed-ebook/close', [EmbedEbookController::class, 'close'])->name('embed.ebook.close');

// External E-book routes (untuk cleanup)
Route::middleware(['activation'])->group(function () {
    Route::post('/ebook/close', [EbookController::class, 'closeEbook'])->name('ebook.close');
    Route::post('/ebook/cleanup', [EbookController::class, 'cleanupOldSessions'])->name('ebook.cleanup');
});

// API untuk ebook viewers (tanpa middleware)
Route::get('/launcher-check', [EbookController::class, 'launcherCheck'])->name('ebook.check');

// Assets untuk embedded ebook (tanpa middleware)
Route::get('/embed-ebook/assets', function () {
    $tempDir = request()->query('temp_dir');
    $sessionKey = request()->query('session_key');
    $path = request()->query('path');
    
    // Log untuk debugging
    Log::info('Asset request:', [
        'temp_dir' => $tempDir,
        'session_key' => substr($sessionKey, 0, 8) . '...',
        'path' => $path
    ]);
    
    if (!$tempDir || !$sessionKey || !$path) {
        abort(403, 'Invalid access - missing parameters');
    }
    
    // Validate session key format
    if (!$sessionKey || strlen($sessionKey) !== 64 || !ctype_xdigit($sessionKey)) {
        abort(403, 'Invalid session key');
    }
    
    // Decode path (karena di-encode di URL)
    $decodedPath = urldecode($path);
    $filePath = $tempDir . '/' . $decodedPath;
    
  
    
    if (!file_exists($filePath)) {
        abort(404, 'Asset not found: ' . $decodedPath);
    }
    
    if (!is_file($filePath)) {
        abort(404, 'Invalid asset path');
    }
    
    // Security check - pastikan file masih dalam temp directory
    $realTempDir = realpath($tempDir);
    $realFilePath = realpath($filePath);
    
    if (!$realFilePath || !$realTempDir || strpos($realFilePath, $realTempDir) !== 0) {
          
        abort(403, 'Access denied - path traversal detected');
    }
    
    // Determine content type
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript', 
        'html' => 'text/html',
        'json' => 'application/json',
        'xml' => 'text/xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'pdf' => 'application/pdf'
    ];
    
    $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
    
    return response()->file($filePath, [
        'Content-Type' => $contentType,
        'Cache-Control' => 'private, max-age=3600',
        'X-Content-Type-Options' => 'nosniff'
    ]);
})->name('embed.ebook.assets');