<?php

namespace App\Jobs;

use App\Models\Transaksi;
use App\Services\FontteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSiapDiambilWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $transaksiId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FontteService $fontteService): void
    {
        $transaksi = Transaksi::with(['customer', 'transaksiProduks.produk'])->find($this->transaksiId);

        if (!$transaksi || !$transaksi->customer) {
            Log::warning('SendSiapDiambilWhatsappJob: Transaksi atau customer tidak ditemukan', [
                'transaksi_id' => $this->transaksiId,
            ]);
            return;
        }

        // Ambil nomor HP customer (prioritas no_hp1, fallback ke no_hp2)
        $noHp = $transaksi->customer->no_hp1 ?? $transaksi->customer->no_hp2 ?? null;

        if (empty($noHp)) {
            Log::warning('SendSiapDiambilWhatsappJob: Nomor HP customer tidak tersedia', [
                'transaksi_id' => $this->transaksiId,
                'customer_id' => $transaksi->customer->id,
            ]);
            return;
        }

        $message = $this->buildMessage($transaksi);

        $result = $fontteService->send($noHp, $message);

        if (!$result['success']) {
            Log::error('SendSiapDiambilWhatsappJob: Gagal mengirim WA', [
                'transaksi_id' => $this->transaksiId,
                'error' => $result['message'],
            ]);
        }
    }

    /**
     * Build WhatsApp message for ready to pickup notification
     */
    protected function buildMessage(Transaksi $transaksi): string
    {
        $items = [];
        foreach ($transaksi->transaksiProduks as $produk) {
            $items[] = "• {$produk->produk->nama} x{$produk->jumlah}";
        }
        $itemList = implode("\n", $items);

        $message = "🎉 *PESANAN SIAP DIAMBIL!*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "Halo *{$transaksi->customer->nama}*!\n\n";

        $message .= "Pesanan Anda dengan invoice:\n";
        $message .= "*{$transaksi->kode}*\n\n";

        $message .= "Telah *SELESAI* dan siap untuk diambil.\n\n";

        $message .= "*Detail Pesanan:*\n";
        $message .= "{$itemList}\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";

        // Cek sisa pembayaran
        $sisa = ($transaksi->total_harga_transaksi_setelah_diskon ?? 0) - ($transaksi->jumlah_bayar ?? 0);
        if ($sisa > 0) {
            $message .= "⚠️ *Sisa Pembayaran:* " . formatRupiah($sisa) . "\n";
            $message .= "_Mohon lunasi sisa pembayaran saat pengambilan._\n\n";
        }

        $message .= "Silakan datang ke toko kami untuk mengambil pesanan Anda.\n\n";

        $message .= "Terima kasih! 🙏\n";
        $message .= "*" . config('app.name', 'DigPrint') . "*";

        return $message;
    }
}
