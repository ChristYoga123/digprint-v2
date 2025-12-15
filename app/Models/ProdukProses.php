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
        return $this->belongsTo(ProdukProsesKategori::class);
    }

    public function produkProsesBahans()
    {
        return $this->hasMany(ProdukProsesBahan::class);
    }

    public function mesin()
    {
        return $this->belongsTo(Mesin::class);
    }
}
