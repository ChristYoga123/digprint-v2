<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\ProdukProsesKategori;
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
            'transaksiProduks.transaksiProses.produkProses',
            'pencatatanKeuangans',
        ])->findOrFail($transaksiId);

        // Calculate payment info
        $totalDibayar = $transaksi->pencatatanKeuangans->sum('jumlah_bayar');
        $sisaTagihan = max(0, $transaksi->total_harga_transaksi_setelah_diskon - $totalDibayar);
        
        // Prepare data
        $showDesign = $request->has('show_design');
        $showAddons = $request->has('show_addons');

        $data = [
            'transaksi' => $transaksi,
            'size' => $size,
            'kode' => $transaksi->kode,
            'tanggal' => $transaksi->created_at->format('d/m/Y H:i'),
            'customer' => $transaksi->customer->nama ?? '-',
            'kasir' => $transaksi->pencatatanKeuangans->first()->user->name ?? $transaksi->createdBy->name ?? '-', // Ambil kasir dari pembayaran pertama, fallback ke pembuat
            'deskprint' => $transaksi->createdBy->name ?? '-',
            'items' => $this->prepareItems($transaksi),
            'subtotal' => $transaksi->total_harga_transaksi,
            'diskon' => $transaksi->total_diskon_transaksi ?? 0,
            'total' => $transaksi->total_harga_transaksi_setelah_diskon,
            'total_dibayar' => $totalDibayar,
            'sisa_tagihan' => $sisaTagihan,
            'status_pembayaran' => $transaksi->status_pembayaran->getLabel() ?? $transaksi->status_pembayaran,
            'metode_pembayaran' => $transaksi->metode_pembayaran ?? '-',
            'show_design' => $showDesign,
            'show_addons' => $showAddons,
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
            // Hitung harga komponen (snapshot harga master saat ini)
            $designPrice = 0;
            $designName = null;
            if ($produk->design) {
                $designPrice = (float) $produk->design->harga;
                $designName = $produk->design->nama;
            }

            $addons = [];
            $addonsTotal = 0;
            // Filter addon dari transaksi proses (Kategori 3)
            $addonProses = $produk->transaksiProses
                ->filter(fn($tp) => $tp->produkProses && $tp->produkProses->produk_proses_kategori_id == ProdukProsesKategori::finishingId());
            
            foreach ($addonProses as $tp) {
                $price = (float) $tp->produkProses->harga;
                $addons[] = [
                    'nama' => $tp->produkProses->nama,
                    'harga' => $price
                ];
                $addonsTotal += $price;
            }

            // Hitung Base Price (Harga Total - Harga Komponen)
            // Menggunakan harga setelah diskon sebagai patokan total yang harus dibayar
            $realTotal = $produk->total_harga_produk_setelah_diskon;
            $baseTotal = $realTotal - $designPrice - $addonsTotal;
            
            // 1. Base Item (Produk Utama)
            $items[] = [
                'nama' => $produk->produk->nama ?? '-',
                'judul' => $produk->judul_pesanan,
                'ukuran' => $this->getUkuran($produk),
                'jumlah' => $produk->jumlah,
                'harga_satuan' => $produk->jumlah > 0 ? $baseTotal / $produk->jumlah : 0,
                'subtotal' => $baseTotal,
                'type' => 'main' 
            ];
            
            // 2. Item Desain (Jika ada)
            if ($designName) {
                $items[] = [
                    'nama' => "Desain: " . $designName,
                    'judul' => null,
                    'ukuran' => '-',
                    'jumlah' => 1,
                    'harga_satuan' => $designPrice,
                    'subtotal' => $designPrice,
                    'type' => 'component'
                ];
            }
            
            // 3. Item Addons (Jika ada)
            foreach ($addons as $addon) {
                $items[] = [
                    'nama' => "Addon: " . $addon['nama'],
                    'judul' => null,
                    'ukuran' => '-',
                    'jumlah' => 1,
                    'harga_satuan' => $addon['harga'],
                    'subtotal' => $addon['harga'],
                    'type' => 'component'
                ];
            }
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
