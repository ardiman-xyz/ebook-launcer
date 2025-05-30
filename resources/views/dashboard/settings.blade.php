<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan</title>
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
            max-width: 500px;
        }
        
        .header {
            text-align: center;
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
        
        .setting-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .setting-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .setting-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        .btn-back {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
        }
        
        .logs-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .log-item {
            font-family: monospace;
            font-size: 12px;
            color: #666;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .log-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">⚙️</div>
            <h1>Pengaturan</h1>
        </div>
        
        <div class="setting-item">
            <div class="setting-title">🧹 Bersihkan Cache</div>
            <div class="setting-description">
                Hapus file temporary dan cache untuk mengoptimalkan performa aplikasi.
            </div>
            <button class="btn btn-primary" onclick="clearCache()">
                Bersihkan Cache
            </button>
        </div>
        
        <div class="setting-item">
            <div class="setting-title">📋 Log Aktivitas</div>
            <div class="setting-description">
                Lihat log aktivitas penggunaan e-book launcher.
            </div>
            <button class="btn btn-secondary" onclick="showLogs()">
                Tampilkan Log
            </button>
            
            <div id="logs-section" class="logs-section" style="display: none; margin-top: 15px;">
                <div class="log-item">[2025-05-30 18:45:23] Content decrypted to: /tmp/ebook_123</div>
                <div class="log-item">[2025-05-30 18:45:24] E-book viewer launched</div>
                <div class="log-item">[2025-05-30 18:50:15] Process monitoring started</div>
                <div class="log-item">[2025-05-30 18:52:30] Temp directory cleaned</div>
            </div>
        </div>
        
        <div class="setting-item">
            <div class="setting-title">🔄 Reset Aktivasi</div>
            <div class="setting-description">
                Reset aktivasi saat ini. Anda harus memasukkan license key ulang.
            </div>
            <button class="btn btn-danger" onclick="resetActivation()">
                Reset Aktivasi
            </button>
        </div>
        
        <div class="setting-item">
            <div class="setting-title">📞 Bantuan & Support</div>
            <div class="setting-description">
                Hubungi support jika mengalami masalah dengan license atau aktivasi.
            </div>
            <button class="btn btn-secondary" onclick="showSupport()">
                Info Support
            </button>
        </div>
        
        <a href="{{ route('dashboard') }}" class="btn-back">
            ← Kembali ke Dashboard
        </a>
    </div>
    
    <script>
        function clearCache() {
            if (confirm('Yakin ingin membersihkan cache?')) {
                // Simulate cache clearing
                alert('✅ Cache berhasil dibersihkan!');
            }
        }
        
        function showLogs() {
            const logsSection = document.getElementById('logs-section');
            logsSection.style.display = logsSection.style.display === 'none' ? 'block' : 'none';
        }
        
        function resetActivation() {
            if (confirm('⚠️ Yakin ingin reset aktivasi?\n\nAnda harus memasukkan license key ulang setelah ini.')) {
                window.location.href = '/activation';
            }
        }
        
        function showSupport() {
            alert(`📞 Informasi Support:
            
                    Email: support@yourcompany.com
                    WhatsApp: +62-xxx-xxxx-xxxx
                    Website: https://yourcompany.com/support

                    Sertakan informasi berikut saat menghubungi support:
                    • License Key: {{ substr($activationData['license_key'], 0, 4) }}****
                    • Device: {{ gethostname() }}
                    • Activation Date: {{ date('d M Y', strtotime($activationData['activated_at'])) }}`);
        }
    </script>
</body>
</html>