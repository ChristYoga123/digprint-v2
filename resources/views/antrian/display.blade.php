<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Antrian - Grafis</title>
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
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: white;
            overflow: hidden;
        }

        .display-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            height: 100vh;
        }

        .main-panel {
            display: flex;
            flex-direction: column;
            padding: 40px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 32px;
            font-weight: 800;
        }

        .clock {
            font-size: 36px;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .date {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 5px;
        }

        .called-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .called-label {
            font-size: 24px;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .called-numbers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            width: 100%;
            max-width: 1200px;
        }

        .called-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
        }

        .called-card.highlight {
            background: rgba(255, 255, 255, 0.25);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }
        }

        .called-number {
            font-size: 100px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 10px;
        }

        .called-loket {
            font-size: 28px;
            font-weight: 600;
            opacity: 0.9;
        }

        .no-called {
            text-align: center;
            opacity: 0.7;
        }

        .no-called-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .no-called-text {
            font-size: 24px;
        }

        .side-panel {
            background: #1e293b;
            padding: 30px;
            display: flex;
            flex-direction: column;
        }

        .side-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .side-title {
            font-size: 18px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .queue-count {
            font-size: 48px;
            font-weight: 800;
            color: #fbbf24;
            margin-top: 10px;
        }

        .queue-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }

        .waiting-list {
            flex: 1;
            overflow-y: auto;
        }

        .waiting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .waiting-item:first-child {
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .waiting-item:first-child .waiting-number {
            color: #fbbf24;
        }

        .waiting-number {
            font-size: 28px;
            font-weight: 700;
        }

        .waiting-time {
            font-size: 12px;
            color: #64748b;
        }

        .next-label {
            font-size: 10px;
            color: #fbbf24;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .empty-queue {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        /* Audio overlay */
        .audio-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .audio-overlay.hidden {
            display: none;
        }

        .audio-prompt {
            text-align: center;
            padding: 60px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 24px;
        }

        .audio-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .audio-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .audio-desc {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .audio-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 16px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
        }

        .audio-btn:hover {
            transform: scale(1.05);
        }

        .connection-status {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 100;
        }

        .connection-status.connected {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .connection-status.disconnected {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
    </style>
</head>

<body>
    <div class="connection-status disconnected" id="connectionStatus">⚪ Menghubungkan...</div>

    {{-- Audio Overlay --}}
    <div class="audio-overlay" id="audioOverlay">
        <div class="audio-prompt">
            <div class="audio-icon">🔊</div>
            <div class="audio-title">Aktifkan Suara</div>
            <div class="audio-desc">Klik tombol di bawah untuk mengaktifkan pengumuman suara</div>
            <button class="audio-btn" onclick="enableAudio()">Aktifkan Suara</button>
        </div>
    </div>

    <div class="display-container">
        <div class="main-panel">
            <div class="header">
                <div class="logo">GRAFIS</div>
                <div>
                    <div class="clock" id="clock">00:00:00</div>
                    <div class="date" id="date"></div>
                </div>
            </div>

            <div class="called-section">
                <div class="called-label">📢 Nomor yang Dipanggil</div>
                <div class="called-numbers-grid" id="calledGrid">
                    @if (count($calledAntrians) > 0)
                        @foreach ($calledAntrians as $index => $antrian)
                            <div class="called-card {{ $index === 0 ? 'highlight' : '' }}">
                                <div class="called-number">
                                    {{ str_pad($antrian['nomor_antrian'], 3, '0', STR_PAD_LEFT) }}</div>
                                <div class="called-loket">→ Loket {{ $antrian['deskprint_number'] }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="no-called">
                            <div class="no-called-icon">⏳</div>
                            <div class="no-called-text">Menunggu panggilan...</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="side-panel">
            <div class="side-header">
                <div class="side-title">Antrian Menunggu</div>
                <div class="queue-count" id="queueCount">{{ count($waitingAntrians) }}</div>
                <div class="queue-subtitle">orang dalam antrian</div>
            </div>

            <div class="waiting-list" id="waitingList">
                @if (count($waitingAntrians) > 0)
                    @foreach ($waitingAntrians as $index => $antrian)
                        <div class="waiting-item">
                            <div>
                                @if ($index === 0)
                                    <div class="next-label">Selanjutnya</div>
                                @endif
                                <div class="waiting-number">
                                    {{ str_pad($antrian['nomor_antrian'], 3, '0', STR_PAD_LEFT) }}</div>
                            </div>
                            <div class="waiting-time">{{ \Carbon\Carbon::parse($antrian['created_at'])->format('H:i') }}
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty-queue">
                        <div class="empty-icon">✨</div>
                        <div>Tidak ada antrian</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // ============ CLOCK ============
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('id-ID');
            document.getElementById('date').textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }
        updateClock();
        setInterval(updateClock, 1000);

        // ============ AUDIO ============
        // PENTING: Browser membutuhkan interaksi user sebelum audio bisa diputar.
        // Jadi overlay SELALU muncul saat halaman pertama kali dibuka.
        let audioEnabled = false; // Selalu false sampai user klik
        let isFirstPoll = true; // Flag untuk skip speech pada polling pertama

        function enableAudio() {
            console.log('Enable audio clicked');

            // Langsung set audioEnabled = true dan sembunyikan overlay
            audioEnabled = true;
            document.getElementById('audioOverlay').classList.add('hidden');

            // Coba unlock speech synthesis dengan suara kosong
            try {
                const u = new SpeechSynthesisUtterance('');
                u.volume = 0;
                speechSynthesis.speak(u);
                console.log('Speech synthesis unlocked');
            } catch (err) {
                console.warn('Speech synthesis unlock failed:', err);
            }
        }

        // ============ VOICE QUEUE ============
        let isSpeaking = false;
        const voiceQueue = [];

        function processVoiceQueue() {
            if (isSpeaking || voiceQueue.length === 0) return;

            const {
                nomor,
                loket
            } = voiceQueue.shift();
            doSpeak(nomor, loket);
        }

        function speak(nomor, loket) {
            if (!audioEnabled) {
                console.log('Audio not enabled, skipping speech');
                return;
            }

            console.log('Queueing speech:', nomor, loket);
            voiceQueue.push({
                nomor,
                loket
            });
            processVoiceQueue();
        }

        function doSpeak(nomor, loket) {
            isSpeaking = true;
            console.log('Speaking:', nomor, loket);

            const text = `Nomor antrian, ${String(nomor).padStart(3, '0')}, silakan menuju ke, loket ${loket}`;
            const u = new SpeechSynthesisUtterance(text);
            u.lang = 'id-ID';
            u.rate = 0.85;
            u.volume = 1;

            u.onstart = () => console.log('Speech started');
            u.onend = () => {
                console.log('Speech ended');
                isSpeaking = false;
                setTimeout(processVoiceQueue, 500);
            };
            u.onerror = (e) => {
                console.error('Speech error:', e.error);
                isSpeaking = false;
                setTimeout(processVoiceQueue, 500);
            };

            const voices = speechSynthesis.getVoices();
            const idVoice = voices.find(v => v.lang.includes('id'));
            if (idVoice) u.voice = idVoice;

            try {
                speechSynthesis.speak(u);
            } catch (err) {
                console.error('Synthesis error:', err);
                isSpeaking = false;
            }
        }

        // Preload voices
        speechSynthesis.getVoices();
        speechSynthesis.onvoiceschanged = () => speechSynthesis.getVoices();

        // ============ UI UPDATE ============
        function updateCalledGrid(antrians) {
            const grid = document.getElementById('calledGrid');

            if (!antrians || antrians.length === 0) {
                grid.innerHTML =
                    `<div class="no-called"><div class="no-called-icon">⏳</div><div class="no-called-text">Menunggu panggilan...</div></div>`;
                return;
            }
            grid.innerHTML = antrians.map((a, i) => `
                <div class="called-card ${i === 0 ? 'highlight' : ''}">
                    <div class="called-number">${String(a.nomor_antrian).padStart(3, '0')}</div>
                    <div class="called-loket">→ Loket ${a.deskprint_number}</div>
                </div>
            `).join('');
        }

        function updateWaitingList(antrians) {
            const list = document.getElementById('waitingList');
            const count = document.getElementById('queueCount');
            count.textContent = antrians ? antrians.length : 0;

            if (!antrians || antrians.length === 0) {
                list.innerHTML =
                    `<div class="empty-queue"><div class="empty-icon">✨</div><div>Tidak ada antrian</div></div>`;
                return;
            }
            list.innerHTML = antrians.map((a, i) => `
                <div class="waiting-item">
                    <div>
                        ${i === 0 ? '<div class="next-label">Selanjutnya</div>' : ''}
                        <div class="waiting-number">${String(a.nomor_antrian).padStart(3, '0')}</div>
                    </div>
                    <div class="waiting-time">${a.created_at || ''}</div>
                </div>
            `).join('');
        }

        // ============ POLLING SYSTEM ============
        let lastCalledId = null;

        async function pollData() {
            try {
                const response = await fetch('/antrian/display-data');
                const data = await response.json();

                // Update UI
                updateCalledGrid(data.calledAntrians);
                updateWaitingList(data.waitingAntrians);

                // Check if there is a called antrian at the top
                if (data.calledAntrians && data.calledAntrians.length > 0) {
                    const topCall = data.calledAntrians[0];
                    const currentKey = `${topCall.nomor_antrian}-${topCall.called_at}`;

                    // Pada polling pertama, hanya inisialisasi lastCalledId tanpa berbicara
                    if (isFirstPoll) {
                        lastCalledId = currentKey;
                        isFirstPoll = false;
                        console.log('First poll - initialized lastCalledId:', currentKey);
                    } else if (lastCalledId !== currentKey) {
                        // Ada panggilan BARU
                        lastCalledId = currentKey;
                        console.log('New call detected:', currentKey);
                        speak(topCall.nomor_antrian, topCall.deskprint_number);
                    }
                } else {
                    // Tidak ada yang dipanggil
                    if (isFirstPoll) {
                        isFirstPoll = false;
                    }
                    lastCalledId = null;
                }

                // Connection status
                const statusEl = document.getElementById('connectionStatus');
                statusEl.textContent = '🟢 Terhubung (Polling)';
                statusEl.className = 'connection-status connected';

            } catch (error) {
                console.error('Polling error:', error);
                const statusEl = document.getElementById('connectionStatus');
                statusEl.textContent = '🔴 Terputus';
                statusEl.className = 'connection-status disconnected';
            }
        }

        // Poll every 3 seconds
        setInterval(pollData, 3000);
        pollData(); // Initial call
    </script>
</body>

</html>
