<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukHarga extends Model
{
    protected $guarded = ['id'];

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function customerKategori()
    {
        return $this->belongsTo(CustomerKategori::class);
    }
}
