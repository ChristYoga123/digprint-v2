<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;

class PrintNotaController extends Controller
{
    public function print(Request $request)
    {
        $transaksiId = $request->get('transaksi_id');
        $size = $request->get('size', 'thermal'); // thermal, a5, a4
        
        $transaksi = Transaksi::with([
            'customer',
            'createdBy',
            'transaksiProduks.produk',
            'transaksiProduks.design',
            'pencatatanKeuangans',
        ])->findOrFail($transaksiId);

        // Calculate payment info
        $totalDibayar = $transaksi->pencatatanKeuangans->sum('jumlah_bayar');
        $sisaTagihan = max(0, $transaksi->total_harga_transaksi_setelah_diskon - $totalDibayar);
        
        // Prepare data
        $data = [
            'transaksi' => $transaksi,
            'size' => $size,
            'kode' => $transaksi->kode,
            'tanggal' => $transaksi->created_at->format('d/m/Y H:i'),
            'customer' => $transaksi->customer->nama ?? '-',
            'kasir' => $transaksi->createdBy->name ?? '-',
            'items' => $this->prepareItems($transaksi),
            'subtotal' => $transaksi->total_harga_transaksi,
            'diskon' => $transaksi->total_diskon_transaksi ?? 0,
            'total' => $transaksi->total_harga_transaksi_setelah_diskon,
            'total_dibayar' => $totalDibayar,
            'sisa_tagihan' => $sisaTagihan,
            'status_pembayaran' => $transaksi->status_pembayaran->getLabel() ?? $transaksi->status_pembayaran,
            'metode_pembayaran' => $transaksi->metode_pembayaran ?? '-',
        ];

        return view('print.nota', $data);
    }

    /**
     * Prepare items data from transaksi produks
     */
    private function prepareItems($transaksi): array
    {
        $items = [];
        
        foreach ($transaksi->transaksiProduks as $produk) {
            $items[] = [
                'nama' => $produk->produk->nama ?? '-',
                'judul' => $produk->judul_pesanan,
                'jumlah' => $produk->jumlah,
                'ukuran' => $this->getUkuran($produk),
                'harga_satuan' => $produk->jumlah > 0 
                    ? round($produk->total_harga_produk_sebelum_diskon / $produk->jumlah) 
                    : 0,
                'diskon' => $produk->total_diskon_produk ?? 0,
                'subtotal' => $produk->total_harga_produk_setelah_diskon,
            ];
        }
        
        return $items;
    }

    /**
     * Get ukuran info
     */
    private function getUkuran($produk): string
    {
        if ($produk->panjang && $produk->lebar) {
            return $produk->panjang . ' x ' . $produk->lebar;
        }
        return '-';
    }
}
