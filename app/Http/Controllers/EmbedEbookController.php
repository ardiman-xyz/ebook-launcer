<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmbedEbookController extends Controller
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
            
            // Create temp directory untuk content
            $tempDir = $this->createTempDirectory();
            
            // Prepare e-book content
            $this->prepareEbookContent($tempDir, $sessionKey);
            
            // Generate URL untuk embedded viewer
            $viewerUrl = url('/embed-ebook/view?' . http_build_query([
                'temp_dir' => $tempDir,
                'session_key' => $sessionKey
            ]));
            
            // Start monitoring
            $this->startProcessMonitoring($tempDir);
            
            return response()->json([
                'success' => true,
                'message' => 'E-book berhasil disiapkan',
                'viewer_url' => $viewerUrl,
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

    public function view(Request $request)
    {
        $tempDir = $request->query('temp_dir');
        $sessionKey = $request->query('session_key');
        
        if (!$tempDir || !$sessionKey) {
            abort(400, 'Missing required parameters');
        }
        
        if (!$this->validateSessionKey($sessionKey)) {
            abort(403, 'Invalid session key');
        }
        
        if (!file_exists($tempDir . '/index.html')) {
            abort(404, 'E-book content not found');
        }
        
        // Load flipbook content
        $flipbookContent = file_get_contents($tempDir . '/index.html');
        
        // Get simple metadata
        $metadata = $this->getSimpleMetadata($tempDir, $flipbookContent);
        
        // Pass data ke view
        return view('ebook.embedded-viewer', [
            'flipbookContent' => $flipbookContent,
            'sessionKey' => $sessionKey,
            'tempDir' => $tempDir,
            'metadata' => $metadata,
            'assetBaseUrl' => url('/embed-ebook/assets') . '?temp_dir=' . urlencode($tempDir) . '&session_key=' . $sessionKey . '&path='
        ]);
    }

    public function iframe(Request $request)
    {
        $tempDir = $request->query('temp_dir');
        $sessionKey = $request->query('session_key');
        
        if (!$tempDir || !$sessionKey) {
            abort(400, 'Missing required parameters');
        }
        
        if (!$this->validateSessionKey($sessionKey)) {
            abort(403, 'Invalid session key');
        }
        
        $indexFile = $tempDir . '/index.html';
        
        if (!file_exists($indexFile)) {
            abort(404, 'E-book content not found');
        }
        
        // Load dan modifikasi content flipbook
        $content = file_get_contents($indexFile);
        
        // Update asset URLs untuk iframe context
        $content = $this->updateAssetUrlsForIframe($content, $tempDir, $sessionKey);
        
        // Inject security script
        $securityScript = '
        <script>
            // Basic security untuk iframe
            document.addEventListener("contextmenu", function(e) { e.preventDefault(); });
            document.addEventListener("selectstart", function(e) { e.preventDefault(); });
            document.addEventListener("keydown", function(e) {
                if (e.keyCode === 123 || (e.ctrlKey && e.shiftKey && e.keyCode === 73)) {
                    e.preventDefault();
                    return false;
                }
            });
            
            console.log("📚 E-book iframe loaded with session:", "' . substr($sessionKey, 0, 8) . '...");
        </script>';
        
        // Inject sebelum </body>
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', $securityScript . '</body>', $content);
        } else {
            $content .= $securityScript;
        }
        
        return response($content)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
    
    private function updateAssetUrlsForIframe($content, $tempDir, $sessionKey)
    {
        // Debug: Log original content untuk debugging
        $this->logActivity('Original content length: ' . strlen($content));
        
        // Update relative asset paths untuk iframe context
        $assetBaseUrl = url('/embed-ebook/assets') . '?temp_dir=' . urlencode($tempDir) . '&session_key=' . $sessionKey . '&path=';
        
        $this->logActivity('Asset base URL: ' . $assetBaseUrl);
        
        // Replace src attributes dengan logging
        $content = preg_replace_callback(
            '/(src|href)=(["\'])([^"\']*)\2/i',
            function ($matches) use ($assetBaseUrl, $tempDir) {
                $attribute = $matches[1];
                $quote = $matches[2];
                $path = $matches[3];
                
                // Skip absolute URLs dan data URLs
                if (preg_match('/^(https?:|data:|\/\/)/i', $path)) {
                    return $matches[0];
                }
                
                // Convert relative path
                $cleanPath = ltrim($path, './');
                $securedUrl = $assetBaseUrl . urlencode($cleanPath);
                
                // Log untuk debugging
                $this->logActivity("Asset URL conversion: '$path' -> '$securedUrl'");
                
                // Check apakah file exists
                $actualFile = $tempDir . '/' . $cleanPath;
                if (!file_exists($actualFile)) {
                    $this->logActivity("WARNING: Asset file not found: $actualFile");
                } else {
                    $this->logActivity("Asset file exists: $actualFile");
                }
                
                return $attribute . '=' . $quote . $securedUrl . $quote;
            },
            $content
        );
        
        return $content;
        try {
            $tempDir = $request->input('temp_dir');
            
            if ($tempDir && is_dir($tempDir)) {
                $this->cleanupTempDirectory($tempDir);
                $this->logActivity('Embedded e-book closed and cleaned up: ' . $tempDir);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'E-book ditutup dan dibersihkan'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function getSimpleMetadata($tempDir, $content)
    {
        // Simple metadata tanpa kompleksitas
        $metadata = [
            'title' => 'Protected E-book',
            'pages' => 'Unknown',
            'size' => 'Calculating...',
            'created' => date('d M Y H:i')
        ];
        
        // Extract title from HTML
        if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $content, $matches)) {
            $title = trim(strip_tags($matches[1]));
            if (!empty($title)) {
                $metadata['title'] = $title;
            }
        }
        
        // Simple size calculation
        try {
            if (is_dir($tempDir)) {
                $size = 0;
                $files = scandir($tempDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $filePath = $tempDir . '/' . $file;
                        if (is_file($filePath)) {
                            $size += filesize($filePath);
                        }
                    }
                }
                $metadata['size'] = $this->formatBytes($size);
            }
        } catch (\Exception $e) {
            $metadata['size'] = 'Unknown';
        }
        
        // Simple page count
        try {
            $filesDir = $tempDir . '/files';
            if (is_dir($filesDir)) {
                $files = scandir($filesDir);
                $imageCount = 0;
                foreach ($files as $file) {
                    if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
                        $imageCount++;
                    }
                }
                $metadata['pages'] = $imageCount > 0 ? $imageCount : 'Unknown';
            }
        } catch (\Exception $e) {
            $metadata['pages'] = 'Unknown';
        }
        
        return $metadata;
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
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
        
        $this->logActivity('E-book content prepared for embedded viewer: ' . $tempDir);
    }
    
    private function copyFlipbookDirectory($sourceDir, $destDir)
    {
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        
        $files = scandir($sourceDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $file;
            $destPath = $destDir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($sourcePath)) {
                $this->copyFlipbookDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
        
        $this->logActivity('Flipbook copied for embedded viewer: ' . $sourceDir . ' to: ' . $destDir);
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
    
    private function validateSessionKey($sessionKey)
    {
        return $sessionKey && strlen($sessionKey) === 64 && ctype_xdigit($sessionKey);
    }
    
    private function generateSessionKey()
    {
        return bin2hex(random_bytes(32));
    }
    
    private function createTempDirectory()
    {
        $tempDir = storage_path('app/temp/embed_ebook_' . time() . '_' . rand(1000, 9999));
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        return $tempDir;
    }
    
    private function startProcessMonitoring($tempDir)
    {
        // Set timeout untuk auto-cleanup (3 jam untuk embedded viewer)
        $cleanupTime = time() + (3 * 60 * 60);
        file_put_contents($tempDir . '/.cleanup_time', $cleanupTime);
        
        $this->logActivity('Embedded viewer monitoring started: ' . $tempDir);
    }
    
    private function cleanupTempDirectory($tempDir)
    {
        if (is_dir($tempDir)) {
            $this->deleteDirectory($tempDir);
            $this->logActivity('Embedded viewer temp directory cleaned: ' . $tempDir);
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
        
        file_put_contents(storage_path('logs/embed-ebook-activity.log'), $logEntry, FILE_APPEND);
    }
}