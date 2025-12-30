<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukProses extends Model
{
    protected $guarded = ['id'];

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function prosesKategori()
    {
        return $this->belongsTo(ProdukProsesKategori::class, 'produk_proses_kategori_id');
    }

    public function produkProsesKategori()
    {
        return $this->belongsTo(ProdukProsesKategori::class, 'produk_proses_kategori_id');
    }

    public function produkProsesBahans()
    {
        return $this->hasMany(ProdukProsesBahan::class);
    }

    public function mesin()
    {
        return $this->belongsTo(Mesin::class);
    }

    public function proses()
    {
        return $this->belongsTo(Proses::class);
    }
}
