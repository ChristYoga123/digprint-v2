<?php

namespace App\Http\Controllers;

use App\Models\TransaksiProduk;
use App\Models\ProdukProsesKategori;
use Illuminate\Http\Request;

class PrintSpkController extends Controller
{
    public function print(Request $request)
    {
        $transaksiProdukId = $request->get('transaksi_produk_id');
        
        $transaksiProduk = TransaksiProduk::with([
            'transaksi.customer',
            'transaksi.createdBy',
            'produk',
            'transaksiProses' => function($query) {
                $query->orderBy('urutan');
            },
            'transaksiProses.produkProses.mesin',
            'transaksiProses.produkProses.produkProsesKategori',
            'transaksiProses.produkProses.produkProsesBahans.bahan',
        ])->findOrFail($transaksiProdukId);

        // Collect all processes for this product
        $sheets = [];
        
        foreach ($transaksiProduk->transaksiProses as $transaksiProses) {
            $produkProses = $transaksiProses->produkProses;
            $kategori = $produkProses->produkProsesKategori;
            
            // Divisi = nama mesin, jika tidak ada maka nama kategori proses
            $divisi = $produkProses->mesin?->nama ?? $kategori->nama;
            
            // Jenis cetak = nama proses
            $jenisCetak = $produkProses->nama;
            
            $sheets[] = [
                'divisi' => $divisi,
                'nota' => $transaksiProduk->transaksi->kode,
                'tgl_order' => $transaksiProduk->transaksi->created_at->format('d-M-y'),
                'dateline' => $transaksiProduk->transaksi->tanggal_jatuh_tempo 
                    ? \Carbon\Carbon::parse($transaksiProduk->transaksi->tanggal_jatuh_tempo)->format('d-M-y')
                    : '-',
                'pemesan' => $transaksiProduk->transaksi->customer->nama ?? '-',
                'nama_file' => $transaksiProduk->judul_pesanan ?? '-',
                'nama_produk' => $transaksiProduk->produk->nama ?? '-',
                'jenis_cetak' => $jenisCetak,
                'bahan' => $this->getBahanInfo($produkProses),
                'satuan' => $this->getSatuanInfo($transaksiProduk),
                'ukuran' => $this->getUkuranInfo($transaksiProduk),
                'jml_cetak' => $transaksiProduk->jumlah,
                'finishing' => $this->getFinishingInfo($transaksiProduk),
                'paraf_desain' => '-None-',
                'operator' => '(Belum terproduksi)',
                'created_by' => $transaksiProduk->transaksi->createdBy->name ?? '(-)' ,
                'datetime_print' => now()->format('d F Y H:i'),
                'urutan_proses' => $transaksiProses->urutan,
                'kategori_proses' => $kategori->nama,
            ];
        }

        return view('print.spk', [
            'sheets' => $sheets,
            'transaksiProduk' => $transaksiProduk,
        ]);
    }

    /**
     * Get bahan info from produk proses
     */
    private function getBahanInfo($produkProses): string
    {
        if (!$produkProses->produkProsesBahans || $produkProses->produkProsesBahans->isEmpty()) {
            return '-';
        }

        $bahanNames = $produkProses->produkProsesBahans->map(function($proseBahan) {
            return $proseBahan->bahan->nama ?? '-';
        })->filter()->unique()->implode(', ');

        return $bahanNames ?: '-';
    }

    /**
     * Get satuan info
     */
    private function getSatuanInfo($transaksiProduk): string
    {
        return 'Lembar';
    }

    /**
     * Get ukuran info
     */
    private function getUkuranInfo($transaksiProduk): string
    {
        if ($transaksiProduk->panjang && $transaksiProduk->lebar) {
            return $transaksiProduk->panjang . ' x ' . $transaksiProduk->lebar . ' cm';
        }
        return '-';
    }

    /**
     * Get finishing info from transaksi proses
     */
    private function getFinishingInfo($transaksiProduk): string
    {
        $finishingProcesses = $transaksiProduk->transaksiProses
            ->filter(function($proses) {
                // Kategori 3 = Finishing (based on typical seeder data)
                return $proses->produkProses->produk_proses_kategori_id == ProdukProsesKategori::finishingId();
            })
            ->map(function($proses) {
                return $proses->produkProses->nama;
            })
            ->implode(', ');

        return $finishingProcesses ?: '-None-';
    }
}
