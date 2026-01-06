<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiProduk extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'addons' => 'json',
        'status' => \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::class,
        'panjang' => 'float',
        'lebar' => 'float',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function design()
    {
        return $this->belongsTo(ProdukProses::class, 'design_id');
    }

    public function transaksiProses()
    {
        return $this->hasMany(TransaksiProses::class);
    }

    /**
     * Cek apakah transaksi produk ini sudah selesai
     */
    public function isSelesai(): bool
    {
        // Cek dulu apakah ada proses yang dibuat untuk transaksi ini
        $totalProses = $this->transaksiProses()->count();
        
        // Jika ada proses, harus cek status semua proses
        if ($totalProses > 0) {
            $prosesSelesai = $this->transaksiProses()
                ->where('status_proses', 'Selesai')
                ->count();

            return $totalProses === $prosesSelesai;
        }

        // Jika tidak ada proses sama sekali...
        
        // Jika produk langsung selesai (seperti fotocopy), return true
        if ($this->produk && $this->produk->apakah_langsung_selesai) {
            return true;
        }

        // Jika produk tidak perlu proses dan tidak ada proses, dianggap selesai
        if ($this->produk && !$this->produk->apakah_perlu_proses) {
            return true;
        }

        // Default: belum selesai
        return false;
    }

    /**
     * Get status proses dalam bentuk string
     * Menampilkan nama proses kategori yang sedang dikerjakan
     */
    /**
     * Get status proses dalam bentuk string
     * Menampilkan nama proses kategori yang sedang dikerjakan atau status global
     */
    public function getStatusProses(): string
    {
        // Jika status global sudah final (Siap Diambil / Selesai)
        if ($this->status === \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SIAP_DIAMBIL) {
            return 'Siap Diambil';
        }
        if ($this->status === \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SELESAI) {
            return 'Selesai';
        }

        // Jika status DALAM_PROSES, tampilkan kategori spesifik
        if ($this->status === \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::DALAM_PROSES) {
            // Cari proses aktif (proses pertama yang belum selesai)
            $prosesAktif = $this->transaksiProses()
                ->where('status_proses', '!=', 'Selesai')
                ->orderBy('urutan')
                ->with('produkProses.prosesKategori')
                ->first();

            if ($prosesAktif && $prosesAktif->produkProses && $prosesAktif->produkProses->prosesKategori) {
                return $prosesAktif->produkProses->prosesKategori->nama;
            }
            return 'Dalam Proses';
        }

        return 'Belum Dimulai';
    }

    /**
     * Update status produk berdasarkan kondisi proses-prosesnya
     */
    public function refreshStatus(): void
    {
        // Jangan update jika sudah SELESAI (karena Selesai butuh aksi manual "Pesanan Diambil")
        if ($this->status === \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SELESAI) {
            return;
        }

        $totalProses = $this->transaksiProses()->count();
        
        if ($totalProses > 0) {
            // Cek apakah semua proses sudah selesai
            $semuaSelesai = $this->transaksiProses()->where('status_proses', '!=', 'Selesai')->doesntExist();
            
            if ($semuaSelesai) {
                $this->update(['status' => \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SIAP_DIAMBIL]);
                return;
            }

            // Cek apakah ada yang sudah dimulai
            $adaYangDimulai = $this->transaksiProses()->where('status_proses', '!=', 'Belum')->exists();
            
            if ($adaYangDimulai) {
                $this->update(['status' => \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::DALAM_PROSES]);
            } else {
                $this->update(['status' => \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::BELUM]);
            }
        } else {
            // Produk tanpa proses (misal Fotocopy)
            if ($this->produk && $this->produk->apakah_langsung_selesai) {
                // Dianggap Siap Diambil
                $this->update(['status' => \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SIAP_DIAMBIL]);
            } else {
                // Produk biasa tanpa proses (default Belum)
                $this->update(['status' => \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::BELUM]);
            }
        }
    }

    /**
     * Cek apakah pengerjaan sudah dimulai (minimal 1 proses dalam status Dalam Proses atau Selesai)
     */
    public function sudahMulaiPengerjaan(): bool
    {
        // Cek dulu apakah ada proses
        $totalProses = $this->transaksiProses()->count();
        
        // Jika ada proses, cek apakah ada yang sudah bukan Belum
        if ($totalProses > 0) {
            return $this->transaksiProses()
                ->where('status_proses', '!=', 'Belum')
                ->exists();
        }

        // Jika tidak ada proses sama sekali...
        
        // Jika produk langsung selesai (seperti fotocopy), dianggap sudah dimulai
        if ($this->produk && $this->produk->apakah_langsung_selesai) {
            return true;
        }

        // Jika produk tidak perlu proses dan tidak ada proses, dianggap sudah dimulai
        if ($this->produk && !$this->produk->apakah_perlu_proses) {
            return true;
        }

        // Default: belum dimulai
        return false;
    }
}

