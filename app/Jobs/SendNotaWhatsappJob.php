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

class SendNotaWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $transaksiId,
        public bool $isDiscountApproved = false,
        public bool $isDiscountRejected = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FontteService $fontteService): void
    {
        $transaksi = Transaksi::with(['customer', 'transaksiProduks.produk'])->find($this->transaksiId);

        if (!$transaksi || !$transaksi->customer) {
            Log::warning('SendNotaWhatsappJob: Transaksi atau customer tidak ditemukan', [
                'transaksi_id' => $this->transaksiId,
            ]);
            return;
        }

        // Ambil nomor HP customer (prioritas no_hp1, fallback ke no_hp2)
        $noHp = $transaksi->customer->no_hp1 ?? $transaksi->customer->no_hp2 ?? null;

        if (empty($noHp)) {
            Log::warning('SendNotaWhatsappJob: Nomor HP customer tidak tersedia', [
                'transaksi_id' => $this->transaksiId,
                'customer_id' => $transaksi->customer->id,
            ]);
            return;
        }

        $message = $this->buildMessage($transaksi);

        $result = $fontteService->send($noHp, $message);

        if (!$result['success']) {
            Log::error('SendNotaWhatsappJob: Gagal mengirim WA', [
                'transaksi_id' => $this->transaksiId,
                'error' => $result['message'],
            ]);
        }
    }

    /**
     * Build WhatsApp message content
     * Template sama untuk semua kondisi (tanpa info diskon approved/rejected)
     */
    protected function buildMessage(Transaksi $transaksi): string
    {
        $items = [];
        foreach ($transaksi->transaksiProduks as $produk) {
            $ukuran = '';
            if ($produk->panjang && $produk->lebar) {
                $ukuran = " ({$produk->panjang}x{$produk->lebar} cm)";
            }
            $items[] = "• {$produk->produk->nama}{$ukuran} x{$produk->jumlah}";
        }
        $itemList = implode("\n", $items);

        // Format harga - ambil dari data terbaru transaksi
        $subtotal = formatRupiah($transaksi->total_harga_transaksi);
        $total = formatRupiah($transaksi->total_harga_transaksi_setelah_diskon);
        $diskon = $transaksi->total_diskon_transaksi ?? 0;
        $totalDibayar = formatRupiah($transaksi->jumlah_bayar ?? 0);

        $message = "🧾 *NOTA TRANSAKSI*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "*Invoice:* {$transaksi->kode}\n";
        $message .= "*Customer:* {$transaksi->customer->nama}\n";
        $message .= "*Tanggal:* " . $transaksi->created_at->format('d/m/Y H:i') . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "*Detail Pesanan:*\n";
        $message .= "{$itemList}\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "*Subtotal:* {$subtotal}\n";

        if ($diskon > 0) {
            $message .= "*Diskon:* -" . formatRupiah($diskon) . "\n";
        }

        $message .= "*Total:* {$total}\n";
        $message .= "*Dibayar:* {$totalDibayar}\n";

        $sisa = ($transaksi->total_harga_transaksi_setelah_diskon ?? 0) - ($transaksi->jumlah_bayar ?? 0);
        if ($sisa > 0) {
            $message .= "*Sisa:* " . formatRupiah($sisa) . "\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "Terima kasih telah berbelanja di *" . config('app.name', 'DigPrint') . "*! 🙏";

        return $message;
    }
}
