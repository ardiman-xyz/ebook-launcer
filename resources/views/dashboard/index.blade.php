<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-book Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .status {
            background: #e8f5e8;
            color: #2d5a2d;
            padding: 10px 15px;
            border-radius: 20px;
            display: inline-block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 30px;
        }
        
        .launch-options {
            margin-bottom: 30px;
        }
        
        .launch-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            text-align: left;
        }
        
        .launch-section h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .launch-section p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .btn-launch {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-launch:hover {
            transform: translateY(-2px);
        }
        
        .btn-internal {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-external {
            background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);
            color: white;
        }
        
        .features {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .feature-tag {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .feature-tag.security {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .feature-tag.performance {
            background: #d4edda;
            color: #155724;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .menu-item {
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            text-decoration: none;
            color: #495057;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .menu-item:hover {
            background: #e9ecef;
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .device-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: left;
            font-size: 14px;
            color: #666;
        }
        
        .device-info h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .device-info p {
            margin-bottom: 5px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .loading {
            display: none;
            color: #667eea;
            font-weight: 500;
            margin-top: 10px;
        }
        
        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">📚</div>
            <h1>E-book Dashboard</h1>
            <div class="status">✅ Aktif & Terverifikasi</div>
        </div>
        
        @if(session('success'))
            <div class="success-message">
                {{ session('success') }}
            </div>
        @endif
        
        <div class="launch-options">
        
            
            <!-- Option 2: External Browser -->
            <div class="launch-section">
                <h3>🌐 Buka di Browser External</h3>
                <p>
                    Buka e-book di browser default sistem (Chrome, Firefox, dll). 
                    Cocok untuk testing atau jika mengalami masalah dengan viewer internal. 
                    Masih memiliki proteksi monitoring launcher.
                </p>
                <button class="btn-launch btn-external" onclick="launchExternalEbook()">
                    🌍 Buka E-book (Browser)
                </button>
                
            </div>
        </div>
        
        <div class="menu-grid">
            <a href="{{ route('settings') }}" class="menu-item">
                ⚙️ Pengaturan
            </a>
            <a href="{{ route('license.info') }}" class="menu-item">
                ℹ️ Info Lisensi
            </a>
            <a href="#" class="menu-item" onclick="resetActivation()">
                🔄 Reset
            </a>
            <a href="#" class="menu-item" onclick="window.close()">
                ❌ Keluar
            </a>
        </div>
        
        <div class="device-info">
            <h3>Informasi Aktivasi</h3>
            <p><strong>License:</strong> {{ substr($activationData['license_key'], 0, 4) }}****</p>
            <p><strong>Device:</strong> {{ gethostname() }}</p>
            <p><strong>Aktivasi:</strong> {{ date('d M Y H:i', strtotime($activationData['activated_at'])) }}</p>
            <p><strong>Status:</strong> <span style="color: #28a745;">Aktif</span></p>
        </div>
    </div>
    
    <script>
        // Launch Internal E-book (Option 1)
        async function launchInternalEbook() {
            const button = document.querySelector('.btn-internal');
            const loading = document.getElementById('internal-loading');
            
            // Show loading state
            button.style.display = 'none';
            loading.style.display = 'block';
            
            try {
                const response = await fetch('/launch-internal-ebook', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loading.innerHTML = '<div class="spinner"></div>E-book berhasil dibuka dalam aplikasi!';
                    
                    // Reset button after delay
                    setTimeout(() => {
                        button.style.display = 'block';
                        loading.style.display = 'none';
                    }, 3000);
                } else {
                    alert('Error: ' + result.message);
                    button.style.display = 'block';
                    loading.style.display = 'none';
                }
            } catch (error) {
                alert('Error: ' + error.message);
                button.style.display = 'block';
                loading.style.display = 'none';
            }
        }
        
        // Launch External E-book (Option 2)
        async function launchExternalEbook() {
            const button = document.querySelector('.btn-external');
            const loading = document.getElementById('external-loading');
            
            // Show loading state
            button.style.display = 'none';
            loading.style.display = 'block';
            
            try {
                const response = await fetch('/launch-ebook', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loading.innerHTML = '<div class="spinner"></div>E-book berhasil dibuka di browser!';
                    
                    // Reset button after delay
                    setTimeout(() => {
                        button.style.display = 'block';
                        loading.style.display = 'none';
                    }, 3000);
                } else {
                    alert('Error: ' + result.message);
                    button.style.display = 'block';
                    loading.style.display = 'none';
                }
            } catch (error) {
                alert('Error: ' + error.message);
                button.style.display = 'block';
                loading.style.display = 'none';
            }
        }
        
        function resetActivation() {
            if (confirm('Yakin ingin reset aktivasi? Anda harus aktivasi ulang.')) {
                window.location.href = '/activation';
            }
        }
        
        // Show tips on first load
        window.addEventListener('load', function() {
            // Check if first time user
            const hasSeenTips = localStorage.getItem('ebook_launcher_tips_seen');
            
            if (!hasSeenTips) {
                setTimeout(() => {
                    alert(`💡 Tips Penggunaan E-book Launcher:

🏠 Mode Internal (Rekomended):
• Keamanan maksimal dengan auto-close protection
• E-book tertutup otomatis jika launcher ditutup
• Performance optimal dalam aplikasi

🌐 Mode Browser External:
• Cocok untuk testing atau troubleshooting
• Tetap memiliki monitoring protection
• Perlu ditutup manual jika launcher ditutup

Pilih mode sesuai kebutuhan Anda!`);
                    
                    localStorage.setItem('ebook_launcher_tips_seen', 'true');
                }, 1000);
            }
        });
    </script>
</body>
</html>