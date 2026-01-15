<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambil Tiket Antrian - Grafis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .logo {
            font-size: 48px;
            font-weight: 800;
            color: white;
            margin-bottom: 10px;
        }
        .subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
            margin-bottom: 50px;
        }
        .card {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
        }
        .card-description {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .take-ticket-btn {
            display: block;
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.3);
        }
        .take-ticket-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(5, 150, 105, 0.4);
        }
        .take-ticket-btn:active {
            transform: translateY(0);
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 30px;
        }
        .stat-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 15px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: #059669;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        .footer {
            margin-top: 40px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
        }
        .display-link {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .display-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">GRAFIS</div>
        <div class="subtitle">Sistem Antrian Digital</div>
        
        <div class="card">
            <div class="icon">🎫</div>
            <div class="card-title">Selamat Datang!</div>
            <div class="card-description">
                Tekan tombol di bawah ini untuk mengambil nomor antrian Anda.<br>
                Mohon tunggu hingga nomor Anda dipanggil.
            </div>
            
            <form action="{{ route('antrian.proses-ambil') }}" method="POST">
                @csrf
                <button type="submit" class="take-ticket-btn">
                    🎟️ Ambil Tiket Antrian
                </button>
            </form>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value">{{ $antrianMenunggu }}</div>
                    <div class="stat-label">Sedang Menunggu</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $antrianSelesai }}</div>
                    <div class="stat-label">Sudah Dilayani</div>
                </div>
            </div>
        </div>
        
        <a href="{{ route('antrian.display') }}" class="display-link" target="_blank">
            📺 Lihat Display Antrian
        </a>
        
        <div class="footer">
            {{ now()->translatedFormat('l, d F Y') }}
        </div>
    </div>
</body>
</html>
