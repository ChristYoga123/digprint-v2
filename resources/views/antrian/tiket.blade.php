<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Antrian #{{ str_pad($antrian->nomor_antrian, 3, '0', STR_PAD_LEFT) }} - Grafis</title>
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .ticket {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            text-align: center;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
        }
        .ticket::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #10b981, #059669, #047857);
        }
        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #059669;
            margin-bottom: 5px;
        }
        .date {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 30px;
        }
        .ticket-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .ticket-number {
            font-size: 120px;
            font-weight: 800;
            color: #059669;
            line-height: 1;
            margin-bottom: 20px;
        }
        .status {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 30px;
        }
        .status.waiting {
            background: #fef3c7;
            color: #d97706;
        }
        .status.called {
            background: #dcfce7;
            color: #16a34a;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .instruction {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            padding: 20px;
            background: #f9fafb;
            border-radius: 12px;
        }
        .instruction strong {
            color: #111827;
        }
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 24px;
            color: #059669;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .loket-info {
            margin-top: 20px;
            padding: 20px;
            background: #dcfce7;
            border-radius: 12px;
        }
        .loket-label {
            font-size: 12px;
            color: #059669;
            margin-bottom: 5px;
        }
        .loket-number {
            font-size: 36px;
            font-weight: 800;
            color: #059669;
        }
        .countdown {
            margin-top: 20px;
            padding: 15px;
            background: #f0fdf4;
            border-radius: 12px;
            color: #059669;
            font-size: 14px;
        }
        .countdown-number {
            font-size: 24px;
            font-weight: 800;
            display: inline-block;
            min-width: 30px;
        }
        .print-instruction {
            margin-top: 20px;
            padding: 15px;
            background: #fef3c7;
            border-radius: 12px;
            color: #92400e;
            font-size: 13px;
        }
        
        @media print {
            body {
                background: white;
            }
            .ticket {
                box-shadow: none;
                max-width: 100%;
            }
            .back-btn, .countdown {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="logo">GRAFIS</div>
        <div class="date">{{ $antrian->tanggal->translatedFormat('d F Y') }} • {{ $antrian->created_at->format('H:i') }}</div>
        
        <div class="ticket-label">Nomor Antrian</div>
        <div class="ticket-number">{{ str_pad($antrian->nomor_antrian, 3, '0', STR_PAD_LEFT) }}</div>
        
        @if($antrian->status->value === 'waiting')
            <div class="status waiting">⏳ Menunggu Dipanggil</div>
            
            <div class="print-instruction">
                💡 <strong>Simpan/cetak halaman ini</strong> sebagai bukti antrian Anda
            </div>
            
            <div class="instruction">
                Silakan tunggu hingga nomor Anda dipanggil.<br>
                <strong>Perhatikan layar display</strong> dan dengarkan pengumuman.
            </div>
            
            <div class="countdown">
                Kembali ke halaman ambil tiket dalam <span class="countdown-number" id="countdown">5</span> detik
            </div>
        @elseif($antrian->status->value === 'called')
            <div class="status called">📢 DIPANGGIL!</div>
            <div class="loket-info">
                <div class="loket-label">Silakan Menuju</div>
                <div class="loket-number">Loket {{ $antrian->deskprint_number }}</div>
            </div>
        @elseif($antrian->status->value === 'completed')
            <div class="status" style="background: #dcfce7; color: #16a34a;">✅ Selesai Dilayani</div>
        @else
            <div class="status" style="background: #fee2e2; color: #dc2626;">⏭️ Dilewati</div>
        @endif
        
        <a href="{{ route('antrian.ambil-tiket') }}" class="back-btn">← Kembali ke Halaman Utama</a>
    </div>

    @if($antrian->status->value === 'waiting')
    <script>
        let countdown = 5;
        const countdownEl = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownEl.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '{{ route("antrian.ambil-tiket") }}';
            }
        }, 1000);
    </script>
    @endif

</body>
</html>
