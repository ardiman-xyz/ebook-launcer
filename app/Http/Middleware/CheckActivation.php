<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CheckActivation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah file aktivasi ada
        if (!Storage::exists('activation.json')) {
            return redirect()->route('activation')
                ->with('error', 'Anda harus aktivasi terlebih dahulu');
        }

        // Load activation data
        $activationData = json_decode(Storage::get('activation.json'), true);
        
        // Cek apakah device fingerprint masih sama
        $currentFingerprint = $this->getDeviceFingerprint();
        
        if ($activationData['device_fingerprint'] !== $currentFingerprint) {
            // Device berubah, hapus aktivasi lama
            Storage::delete('activation.json');
            
            return redirect()->route('activation')
                ->with('error', 'Device berubah atau tidak valid. Silakan aktivasi ulang.');
        }

        // Cek apakah aktivasi expired (optional - 30 hari)
        $activatedAt = strtotime($activationData['activated_at']);
        $expiryDays = 30; // 30 hari
        $expiryTime = $activatedAt + ($expiryDays * 24 * 60 * 60);
        
        if (time() > $expiryTime) {
            Storage::delete('activation.json');
            
            return redirect()->route('activation')
                ->with('error', 'Aktivasi telah kedaluwarsa. Silakan aktivasi ulang.');
        }

        return $next($request);
    }

    private function getDeviceFingerprint()
    {
        $systemInfo = [
            'hostname' => gethostname(),
            'os' => PHP_OS,
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'user' => get_current_user(),
        ];
        
        return hash('sha256', json_encode($systemInfo));
    }
}