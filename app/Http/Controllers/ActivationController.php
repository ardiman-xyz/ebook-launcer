<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ActivationController extends Controller
{
    public function index()
    {
        // Cek apakah sudah pernah aktivasi
        if (Storage::exists('activation.json')) {
            return redirect()->route('dashboard');
        }
        
        return view('activation.index');
    }
    
    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string'
        ]);
        
        $licenseKey = $request->license_key;
        $deviceFingerprint = $this->getDeviceFingerprint();
        
        // Simulasi validasi ke server (nanti kita ganti dengan real server)
        $isValid = $this->validateLicense($licenseKey, $deviceFingerprint);
        
        if ($isValid) {
            // Simpan aktivasi
            $activationData = [
                'license_key' => $licenseKey,
                'device_fingerprint' => $deviceFingerprint,
                'activation_token' => $this->generateToken(),
                'activated_at' => now()->toISOString()
            ];
            
            Storage::put('activation.json', json_encode($activationData));
            
            return redirect()->route('dashboard')
                ->with('success', 'Aktivasi berhasil!');
        }
        
        return back()
            ->withErrors(['license_key' => 'License key tidak valid atau sudah digunakan.'])
            ->withInput();
    }
    
    private function getDeviceFingerprint()
    {
        // Generate device fingerprint dari berbagai sumber
        $systemInfo = [
            'hostname' => gethostname(),
            'os' => PHP_OS,
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'user' => get_current_user(),
        ];
        
        return hash('sha256', json_encode($systemInfo));
    }
    
    private function validateLicense($licenseKey, $deviceFingerprint)
    {
        // Untuk testing, kita anggap license valid jika:
        // 1. License key tidak kosong
        // 2. Formatnya seperti XXXX-XXXX-XXXX-XXXX
        
        if (preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $licenseKey)) {
            return true;
        }
        
        // Nanti akan diganti dengan HTTP request ke server:
        /*
        try {
            $response = Http::post('https://your-server.com/api/validate-license', [
                'license_key' => $licenseKey,
                'device_fingerprint' => $deviceFingerprint
            ]);
            
            return $response->successful() && $response->json()['status'] === 'valid';
        } catch (Exception $e) {
            return false;
        }
        */
        
        return false;
    }
    
    private function generateToken()
    {
        return bin2hex(random_bytes(32));
    }
}