{{-- Update resources/views/embed/viewer.blade.php - Improve layout --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>📚 E-book Viewer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
            background: #f8f9fa;
            overflow: hidden;
        }
        
        .viewer-header {
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
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .viewer-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .viewer-info {
            font-size: 12px;
            opacity: 0.8;
            margin-left: 10px;
        }
        
        .viewer-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-badge {
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
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
        
        .btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }
        
        .btn.danger {
            background: rgba(220, 53, 69, 0.8);
        }
        
        .btn.danger:hover {
            background: rgba(220, 53, 69, 1);
        }
        
        .content-frame {
            position: fixed;
            top: 50px;
            left: 0;
            right: 0;
            bottom: 0;
            border: none;
            background: white;
            width: 100%;
            height: calc(100vh - 50px);
        }
        
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #666;
            z-index: 999;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            font-size: 16px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="viewer-header">
        <div style="display: flex; align-items: center;">
            <div class="viewer-title">
                <span>📚</span>
                <span>E-book Viewer</span>
            </div>
            <div class="viewer-info">
                Protected Content • Session Active
            </div>
        </div>
        
        <div class="viewer-controls">
            <div class="status-badge">
                <div class="status-dot"></div>
                <span>Protected</span>
            </div>
            
            <button class="btn" onclick="reloadContent()" title="Reload Content">
                <span>🔄</span>
                <span>Reload</span>
            </button>
            
            <button class="btn" onclick="toggleFullscreen()" title="Toggle Fullscreen">
                <span>⛶</span>
                <span>Fullscreen</span>
            </button>
            
            <button class="btn danger" onclick="closeViewer()" title="Close Viewer">
                <span>✕</span>
                <span>Close</span>
            </button>
        </div>
    </div>
    
    <!-- Loading -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <div class="loading-text">Loading e-book content...</div>
        <div style="font-size: 12px; color: #999; margin-top: 10px;">
            Please wait while we prepare your protected content
        </div>
    </div>
    <iframe 
        id="contentFrame" 
        class="content-frame" 
        src="{{ asset('/flipbook/out3/index.html') }}"
        onload="handleFrameLoad()"
        onerror="handleFrameError()"
        style="display: none;"
        frameborder="0"
        allowfullscreen>
    </iframe>

    <script>
        const CONFIG = {
            tempDir: @json($tempDir ?? ''),
            sessionKey: @json($sessionKey ?? '')
        };
    
        let loadTimeout;
    
        function handleFrameLoad() {
            clearTimeout(loadTimeout);
            document.getElementById('loading').style.display = 'none';
            document.getElementById('contentFrame').style.display = 'block';
            console.log('✅ Flipbook HTML loaded successfully');
            
            // Try to access iframe content for debugging
            try {
                const iframe = document.getElementById('contentFrame');
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                console.log('📄 Iframe content:', iframeDoc.title);
            } catch (e) {
                console.log('📄 Iframe loaded (cross-origin protection active)');
            }
        }
    
        function handleFrameError() {
            clearTimeout(loadTimeout);
            console.error('❌ Failed to load flipbook content');
            
            document.getElementById('loading').innerHTML = 
                '<div style="text-align: center; color: #dc3545;">' +
                '<h3>❌ Failed to load flipbook</h3>' +
                '<p>There was an error loading the flipbook content.</p>' +
                '<button onclick="reloadContent()" style="margin: 10px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Try Reload</button>' +
                '</div>';
        }
    
        function reloadContent() {
            console.log('🔄 Reloading flipbook content...');
            
            // Show loading
            document.getElementById('loading').innerHTML = 
                '<div class="spinner"></div>' +
                '<div class="loading-text">Reloading flipbook...</div>';
            document.getElementById('loading').style.display = 'block';
            document.getElementById('contentFrame').style.display = 'none';
            
            // Reload iframe with cache busting
            const iframe = document.getElementById('contentFrame');
            const currentSrc = iframe.src;
            const url = new URL(currentSrc);
            url.searchParams.set('_reload', Date.now());
            iframe.src = url.toString();
            
            // Reset timeout
            loadTimeout = setTimeout(() => {
                handleFrameError();
            }, 10000);
        }
    
        function toggleFullscreen() {
            console.log('🖥️ Toggling fullscreen...');
            
            try {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen()
                        .then(() => {
                            console.log('✅ Entered fullscreen mode');
                        })
                        .catch(err => {
                            console.log('❌ Fullscreen not supported:', err.message);
                            alert('Fullscreen tidak didukung di browser ini');
                        });
                } else {
                    document.exitFullscreen()
                        .then(() => {
                            console.log('✅ Exited fullscreen mode');
                        })
                        .catch(err => {
                            console.log('❌ Exit fullscreen failed:', err.message);
                        });
                }
            } catch (error) {
                console.log('❌ Fullscreen error:', error.message);
                alert('Fitur fullscreen tidak tersedia');
            }
        }
    
        function closeViewer() {
            console.log('🚪 Closing viewer...');
            
            if (confirm('Tutup e-book viewer?')) {
                try {
                    // Optional: Send cleanup request (jika ada endpoint)
                    if (CONFIG.tempDir && CONFIG.sessionKey) {
                        fetch('/embed-cleanup', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                temp_dir: CONFIG.tempDir,
                                session: CONFIG.sessionKey
                            })
                        }).catch(err => {
                            console.log('Cleanup request failed (non-critical):', err.message);
                        });
                    }
                    
                    // Close window
                    setTimeout(() => {
                        window.close();
                    }, 500);
                    
                } catch (error) {
                    console.log('Close error:', error.message);
                    // Fallback: just close
                    window.close();
                }
            }
        }
    
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case 'F11':
                    e.preventDefault();
                    toggleFullscreen();
                    break;
                case 'F5':
                    e.preventDefault();
                    reloadContent();
                    break;
                case 'Escape':
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    }
                    break;
            }
        });
    
        // Fullscreen change event
        document.addEventListener('fullscreenchange', function() {
            const isFullscreen = !!document.fullscreenElement;
            console.log('📺 Fullscreen changed:', isFullscreen ? 'ON' : 'OFF');
        });
    
        // Status monitoring
        setInterval(() => {
            const dot = document.querySelector('.status-dot');
            if (dot) {
                dot.style.background = '#28a745'; // Keep green
            }
        }, 5000);
    
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📚 Flipbook Viewer initialized');
            console.log('📁 Config:', {
                tempDir: CONFIG.tempDir || 'not set',
                sessionKey: CONFIG.sessionKey ? CONFIG.sessionKey.substring(0, 8) + '...' : 'not set',
                iframeSrc: document.getElementById('contentFrame')?.src || 'not found'
            });
            
            // Set initial timeout
            loadTimeout = setTimeout(() => {
                handleFrameError();
            }, 15000);
        });
    
        // Test functions - untuk debugging di console
        window.testFunctions = {
            reload: reloadContent,
            fullscreen: toggleFullscreen,
            close: closeViewer
        };
    
        console.log('🎮 Control functions loaded. Test dengan: testFunctions.reload(), testFunctions.fullscreen(), testFunctions.close()');
    </script>
</body>
</html>