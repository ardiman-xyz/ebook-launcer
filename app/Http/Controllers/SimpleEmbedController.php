<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\FacadesLog;
use Illuminate\Support\Facades\Storage;

class SimpleEmbedController extends Controller
{

    public function index(Request $request)
    {
        $tempDir = $request->query('temp_dir');
        $sessionKey = $request->query('session');
        
        if (!$tempDir || !$sessionKey) {
            abort(400, 'Missing parameters');
        }
        
        $indexFile = $tempDir . '/index.html';
        
        if (!file_exists($indexFile)) {
            abort(404, 'Index file not found');
        }
        
        // Baca dan proses content
        $content = file_get_contents($indexFile);
        
        // Update asset URLs untuk iframe context
        $content = preg_replace_callback(
            '/(src|href)=(["\'])([^"\']*)\2/i',
            function ($matches) use ($tempDir, $sessionKey) {
                $path = $matches[3];
                
                // Skip absolute URLs dan data URLs
                if (preg_match('/^(https?:|data:|\/\/)/i', $path)) {
                    return $matches[0];
                }
                
                // Convert ke secure asset URL
                $cleanPath = ltrim($path, './');
                $assetUrl = url('/embed-asset/' . $cleanPath . '?' . http_build_query([
                    'temp_dir' => $tempDir,
                    'session' => $sessionKey
                ]));
                
                return $matches[1] . '=' . $matches[2] . $assetUrl . $matches[2];
            },
            $content
        );
        
        return response($content)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function launch(Request $request)
    {
        try {
            Log::info('SimpleEmbed launch called - attempt');
            
            // Debug: cek activation file
            if (!Storage::exists('activation.json')) {
                Log::error('Activation file missing during launch');
                return response()->json([
                    'success' => false,
                    'message' => 'File aktivasi tidak ditemukan'
                ], 403);
            }
            
            // Validate aktivasi - BYPASS untuk testing
            if (!$this->isActivationValid()) {
                Log::error('Activation validation failed during launch');
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi aktivasi gagal'
                ], 403);
            }
            
            Log::info('Activation valid, creating session...');
            
            // Generate session
            $sessionKey = $this->generateSessionKey();
            $tempDir = $this->createTempDirectory();
            
            Log::info('Created temp dir: ' . $tempDir);
            
            // Copy content
            $this->prepareContent($tempDir);
            
            // Return URL untuk embed viewer
            $viewerUrl = url('/embed-viewer?' . http_build_query([
                'temp_dir' => $tempDir,
                'session' => $sessionKey
            ]));
            
            Log::info('Success - returning viewer URL');
            
            return response()->json([
                'success' => true,
                'message' => 'E-book siap dibuka',
                'viewer_url' => $viewerUrl
            ]);
            
        } catch (\Exception $e) {
            Log::error('SimpleEmbed launch exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // 2. Update isActivationValid - BYPASS TOTAL untuk testing
    private function isActivationValid()
    {
        Log::info('Checking activation validity...');
        
        // COMPLETE BYPASS untuk debugging
        return true;
        
        /*
        // Original code untuk production nanti
        if (!Storage::exists('activation.json')) {
            return false;
        }
        
        $activation = json_decode(Storage::get('activation.json'), true);
        $currentFingerprint = $this->getDeviceFingerprint();
        
        return $activation['device_fingerprint'] === $currentFingerprint;
        */
    }
    
    public function view(Request $request)
    {
        $tempDir = $request->query('temp_dir');
        $sessionKey = $request->query('session');
        
        if (!$tempDir || !$sessionKey) {
            abort(400, 'Missing parameters');
        }
        
        if (!file_exists($tempDir . '/index.html')) {
            abort(404, 'Content not found');
        }
        
        return view('embed.viewer', [
            'tempDir' => $tempDir,
            'sessionKey' => $sessionKey
        ]);
    }
    
    public function content(Request $request)
    {
        $tempDir = $request->query('temp_dir');
        $sessionKey = $request->query('session');
        
        if (!$tempDir || !$sessionKey) {
            abort(400, 'Missing parameters');
        }
        
        $indexFile = $tempDir . '/index.html';
        
        if (!file_exists($indexFile)) {
            abort(404, 'Content not found');
        }
        
        $content = file_get_contents($indexFile);
        
        // DEBUG: Log original content
        Log::info('Original content length: ' . strlen($content));
        
        // Update asset URLs - PERBAIKI REGEX dan URL generation
        $content = preg_replace_callback(
            '/(src|href)=(["\'])([^"\']*)\2/i',
            function ($matches) use ($tempDir, $sessionKey) {
                $attribute = $matches[1]; // src atau href
                $quote = $matches[2];     // " atau '
                $path = $matches[3];      // path asli
                
                // Skip absolute URLs, data URLs, dan URLs yang sudah diproses
                if (preg_match('/^(https?:|data:|\/\/|\/embed-asset)/i', $path)) {
                    return $matches[0];
                }
                
                // Clean path - remove ./ dan ../
                $cleanPath = preg_replace('/^\.\/+/', '', $path);
                $cleanPath = preg_replace('/^\/+/', '', $cleanPath);
                
                // Generate secure asset URL
                $assetUrl = url('/embed-asset/' . $cleanPath . '?' . http_build_query([
                    'temp_dir' => $tempDir,
                    'session' => $sessionKey
                ]));
                
                // DEBUG: Log URL conversion
                Log::info("Asset URL conversion: '$path' -> '$assetUrl'");
                
                return $attribute . '=' . $quote . $assetUrl . $quote;
            },
            $content
        );
        
        // DEBUG: Log processed content length
        Log::info('Processed content length: ' . strlen($content));
        
        return response($content)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
    

    public function asset(Request $request, $path)
    {
        $tempDir = $request->query('temp_dir');
        $sessionKey = $request->query('session');
        
        // Debug logging
        Log::info('Asset request received:', [
            'path' => $path,
            'temp_dir' => $tempDir,
            'session' => $sessionKey ? substr($sessionKey, 0, 8) . '...' : 'missing'
        ]);
        
        if (!$tempDir || !$sessionKey) {
            Log::error('Missing required parameters');
            abort(400, 'Missing parameters');
        }
        
        // Decode path dan clean
        $decodedPath = urldecode($path);
        $filePath = $tempDir . '/' . $decodedPath;
        
        Log::info('Looking for file:', [
            'decoded_path' => $decodedPath,
            'full_path' => $filePath,
            'exists' => file_exists($filePath)
        ]);
        
        if (!file_exists($filePath)) {
            // Debug: List available files
            if (is_dir($tempDir)) {
                $allFiles = $this->getAllFiles($tempDir);
                Log::info('Available files in temp dir:', $allFiles);
            }
            
            Log::error('File not found: ' . $filePath);
            abort(404, 'Asset not found: ' . $decodedPath);
        }
        
        // Security check
        $realTempDir = realpath($tempDir);
        $realFilePath = realpath($filePath);
        
        if (!$realFilePath || !$realTempDir || strpos($realFilePath, $realTempDir) !== 0) {
            Log::error('Security violation detected');
            abort(403, 'Access denied');
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // KHUSUS UNTUK INDEX.HTML - Process asset URLs
        if ($extension === 'html' && basename($filePath) === 'index.html') {
            Log::info('Processing HTML file with asset URL rewriting');
            
            $content = file_get_contents($filePath);
            
            // Update semua relative URLs
            $content = preg_replace_callback(
                '/(src|href)=(["\'])([^"\']*)\2/i',
                function ($matches) use ($tempDir, $sessionKey) {
                    $attribute = $matches[1];
                    $quote = $matches[2];
                    $originalPath = $matches[3];
                    
                    // Skip absolute URLs, data URLs, and already processed URLs
                    if (preg_match('/^(https?:|data:|\/\/|mailto:|tel:|#|\/embed-asset)/i', $originalPath)) {
                        return $matches[0];
                    }
                    
                    // Clean relative path
                    $cleanPath = $originalPath;
                    $cleanPath = preg_replace('/^\.\/+/', '', $cleanPath);
                    $cleanPath = preg_replace('/^\/+/', '', $cleanPath);
                    
                    // Generate secure URL
                    $secureUrl = url('/embed-asset/' . $cleanPath . '?' . http_build_query([
                        'temp_dir' => $tempDir,
                        'session' => $sessionKey
                    ]));
                    
                    Log::info("URL rewrite: '$originalPath' -> '$secureUrl'");
                    
                    return $attribute . '=' . $quote . $secureUrl . $quote;
                },
                $content
            );
            
            return response($content)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }
        
        // UNTUK FILE LAINNYA - serve langsung
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
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
            'pdf' => 'application/pdf',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm'
        ];
        
        $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        Log::info('Serving file:', [
            'path' => $filePath,
            'type' => $contentType,
            'size' => filesize($filePath)
        ]);
        
        return response()->file($filePath, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=3600'
        ]);
    }
    

    private function getAllFiles($dir, $prefix = '')
    {
        $files = [];
        
        if (!is_dir($dir)) {
            return $files;
        }
        
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $fullPath = $dir . '/' . $item;
            $relativePath = $prefix . $item;
            
            if (is_dir($fullPath)) {
                $files[] = $relativePath . '/';
                $subFiles = $this->getAllFiles($fullPath, $relativePath . '/');
                $files = array_merge($files, $subFiles);
            } else {
                $files[] = $relativePath;
            }
        }
        
        return $files;
    }



    private function prepareContent($tempDir)
    {
        $sourceDir = storage_path('app/flipbook/out3');
        
        Log::info('Preparing content from existing flipbook:', [
            'source' => $sourceDir,
            'dest' => $tempDir,
            'source_exists' => is_dir($sourceDir),
            'index_exists' => file_exists($sourceDir . '/index.html')
        ]);
        
        if (!file_exists($sourceDir . '/index.html')) {
            throw new \Exception('Flipbook tidak ditemukan di: ' . $sourceDir);
        }
        
        // Copy semua content dari flipbook asli
        $this->copyDirectory($sourceDir, $tempDir);
        
        Log::info('Content copy completed:', [
            'source_files' => is_dir($sourceDir) ? count(scandir($sourceDir)) - 2 : 0,
            'dest_files' => is_dir($tempDir) ? count(scandir($tempDir)) - 2 : 0
        ]);
    }
    
    private function copyDirectory($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $sourcePath = $source . '/' . $file;
            $destPath = $dest . '/' . $file;
            
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }
    
    
      
    private function generateSessionKey()
    {
        return bin2hex(random_bytes(16));
    }
    
    private function createTempDirectory()
    {
        $tempDir = storage_path('app/temp/embed_' . time() . '_' . rand(1000, 9999));
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        return $tempDir;
    }
    
    private function getDeviceFingerprint()
    {
        $systemInfo = [
            'hostname' => gethostname(),
            'os' => PHP_OS,
            'user' => get_current_user(),
        ];
        
        return hash('sha256', json_encode($systemInfo));
    }
}