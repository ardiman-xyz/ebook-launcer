<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi E-book</title>
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
            max-width: 450px;
            text-align: center;
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
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: monospace;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .help-text {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
            border-left: 4px solid #667eea;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #c33;
        }
        
        .success {
            background: #efe;
            color: #3c3;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #3c3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📚</div>
        <h1>Aktivasi E-book</h1>
        <p class="subtitle">Masukkan license key untuk mengakses e-book Anda</p>
        
        @if($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif
        
        @if(session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif
        
        <div class="help-text">
            💡 <strong>Petunjuk:</strong><br>
            License key terdapat dalam file <code>license_key.txt</code> yang disertakan dalam paket download Anda.
            Format: XXXX-XXXX-XXXX-XXXX
        </div>
        
        <form method="POST" action="{{ route('activate') }}">
            @csrf
            
            <div class="form-group">
                <label for="license_key">License Key</label>
                <input 
                    type="text" 
                    id="license_key" 
                    name="license_key" 
                    placeholder="ABCD-1234-EFGH-5678"
                    value="{{ old('license_key') }}"
                    required
                    style="text-transform: uppercase;"
                >
            </div>
            
            <button type="submit" class="btn">
                🚀 Aktivasi Sekarang
            </button>
        </form>
    </div>
    
    <script>
        // Auto format license key
        document.getElementById('license_key').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^A-Z0-9]/g, '');
            let formatted = value.match(/.{1,4}/g)?.join('-') || value;
            if (formatted.length > 19) formatted = formatted.substring(0, 19);
            e.target.value = formatted;
        });
    </script>
</body>
</html>