<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>📚 {{ $metadata['title'] }}</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
            background: #f8f9fa;
            color: #333;
            overflow: hidden;
        }
        
        /* Header toolbar */
        .ebook-header {
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
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .ebook-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ebook-meta {
            font-size: 11px;
            opacity: 0.8;
            display: flex;
            gap: 10px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
        }
        
        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #28a745;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .header-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            transition: background 0.2s;
        }
        
        .header-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .header-btn.danger {
            background: rgba(220, 53, 69, 0.8);
        }
        
        .header-btn.danger:hover {
            background: rgba(220, 53, 69, 1);
        }
        
        /* Iframe container */
        .iframe-container {
            position: fixed;
            top: 50px;
            left: 0;
            right: 0;
            bottom: 0;
            background: white;
        }
        
        .ebook-iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: white;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 50px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: #666;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <!-- Header Toolbar -->
    <div class="ebook-header">
        <div class="header-left">
            <div class="ebook-title">
                <span>📚</span>
                <span>{{ $metadata['title'] }}</span>
            </div>
            <div class="ebook-meta">
                <span>📄 {{ $metadata['pages'] }}</span>
                <span>💾 {{ $metadata['size'] }}</span>
                <span>🕒 {{ $metadata['created'] }}</span>
            </div>
        </div>
        
        <div class="header-right">
            <div class="status-indicator">
                <div class="status-dot"></div>
                <span>Protected</span>
            </div>
            
            <button class="header-btn" onclick="reloadIframe()" title="Reload">
                🔄
            </button>
            
            <button class="header-btn" onclick="toggleFullscreen()" title="Fullscreen">
                ⛶
            </button>
            
            <button class="header-btn danger" onclick="closeEbook()" title="Close">
                ✕
            </button>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Memuat e-book...</div>
    </div>
    
    <!-- Iframe Container -->
    <div class="iframe-container">
        <iframe 
            id="ebookIframe" 
            class="ebook-iframe" 
            src="{{ url('/embed-ebook/iframe?' . http_build_query(['temp_dir' => $tempDir, 'session_key' => $sessionKey])) }}"
            onload="handleIframeLoad()"
            onerror="handleIframeError()">
        </iframe>
    </div>

    <script>
        // Configuration
        const EBOOK_CONFIG = {
            sessionKey: @json($sessionKey),
            tempDir: @json($tempDir),
            csrfToken: @json(csrf_token())
        };
        
        let iframeLoaded = false;
        
        function handleIframeLoad() {
            console.log('✅ Iframe loaded successfully');
            iframeLoaded = true;
            
            // Hide loading overlay after short delay
            setTimeout(() => {
                document.getElementById('loadingOverlay').style.display = 'none';
            }, 1000);
        }
        
        function handleIframeError() {
            console.error('❌ Iframe failed to load');
            document.getElementById('loadingOverlay').innerHTML = 
                '<div style="text-align: center; color: #dc3545;">' +
                '<h3>❌ Gagal memuat e-book</h3>' +
                '<p>Error loading iframe content</p>' +
                '<button onclick="reloadIframe()" style="margin-top: 10px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Coba Lagi</button>' +
                '</div>';
        }
        
        function reloadIframe() {
            console.log('🔄 Reloading iframe...');
            document.getElementById('loadingOverlay').style.display = 'flex';
            document.getElementById('ebookIframe').src = document.getElementById('ebookIframe').src;
        }
        
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log('Fullscreen not supported');
                });
            } else {
                document.exitFullscreen();
            }
        }
        
        function closeEbook() {
            if (confirm("Tutup e-book dan bersihkan data temporary?")) {
                fetch('/embed-ebook/close', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': EBOOK_CONFIG.csrfToken
                    },
                    body: JSON.stringify({
                        temp_dir: EBOOK_CONFIG.tempDir
                    })
                }).then(() => {
                    window.close();
                }).catch(() => {
                    window.location.href = '/dashboard';
                });
            }
        }
        
        // Connection monitoring
        setInterval(() => {
            fetch('/launcher-check')
                .then(response => {
                    if (response.ok) {
                        document.querySelector('.status-dot').style.background = '#28a745';
                    } else {
                        throw new Error('Launcher not responding');
                    }
                })
                .catch(() => {
                    document.querySelector('.status-dot').style.background = '#dc3545';
                });
        }, 30000);
        
        console.log('📚 Iframe E-book Viewer initialized');
        console.log('🔒 Session:', EBOOK_CONFIG.sessionKey.substring(0, 16) + '...');
        
        // Auto-hide loading after 10 seconds as fallback
        setTimeout(() => {
            if (!iframeLoaded) {
                console.warn('⚠️ Iframe load timeout, hiding loading overlay');
                document.getElementById('loadingOverlay').style.display = 'none';
            }
        }, 10000);
    </script>
</body>
</html>