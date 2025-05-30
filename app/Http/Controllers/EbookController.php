<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;

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
            
            // Generate session key untuk decrypt
            $sessionKey = $this->generateSessionKey();
            
            // Create temp directory untuk decrypted content
            $tempDir = $this->createTempDirectory();
            
            // Decrypt ebook content (simulasi - nanti kita implement)
            $this->decryptEbookContent($tempDir, $sessionKey);
            
            // Launch ebook viewer (simulasi - nanti kita implement)
            $this->launchEbookViewer($tempDir);
            
            // Start monitoring process
            $this->startProcessMonitoring($tempDir);
            
            return response()->json([
                'success' => true,
                'message' => 'E-book berhasil dibuka',
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
    
    private function decryptEbookContent($tempDir, $sessionKey)
    {
        // Baca file flipbook asli
        $flipbookPath = storage_path('app/flipbook/index.html');
        
        if (!file_exists($flipbookPath)) {
            throw new \Exception('File flipbook tidak ditemukan di: ' . $flipbookPath);
        }
        
        // Baca content flipbook asli
        $flipbookContent = file_get_contents($flipbookPath);
        
        // Tambah monitoring script
        $monitoringScript = '
        <script>
        setInterval(() => {
            fetch("http://127.0.0.1:8101/launcher-check")
                .catch(() => {
                    alert("⚠️ Launcher connection lost!");
                    window.close();
                });
        }, 30000);
        </script>';
        
        // Inject monitoring sebelum </body>
        if (strpos($flipbookContent, '</body>') !== false) {
            $flipbookContent = str_replace('</body>', $monitoringScript . '</body>', $flipbookContent);
        } else {
            $flipbookContent .= $monitoringScript;
        }
        
        // Tulis ke temp directory
        $indexFile = $tempDir . '/index.html';
        file_put_contents($indexFile, $flipbookContent);
        
        $this->logActivity('FlipBook content loaded to: ' . $indexFile);
    }
    
    private function createSampleAssets($tempDir)
    {
        // Create assets folder
        $assetsDir = $tempDir . '/assets';
        if (!is_dir($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }
        
        // Create sample CSS file
        $css = '
        .flipbook-enhanced {
            background: linear-gradient(45deg, #667eea, #764ba2);
            animation: gradientShift 5s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background: linear-gradient(45deg, #667eea, #764ba2); }
            50% { background: linear-gradient(45deg, #764ba2, #667eea); }
        }
        ';
        file_put_contents($assetsDir . '/style.css', $css);
        
        // Create sample JS file
        $js = '
        console.log("E-book assets loaded successfully");
        console.log("Session protected with encryption");
        ';
        file_put_contents($assetsDir . '/script.js', $js);
        
        // Create sample config file
        $config = json_encode([
            'title' => 'Protected E-book',
            'version' => '1.0.0',
            'encryption' => 'AES-256',
            'session_key' => 'protected',
            'timestamp' => time()
        ], JSON_PRETTY_PRINT);
        file_put_contents($assetsDir . '/config.json', $config);
    }
    
    private function launchEbookViewer($tempDir)
    {
        // Launch ebook dengan browser default (lebih reliable)
        $indexFile = $tempDir . '/index.html';
        
        // Pastikan file ada
        if (!file_exists($indexFile)) {
            throw new \Exception('E-book file not found: ' . $indexFile);
        }
        
        // Open di browser default
        if (PHP_OS_FAMILY === 'Windows') {
            exec("start \"\" \"$indexFile\"");
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            exec("open \"$indexFile\"");
        } else {
            exec("xdg-open \"$indexFile\"");
        }
        
        $this->logActivity('E-book viewer launched: ' . $indexFile);
    }
    
    private function startProcessMonitoring($tempDir)
    {
        // Register cleanup ketika script terminate
        // register_shutdown_function(function() use ($tempDir) {
        //     $this->cleanupTempDirectory($tempDir);
        // });
        
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
        file_put_contents(storage_path('logs/ebook-activity.log'), $logEntry, FILE_APPEND);
    }
}