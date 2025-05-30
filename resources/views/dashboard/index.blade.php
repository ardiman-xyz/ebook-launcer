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
            max-width: 500px;
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
        
        .main-action {
            margin-bottom: 30px;
        }
        
        .btn-primary {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
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
        
        <div class="main-action">
            <button class="btn-primary" onclick="launchEbook()">
                🚀 Buka E-book
            </button>
            <div class="loading" id="loading">
                <div class="spinner"></div>
                Membuka e-book...
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
        async function launchEbook() {
            const button = document.querySelector('.btn-primary');
            const loading = document.getElementById('loading');
            
            // Show loading
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
                    loading.innerHTML = '<div class="spinner"></div>E-book berhasil dibuka!';
                    setTimeout(() => {
                        button.style.display = 'block';
                        loading.style.display = 'none';
                    }, 2000);
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
    </script>
</body>
</html>