<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ProdukProsesKategori extends Model
{
    protected $guarded = ['id'];

    // Nama kategori (sesuai dengan seeder)
    public const NAMA_PRA_PRODUKSI = 'Pra Produksi';
    public const NAMA_PRODUKSI = 'Produksi';
    public const NAMA_FINISHING = 'Finishing';

    /**
     * Get ID for Pra Produksi kategori
     */
    public static function praProduksiId(): ?int
    {
        return Cache::remember('produk_proses_kategori_pra_produksi_id', 3600, function () {
            return static::where('nama', self::NAMA_PRA_PRODUKSI)->value('id');
        });
    }

    /**
     * Get ID for Produksi kategori
     */
    public static function produksiId(): ?int
    {
        return Cache::remember('produk_proses_kategori_produksi_id', 3600, function () {
            return static::where('nama', self::NAMA_PRODUKSI)->value('id');
        });
    }

    /**
     * Get ID for Finishing kategori
     */
    public static function finishingId(): ?int
    {
        return Cache::remember('produk_proses_kategori_finishing_id', 3600, function () {
            return static::where('nama', self::NAMA_FINISHING)->value('id');
        });
    }

    /**
     * Clear cached IDs
     */
    public static function clearIdCache(): void
    {
        Cache::forget('produk_proses_kategori_pra_produksi_id');
        Cache::forget('produk_proses_kategori_produksi_id');
        Cache::forget('produk_proses_kategori_finishing_id');
    }
}
