<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\TransaksiProses\StatusProsesEnum;
use App\Models\ProdukProsesKategori;

class TransaksiProses extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status_proses' => StatusProsesEnum::class,
    ];

    public function transaksiProduk()
    {
        return $this->belongsTo(TransaksiProduk::class);
    }

    public function produkProses()
    {
        return $this->belongsTo(ProdukProses::class);
    }

    public function kloter()
    {
        return $this->belongsTo(Kloter::class);
    }

    public function transaksiProsesSample()
    {
        return $this->hasOne(TransaksiProsesSample::class);
    }

    public function bahanUsages()
    {
        return $this->hasMany(TransaksiProsesBahanUsage::class);
    }

    /**
     * Get all workers who worked on this process
     */
    public function karyawanPekerjaans()
    {
        return $this->morphMany(KaryawanPekerjaan::class, 'karyawan_pekerjaan');
    }

    public function canStartProduction(): bool
    {
        // Cek apakah proses sebelumnya sudah selesai
        if ($this->urutan <= 1) {
            return true; // Proses pertama bisa langsung dimulai
        }

        // Cek proses sebelumnya
        $previousProses = static::where('transaksi_produk_id', $this->transaksi_produk_id)
            ->where('urutan', '<', $this->urutan)
            ->get();

        foreach ($previousProses as $proses) {
            if ($proses->status_proses !== StatusProsesEnum::SELESAI) {
                return false;
            }
        }

        return true;
    }

    public function requiresSample(): bool
    {
        // Sample hanya untuk proses produksi (kategori 2) yang mengurangi bahan
        return $this->produkProses 
            && $this->produkProses->produk_proses_kategori_id == ProdukProsesKategori::produksiId()
            && $this->produkProses->apakah_mengurangi_bahan;
    }

    public function getMaterialNeeds(): array
    {
        // Get material needs from produkProsesBahans
        if (!$this->produkProses) {
            return [];
        }

        // Load produkProsesBahans if not already loaded
        $this->produkProses->load('produkProsesBahans');

        $needs = [];
        foreach ($this->produkProses->produkProsesBahans as $produkProsesBahan) {
            $needs[] = [
                'bahan_id' => $produkProsesBahan->bahan_id,
                'jumlah_per_unit' => $produkProsesBahan->jumlah,
                'apakah_dipengaruhi_oleh_dimensi' => $produkProsesBahan->apakah_dipengaruhi_oleh_dimensi,
            ];
        }

        return $needs;
    }
}
