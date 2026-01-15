# Setup Laravel Reverb untuk Antrian Real-time

## 1. Install Dependencies

```bash
# Install Laravel Reverb
composer require laravel/reverb

# Install Reverb (akan membuat config dan migration)
php artisan reverb:install

# Install NPM dependencies untuk Laravel Echo
npm install laravel-echo pusher-js
```

## 2. Konfigurasi Environment (.env)

Tambahkan ke file `.env`:

```env
# Broadcasting
BROADCAST_CONNECTION=reverb

# Reverb Configuration
REVERB_APP_ID=my-app-id
REVERB_APP_KEY=my-app-key
REVERB_APP_SECRET=my-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite (untuk frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Untuk **production** (dengan HTTPS):

```env
REVERB_HOST="your-domain.com"
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## 3. Build Assets

```bash
npm run build
# atau untuk development
npm run dev
```

## 4. Jalankan Reverb Server

Di terminal terpisah, jalankan:

```bash
php artisan reverb:start
```

Untuk production, gunakan supervisor atau systemd:

```ini
# /etc/supervisor/conf.d/reverb.conf
[program:reverb]
process_name=%(program_name)s
command=php /path/to/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

## 5. Cara Kerja

### Arsitektur

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Loket 1    │     │  Loket 2    │     │  Loket 3    │
│  (Admin)    │     │  (Admin)    │     │  (Admin)    │
└──────┬──────┘     └──────┬──────┘     └──────┬──────┘
       │                   │                   │
       └───────────────────┼───────────────────┘
                           │ broadcast()
                           ▼
                    ┌─────────────┐
                    │   Reverb    │
                    │  WebSocket  │
                    │   Server    │
                    └──────┬──────┘
                           │ Push events
                           ▼
                    ┌─────────────┐
                    │   Display   │ ← Voice announcement
                    │  (TV/Monitor)│   dengan queue system
                    └─────────────┘
```

### Events yang Di-broadcast

1. **AntrianDipanggil** - Ketika nomor antrian dipanggil
   - Trigger voice announcement di display
   - Data: `{ nomor, loket, called_at }`

2. **AntrianUpdated** - Ketika ada perubahan data antrian
   - Update UI display secara real-time
   - Data: `{ statistik, calledAntrians, waitingAntrians }`

## 6. Testing

1. Buka halaman admin antrian di browser 1
2. Buka `/antrian/display` di browser 2 (atau TV)
3. Klik "Panggil Berikutnya" di admin
4. Display akan:
   - Update UI secara real-time (tanpa refresh)
   - Memutar voice announcement dengan queue

## 7. Troubleshooting

### Voice tidak keluar
- Pastikan browser mengizinkan audio autoplay
- Klik sekali di halaman display untuk enable audio

### Tidak real-time
- Cek apakah Reverb server berjalan: `php artisan reverb:start`
- Cek console browser untuk error WebSocket
- Pastikan `BROADCAST_CONNECTION=reverb` di .env

### Connection status "Terputus"
- Cek apakah port Reverb tidak diblok firewall
- Untuk HTTPS, pastikan SSL certificate valid
