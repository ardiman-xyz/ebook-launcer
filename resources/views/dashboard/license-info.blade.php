<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Lisensi</title>
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
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        
        .info-value {
            color: #6c757d;
            font-family: monospace;
        }
        
        .status-active {
            color: #28a745;
            font-weight: 600;
        }
        
        .device-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .device-info h3 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .fingerprint {
            font-family: monospace;
            font-size: 12px;
            color: #666;
            word-break: break-all;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
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
        
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">ℹ️</div>
            <h1>Informasi Lisensi</h1>
        </div>
        
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">License Key:</span>
                <span class="info-value">{{ $activationData['license_key'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="status-active">✅ Aktif</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal Aktivasi:</span>
                <span class="info-value">{{ date('d M Y, H:i:s', strtotime($activationData['activated_at'])) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Token:</span>
                <span class="info-value">{{ substr($activationData['activation_token'], 0, 16) }}...</span>
            </div>
        </div>
        
        <div class="device-info">
            <h3>🖥️ Informasi Device</h3>
            <div class="info-row">
                <span class="info-label">Hostname:</span>
                <span class="info-value">{{ gethostname() }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Operating System:</span>
                <span class="info-value">{{ PHP_OS }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">PHP Version:</span>
                <span class="info-value">{{ PHP_VERSION }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">User:</span>
                <span class="info-value">{{ get_current_user() }}</span>
            </div>
            
            <div class="fingerprint">
                <strong>Device Fingerprint:</strong><br>
                {{ $activationData['device_fingerprint'] }}
            </div>
        </div>
        
        <div class="warning">
            <strong>⚠️ Perhatian:</strong><br>
            License ini terikat dengan device ini. Jika Anda mengganti hardware atau pindah ke komputer lain, 
            Anda perlu mengontak support untuk reset license.
        </div>
        
        <a href="{{ route('dashboard') }}" class="btn-back">
            ← Kembali ke Dashboard
        </a>
    </div>
</body>
</html>