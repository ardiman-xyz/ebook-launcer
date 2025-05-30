<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EbookController extends Controller
{
    public function launch(Request $request)
    {
        try {
            // Validate aktivasi masih valid
            if (!$this->isActivationValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aktivasi tidak valid'
                ], 403);
            }
            
            // Generate session key untuk tracking
            $sessionKey = $this->generateSessionKey();
            
            // Create temp directory untuk decrypted content
            $tempDir = $this->createTempDirectory();
            
            // Copy dan modifikasi ebook content
            $this->prepareEbookContent($tempDir, $sessionKey);
            
            // Launch ebook di browser
            $this->launchEbookInBrowser($tempDir);
            
            // Start monitoring process
            $this->startProcessMonitoring($tempDir);
            
            return response()->json([
                'success' => true,
                'message' => 'E-book berhasil dibuka di browser',
                'temp_dir' => $tempDir,
                'session_key' => $sessionKey
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function launcherCheck()
    {
        // API endpoint untuk ebook viewer check launcher masih hidup
        return response()->json([
            'status' => 'alive',
            'timestamp' => time(),
            'message' => 'Launcher is running'
        ]);
    }
    
    private function prepareEbookContent($tempDir, $sessionKey)
    {
        // Path ke flipbook hasil generate PDF (di folder out3)
        $flipbookSourceDir = storage_path('app/flipbook/out3');
        $flipbookIndexPath = $flipbookSourceDir . '/index.html';
        
        if (!file_exists($flipbookIndexPath)) {
            throw new \Exception('File flipbook tidak ditemukan di: ' . $flipbookIndexPath);
        }
        
        // Copy seluruh directory out3 ke temp directory
        $this->copyFlipbookDirectory($flipbookSourceDir, $tempDir);
        
        // Baca dan modifikasi file index.html untuk keamanan
        $indexFile = $tempDir . '/index.html';
        $originalContent = file_get_contents($indexFile);
        
        // Inject minimal security monitoring
        $securityScript = $this->getBasicSecurityScript($sessionKey);
        
        // Inject script sebelum </body>
        if (strpos($originalContent, '</body>') !== false) {
            $originalContent = str_replace('</body>', $securityScript . '</body>', $originalContent);
        } else {
            $originalContent .= $securityScript;
        }
        
        // Tulis kembali file yang sudah dimodifikasi
        file_put_contents($indexFile, $originalContent);
        
        $this->logActivity('E-book content prepared at: ' . $indexFile);
    }
    
    private function getBasicSecurityScript($sessionKey)
    {
        return '
        <!-- Security Monitor -->
        <div id="launcher-status" style="position: fixed; top: 10px; right: 10px; background: rgba(40, 167, 69, 0.9); color: white; padding: 8px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; z-index: 9999; font-family: Arial, sans-serif;">
            📚 E-book Protected
        </div>
        
        <div style="position: fixed; bottom: 10px; right: 10px; background: rgba(0, 0, 0, 0.7); color: #fff; padding: 5px 10px; border-radius: 10px; font-size: 10px; z-index: 9998; font-family: monospace; opacity: 0.7;">
            Session: ' . substr($sessionKey, 0, 8) . '...<br>
            Host: ' . gethostname() . '<br>
            Time: ' . date('H:i:s') . '
        </div>
        
        <script>
            // Simplified security dengan passive monitoring
            let isLauncherActive = true;
            let statusElement = document.getElementById("launcher-status");
            
            // Function untuk check launcher (optional, tidak paksa)
            function checkLauncherStatus() {
                // Coba beberapa kemungkinan URL launcher
                const urls = [
                    "http://127.0.0.1:8000/launcher-check",
                    "http://localhost:8000/launcher-check",
                    "http://127.0.0.1:8080/launcher-check"
                ];
                
                Promise.any(urls.map(url => 
                    fetch(url, { 
                        method: "GET",
                        signal: AbortSignal.timeout(5000) // 5 second timeout
                    })
                ))
                .then(response => {
                    if (response.ok) {
                        isLauncherActive = true;
                        if (statusElement) {
                            statusElement.textContent = "🔗 Launcher Connected";
                            statusElement.style.background = "rgba(40, 167, 69, 0.9)";
                        }
                        console.log("✅ Launcher connection verified");
                    }
                })
                .catch(error => {
                    // Jangan langsung tutup, hanya update status
                    isLauncherActive = false;
                    if (statusElement) {
                        statusElement.textContent = "⚠️ Launcher Check Failed";
                        statusElement.style.background = "rgba(255, 193, 7, 0.9)";
                    }
                    console.log("⚠️ Launcher check failed (not critical):", error.message);
                });
            }
            
            // Warning ketika user coba close tab/window
            window.addEventListener("beforeunload", function(e) {
                if (isLauncherActive) {
                    e.preventDefault();
                    e.returnValue = "E-book masih aktif. Yakin ingin keluar?";
                    return e.returnValue;
                }
            });
            
            // Minimal security - disable beberapa shortcuts saja
            document.addEventListener("keydown", function(e) {
                // Hanya disable F12 untuk mencegah inspect element casual
                if (e.keyCode === 123) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Optional: Periodic check (tidak agresif)
            setTimeout(() => {
                checkLauncherStatus(); // Check setelah 10 detik
                
                // Set interval check yang tidak agresif (setiap 2 menit)
                setInterval(checkLauncherStatus, 120000);
            }, 10000);
            
            console.log("📚 E-book Session: ' . substr($sessionKey, 0, 16) . '");
            console.log("🔒 Simplified protection loaded");
            console.log("💡 Tip: Keep the launcher app open for best experience");
        </script>';
    }
    
    private function launchEbookInBrowser($tempDir)
    {
        // Path ke file index.html di temp directory
        $indexFile = $tempDir . '/index.html';
        
        // Pastikan file ada
        if (!file_exists($indexFile)) {
            throw new \Exception('E-book file not found: ' . $indexFile);
        }
        
        // Buka di browser default sesuai OS
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows
            exec("start \"\" \"$indexFile\"");
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            // macOS
            exec("open \"$indexFile\"");
        } else {
            // Linux
            exec("xdg-open \"$indexFile\"");
        }
        
        $this->logActivity('E-book launched in browser: ' . $indexFile);
    }
    
    private function copyFlipbookDirectory($sourceDir, $destDir)
    {
        // Buat directory tujuan jika belum ada
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        
        // Scan semua file dan folder di source
        $files = scandir($sourceDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $file;
            $destPath = $destDir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($sourcePath)) {
                // Recursively copy subdirectory (files/, mobile/, dll)
                $this->copyFlipbookDirectory($sourcePath, $destPath);
            } else {
                // Copy file (index.html, shot.png, dll)
                copy($sourcePath, $destPath);
            }
        }
        
        $this->logActivity('Flipbook copied from: ' . $sourceDir . ' to: ' . $destDir);
    }
    
    private function isActivationValid()
    {
        if (!Storage::exists('activation.json')) {
            return false;
        }
        
        $activation = json_decode(Storage::get('activation.json'), true);
        $currentFingerprint = $this->getDeviceFingerprint();
        
        return $activation['device_fingerprint'] === $currentFingerprint;
    }
    
    private function generateSessionKey()
    {
        return bin2hex(random_bytes(32));
    }
    
    private function createTempDirectory()
    {
        $tempDir = storage_path('app/temp/ebook_' . time() . '_' . rand(1000, 9999));
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        return $tempDir;
    }
    
    private function startProcessMonitoring($tempDir)
    {
        // Set timeout untuk auto-cleanup (2 jam)
        $cleanupTime = time() + (2 * 60 * 60);
        file_put_contents($tempDir . '/.cleanup_time', $cleanupTime);
        
        $this->logActivity('Process monitoring started for: ' . $tempDir);
    }
    
    private function cleanupTempDirectory($tempDir)
    {
        if (is_dir($tempDir)) {
            $this->deleteDirectory($tempDir);
            $this->logActivity('Temp directory cleaned: ' . $tempDir);
        }
    }
    
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
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
    
    private function logActivity($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        
        $logDir = storage_path('logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents(storage_path('logs/ebook-activity.log'), $logEntry, FILE_APPEND);
    }
}