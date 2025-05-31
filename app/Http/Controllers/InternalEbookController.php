<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Native\Laravel\Facades\Window;

class InternalEbookController extends Controller
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
            
            // Launch e-book di window internal
            $this->launchInternalWindow($tempDir, $sessionKey);
            
            // Start monitoring
            $this->startProcessMonitoring($tempDir);
            
            return response()->json([
                'success' => true,
                'message' => 'E-book berhasil dibuka dalam aplikasi',
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

    public function viewEbook(Request $request)
    {
        // Route untuk menampilkan e-book di window internal
        $tempDir = $request->query('temp_dir');
        $sessionKey = $request->query('session_key');
        
        // Debug logging
        $this->logActivity("ViewEbook called with tempDir: {$tempDir}, sessionKey: " . substr($sessionKey, 0, 8) . "...");
        
        if (!$tempDir || !$sessionKey) {
            $this->logActivity("Missing parameters - tempDir: {$tempDir}, sessionKey present: " . ($sessionKey ? 'yes' : 'no'));
            abort(400, 'Missing required parameters');
        }
        
        if (!file_exists($tempDir . '/index.html')) {
            $this->logActivity("E-book content not found at: {$tempDir}/index.html");
            abort(404, 'E-book content not found');
        }
        
        // Validate session key
        if (!$this->validateSessionKey($sessionKey)) {
            $this->logActivity("Invalid session key format");
            abort(403, 'Invalid session');
        }
        
        try {
            // Load content dari temp directory
            $content = file_get_contents($tempDir . '/index.html');
            
            if (!$content) {
                $this->logActivity("Failed to read content from: {$tempDir}/index.html");
                abort(500, 'Failed to load e-book content');
            }
            
            // Enhance content untuk internal viewer
            $enhancedContent = $this->enhanceContentForInternalViewer($content, $sessionKey, $tempDir);
            
            $this->logActivity("E-book content successfully loaded and enhanced");
            
            return response($enhancedContent)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
                
        } catch (\Exception $e) {
            $this->logActivity("Error in viewEbook: " . $e->getMessage());
            abort(500, 'Internal server error: ' . $e->getMessage());
        }
    }

    public function closeEbook(Request $request)
    {
        try {
            $tempDir = $request->input('temp_dir');
            
            if ($tempDir && is_dir($tempDir)) {
                $this->cleanupTempDirectory($tempDir);
                $this->logActivity('E-book closed and cleaned up: ' . $tempDir);
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
    
    public function launcherCheck()
    {
        // API endpoint untuk internal window check main app
        return response()->json([
            'status' => 'alive',
            'timestamp' => time(),
            'message' => 'Main launcher is running',
            'app_version' => config('app.version', '1.0.0')
        ]);
    }
    
    private function launchInternalWindow($tempDir, $sessionKey)
    {
        // URL untuk internal viewer
        $viewerUrl = url('/internal-ebook/view?' . http_build_query([
            'temp_dir' => $tempDir,
            'session_key' => $sessionKey
        ]));
        
        // Debug log untuk memastikan URL benar
        $this->logActivity('Launching internal window with URL: ' . $viewerUrl);
        
        try {
            // Coba approach sederhana dulu
            Window::open()
                ->url($viewerUrl)
                ->title('📚 E-book Viewer')
                ->width(1200)
                ->height(850)
                ->minWidth(900)
                ->minHeight(700)
                ->maximizable(true)
                ->resizable(true)
                ->hideMenu(true)
                ->alwaysOnTop(false);
                
            $this->logActivity('Internal window launched successfully');
            
        } catch (\Exception $e) {
            $this->logActivity('Failed to launch internal window: ' . $e->getMessage());
            
            // Fallback: Launch di browser external sebagai backup
            $this->launchInBrowserFallback($tempDir);
        }
    }
    
    private function launchInBrowserFallback($tempDir)
    {
        // Fallback method - buka di browser jika NativePHP Window gagal
        $indexFile = $tempDir . '/index.html';
        
        if (!file_exists($indexFile)) {
            throw new \Exception('E-book file not found: ' . $indexFile);
        }
        
        // Buka di browser default sesuai OS
        if (PHP_OS_FAMILY === 'Windows') {
            exec("start \"\" \"$indexFile\"");
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            exec("open \"$indexFile\"");
        } else {
            exec("xdg-open \"$indexFile\"");
        }
        
        $this->logActivity('Fallback: E-book launched in external browser: ' . $indexFile);
    }
    
    private function enhanceContentForInternalViewer($content, $sessionKey, $tempDir)
    {
        // Update relative URLs untuk assets
        $content = $this->updateAssetUrls($content, $tempDir, $sessionKey);
        
        // Inject internal app enhancements
        $internalEnhancements = $this->getInternalAppEnhancements($sessionKey, $tempDir);
        
        // Inject sebelum </head>
        if (strpos($content, '</head>') !== false) {
            $content = str_replace('</head>', $internalEnhancements . '</head>', $content);
        } else {
            $content = $internalEnhancements . $content;
        }
        
        // Inject UI controls setelah <body>
        $uiControls = $this->getInternalUIControls($sessionKey, $tempDir);
        
        if (strpos($content, '<body') !== false) {
            $content = preg_replace('/(<body[^>]*>)/', '$1' . $uiControls, $content);
        } else {
            $content = $uiControls . $content;
        }
        
        // Add closing wrapper for content
        $content .= '</div>'; // Close .ebook-content wrapper
        
        return $content;
    }
    
    private function getInternalAppEnhancements($sessionKey, $tempDir)
    {
        return '
        <meta name="csrf-token" content="' . csrf_token() . '">
        <style>
            /* Internal App Styling */
            body {
                margin: 0;
                padding: 0;
                background: #f8f9fa;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
                user-select: none;
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
            }
            
            /* App Header */
            .app-header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 50px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 20px;
                z-index: 10000;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .app-title {
                font-size: 16px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .app-controls {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            
            .app-btn {
                background: rgba(255,255,255,0.2);
                border: none;
                color: white;
                padding: 6px 12px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 12px;
                transition: background 0.2s;
            }
            
            .app-btn:hover {
                background: rgba(255,255,255,0.3);
            }
            
            .app-btn.danger {
                background: rgba(220, 53, 69, 0.8);
            }
            
            .app-btn.danger:hover {
                background: rgba(220, 53, 69, 1);
            }
            
            /* Content area */
            .ebook-content {
                margin-top: 50px;
                height: calc(100vh - 50px);
                overflow: auto;
            }
            
            /* Status indicator */
            .status-indicator {
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 12px;
            }
            
            .status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #28a745;
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
            
            /* Hide original flipbook controls that might conflict */
            .flipbook-container {
                position: relative;
            }
        </style>
        
        <script>
            // Internal App JavaScript
            const APP_SESSION = "' . $sessionKey . '";
            const TEMP_DIR = "' . addslashes($tempDir) . '";
            
            // App state management
            let appState = {
                isActive: true,
                sessionValid: true,
                lastCheck: Date.now()
            };
            
            // Internal app functions
            function closeEbookApp() {
                if (confirm("Tutup e-book dan bersihkan data temporary?")) {
                    fetch("/internal-ebook/close", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").getAttribute("content")
                        },
                        body: JSON.stringify({
                            temp_dir: TEMP_DIR
                        })
                    }).finally(() => {
                        window.close();
                    });
                }
            }
            
            function toggleFullscreen() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(err => {
                        console.log("Fullscreen not supported");
                    });
                } else {
                    document.exitFullscreen();
                }
            }
            
            function refreshContent() {
                window.location.reload();
            }
            
            // Enhanced security untuk internal app
            document.addEventListener("keydown", function(e) {
                // Allow some developer shortcuts for internal app debugging
                if (e.ctrlKey && e.shiftKey && e.keyCode === 73 && e.altKey) {
                    // Ctrl+Shift+Alt+I allowed for internal debugging
                    return true;
                }
                
                // Block other dev tools shortcuts
                if (e.keyCode === 123 || // F12
                    (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
                    (e.ctrlKey && e.keyCode === 85)) { // Ctrl+U
                    e.preventDefault();
                    return false;
                }
            });
            
            // App lifecycle management
            window.addEventListener("load", function() {
                console.log("📚 Internal E-book App loaded");
                console.log("🔒 Session:", APP_SESSION.substring(0, 16) + "...");
                
                // Focus window
                window.focus();
                
                // Update status
                updateAppStatus("ready");
            });
            
            function updateAppStatus(status) {
                const indicator = document.querySelector(".status-indicator .status-text");
                if (indicator) {
                    indicator.textContent = status === "ready" ? "Ready" : "Active";
                }
            }
            
            // Cleanup on window close
            window.addEventListener("beforeunload", function() {
                appState.isActive = false;
            });
        </script>';
    }
    
    private function getInternalUIControls($sessionKey, $tempDir)
    {
        return '
        <!-- Internal App Header -->
        <div class="app-header">
            <div class="app-title">
                <span>📚</span>
                <span>E-book Viewer</span>
            </div>
            
            <div class="app-controls">
                <div class="status-indicator">
                    <div class="status-dot"></div>
                    <span class="status-text">Active</span>
                </div>
                
                <button class="app-btn" onclick="refreshContent()" title="Refresh">
                    🔄 Refresh
                </button>
                
                <button class="app-btn" onclick="toggleFullscreen()" title="Toggle Fullscreen">
                    ⛶ Fullscreen
                </button>
                
                <button class="app-btn danger" onclick="closeEbookApp()" title="Close E-book">
                    ✕ Close
                </button>
            </div>
        </div>
        
        <!-- Content Wrapper -->
        <div class="ebook-content">';
    }
    
    private function updateAssetUrls($content, $tempDir, $sessionKey)
    {
        // Update relative asset paths untuk route internal
        $content = preg_replace_callback(
            '/(src|href)=(["\'])([^"\']*)\2/i',
            function ($matches) use ($tempDir, $sessionKey) {
                $attribute = $matches[1];
                $quote = $matches[2];
                $path = $matches[3];
                
                // Skip absolute URLs dan data URLs
                if (preg_match('/^(https?:|data:|\/\/)/i', $path)) {
                    return $matches[0];
                }
                
                // Convert relative path ke route asset internal
                $cleanPath = ltrim($path, './');
                $secureUrl = url('/internal-ebook/assets/' . $cleanPath) . 
                            '?' . http_build_query(['temp_dir' => $tempDir, 'session_key' => $sessionKey]);
                
                return $attribute . '=' . $quote . $secureUrl . $quote;
            },
            $content
        );
        
        return $content;
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
        
        $this->logActivity('E-book content prepared for internal viewer: ' . $tempDir);
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
        
        $this->logActivity('Flipbook copied for internal viewer: ' . $sourceDir . ' to: ' . $destDir);
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
        $tempDir = storage_path('app/temp/internal_ebook_' . time() . '_' . rand(1000, 9999));
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        return $tempDir;
    }
    
    private function startProcessMonitoring($tempDir)
    {
        // Set timeout untuk auto-cleanup (4 jam untuk internal app)
        $cleanupTime = time() + (4 * 60 * 60);
        file_put_contents($tempDir . '/.cleanup_time', $cleanupTime);
        
        $this->logActivity('Internal app monitoring started: ' . $tempDir);
    }
    
    private function cleanupTempDirectory($tempDir)
    {
        if (is_dir($tempDir)) {
            $this->deleteDirectory($tempDir);
            $this->logActivity('Internal app temp directory cleaned: ' . $tempDir);
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
        
        file_put_contents(storage_path('logs/internal-ebook-activity.log'), $logEntry, FILE_APPEND);
    }
}